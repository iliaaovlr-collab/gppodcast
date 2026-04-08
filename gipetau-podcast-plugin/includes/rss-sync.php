<?php
/**
 * RSS Sync — импорт и синхронизация выпусков из RSS-ленты подкаста.
 *
 * Идентификация: по guid (приоритет), fallback на enclosure URL.
 * Новые → publish. Удалённые из RSS → draft. Изменённые → обновляются.
 * Обложки и аудио сохраняются в медиабиблиотеку WP.
 */
defined('ABSPATH') || exit;

// Крон-хук
add_action('gpp_rss_sync_hook', 'gpp_rss_sync');

/**
 * Основная функция синхронизации.
 * $limit: сколько выпусков импортировать (0 = все). Используется при первичном заполнении.
 */
function gpp_rss_sync($limit = 0) {
    $rss_url = get_option('gpp_rss_url', '');
    if (empty($rss_url)) return ['error' => 'RSS URL не задан'];

    $response = wp_remote_get($rss_url, ['timeout' => 30]);
    if (is_wp_error($response)) return ['error' => $response->get_error_message()];

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) return ['error' => 'Пустой ответ RSS'];

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    if (!$xml) return ['error' => 'Невалидный XML'];

    $channel = $xml->channel;
    if (!$channel) return ['error' => 'Нет <channel> в RSS'];

    // Пространства имён
    $ns = $xml->getNamespaces(true);
    $itunes_ns = $ns['itunes'] ?? 'http://www.itunes.com/dtds/podcast-1.0.dtd';

    // Save podcast cover as fallback for episodes without own cover
    $ch_itunes = $channel->children($itunes_ns);
    $podcast_cover = '';
    if (isset($ch_itunes->image)) {
        $podcast_cover = (string) ($ch_itunes->image->attributes()['href'] ?? '');
    }
    if (!$podcast_cover && isset($channel->image->url)) {
        $podcast_cover = (string) $channel->image->url;
    }
    if ($podcast_cover) update_option('gpp_podcast_cover', $podcast_cover);

    // ═══ Страница «О подкасте» (при первом запуске) ═══
    gpp_sync_about_page($channel, $itunes_ns);

    // ═══ Парсим все выпуски из RSS ═══
    $rss_episodes = [];
    $items = $channel->item;
    $count = 0;

    foreach ($items as $item) {
        if ($limit > 0 && $count >= $limit) break;

        $itunes = $item->children($itunes_ns);
        $guid   = trim((string) $item->guid);
        $enc_url = (string) ($item->enclosure['url'] ?? '');

        // Уникальный идентификатор: guid → enclosure URL
        $uid = $guid ?: $enc_url;
        if (empty($uid)) continue;

        // Обложка выпуска — itunes:image
        $ep_image = '';
        if (isset($itunes->image)) {
            $ep_image = (string) ($itunes->image->attributes()['href'] ?? '');
        }
        if (!$ep_image) {
            // Fallback: regex из raw XML
            $raw = $item->asXML();
            if (preg_match('/itunes:image[^>]+href=["\']([^"\']+)["\']/i', $raw, $m)) {
                $ep_image = $m[1];
            }
        }

        $rss_episodes[$uid] = [
            'guid'        => $guid,
            'title'       => (string) $item->title,
            'description' => (string) ($itunes->summary ?: $item->description),
            'link'        => (string) $item->link,
            'pubDate'     => (string) $item->pubDate,
            'audio_url'   => $enc_url,
            'duration'    => (int) $itunes->duration,
            'image'       => $ep_image,
            'episode'     => (int) ($itunes->episode ?? 0),
            'season'      => (int) ($itunes->season ?? 0),
        ];
        $count++;
    }

    // ═══ Существующие выпуски в WP ═══
    $existing = get_posts([
        'post_type'      => 'podcast_episode',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'meta_key'       => '_gpp_uid',
    ]);

    $existing_map = []; // uid → post_id
    foreach ($existing as $post) {
        $uid = get_post_meta($post->ID, '_gpp_uid', true);
        if ($uid) $existing_map[$uid] = $post->ID;
    }

    $stats = ['created' => 0, 'updated' => 0, 'drafted' => 0];

    // ═══ Создание / обновление ═══
    foreach ($rss_episodes as $uid => $ep) {
        $content = gpp_process_description($ep['description']);

        if (isset($existing_map[$uid])) {
            // Обновляем существующий
            $post_id = $existing_map[$uid];
            $post = get_post($post_id);

            $updates = [];
            if ($post->post_title !== $ep['title'])   $updates['post_title'] = $ep['title'];
            if ($post->post_content !== $content)      $updates['post_content'] = $content;
            if ($post->post_status === 'draft')        $updates['post_status'] = 'publish';

            if (!empty($updates)) {
                $updates['ID'] = $post_id;
                wp_update_post($updates);
                $stats['updated']++;
            }

            // Обновляем мету
            update_post_meta($post_id, '_gpp_duration', $ep['duration']);
            update_post_meta($post_id, '_gpp_season', $ep['season']);
            update_post_meta($post_id, '_gpp_episode', $ep['episode']);

            // Обложка — обновляем если изменилась
            if ($ep['image']) {
                $cur_img = get_post_meta($post_id, '_gpp_image_url', true);
                if ($cur_img !== $ep['image']) {
                    gpp_set_episode_thumbnail($post_id, $ep['image'], $ep['title']);
                    update_post_meta($post_id, '_gpp_image_url', $ep['image']);
                }
            }

            // Аудио URL — обновляем если изменился
            if ($ep['audio_url']) {
                update_post_meta($post_id, '_gpp_audio_url', $ep['audio_url']);
            }

            // Таймкоды → мета (для архивной страницы)
            $tc = gpp_extract_timecodes($ep['description']);
            update_post_meta($post_id, '_gpp_timecodes', json_encode($tc, JSON_UNESCAPED_UNICODE));

            unset($existing_map[$uid]);
        } else {
            // Создаём новый
            $post_id = wp_insert_post([
                'post_type'    => 'podcast_episode',
                'post_title'   => $ep['title'],
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_date'    => date('Y-m-d H:i:s', strtotime($ep['pubDate'])),
            ]);

            if (!$post_id || is_wp_error($post_id)) continue;

            // Мета
            update_post_meta($post_id, '_gpp_uid', $uid);
            update_post_meta($post_id, '_gpp_guid', $ep['guid']);
            update_post_meta($post_id, '_gpp_audio_url', $ep['audio_url']);
            update_post_meta($post_id, '_gpp_duration', $ep['duration']);
            update_post_meta($post_id, '_gpp_season', $ep['season']);
            update_post_meta($post_id, '_gpp_episode', $ep['episode']);
            update_post_meta($post_id, '_gpp_image_url', $ep['image']);
            update_post_meta($post_id, '_gpp_link', $ep['link']);

            // Таймкоды → мета
            $tc = gpp_extract_timecodes($ep['description']);
            update_post_meta($post_id, '_gpp_timecodes', json_encode($tc, JSON_UNESCAPED_UNICODE));

            // Сезон → таксономия
            if ($ep['season']) {
                $term_name = 'Сезон ' . $ep['season'];
                wp_set_object_terms($post_id, $term_name, 'podcast_season');
            }

            // Обложка
            if ($ep['image']) {
                gpp_set_episode_thumbnail($post_id, $ep['image'], $ep['title']);
            }

            $stats['created']++;
        }
    }

    // ═══ Удалённые из RSS → черновик ═══
    foreach ($existing_map as $uid => $post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_status === 'publish') {
            wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            $stats['drafted']++;
        }
    }

    // Сохраняем время последней синхронизации
    update_option('gpp_last_sync', current_time('mysql'));
    update_option('gpp_last_sync_stats', $stats);

    return $stats;
}

/**
 * Страница «О подкасте» — создаётся один раз при первом запуске.
 */
function gpp_sync_about_page($channel, $itunes_ns) {
    if (get_option('gpp_about_page_id')) return;

    $itunes = $channel->children($itunes_ns);
    $title  = (string) $channel->title;
    $desc   = (string) ($itunes->summary ?: $channel->description);

    $page_id = wp_insert_post([
        'post_type'    => 'page',
        'post_title'   => 'О подкасте',
        'post_content' => wp_kses_post(nl2br($desc)),
        'post_status'  => 'publish',
    ]);

    if ($page_id && !is_wp_error($page_id)) {
        update_option('gpp_about_page_id', $page_id);

        // Обложка подкаста
        $cover = '';
        if (isset($itunes->image)) {
            $cover = (string) ($itunes->image->attributes()['href'] ?? '');
        }
        if (!$cover && isset($channel->image->url)) {
            $cover = (string) $channel->image->url;
        }
        if ($cover) {
            gpp_set_episode_thumbnail($page_id, $cover, $title);
        }
    }
}

/**
 * Обработка описания: таймкоды → ссылки, URL → <a>, email → mailto.
 */
function gpp_process_description($text) {
    $text = wp_strip_all_tags($text);

    // Таймкоды: "01:23 Текст" — только время кликабельно
    $text = preg_replace_callback(
        '/(\d{1,2}:\d{2}(?::\d{2})?)\s+(.+?)(?:\r?\n|\n|$)/u',
        function($m) {
            $parts = explode(':', $m[1]);
            $secs = count($parts) === 3
                ? $parts[0]*3600 + $parts[1]*60 + $parts[2]
                : $parts[0]*60 + $parts[1];
            $label = trim($m[2]);
            return '<span class="gpp-timecode"><a class="gpp-tc-time" href="#" data-time="' . $secs . '">'
                   . esc_html($m[1]) . '</a> '
                   . esc_html($label) . '</span>';
        },
        $text
    );

    // mailto
    $text = preg_replace(
        '/(mailto:)?([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i',
        '<a href="mailto:$2">$2</a>',
        $text
    );

    // https/http URLs (не ловим уже обёрнутые)
    $text = preg_replace(
        '/(?<!href=["\'])(?<!">)(https?:\/\/[^\s<\)]+)/i',
        '<a href="$1" target="_blank" rel="noopener">$1</a>',
        $text
    );

    // Убираем лишние пустые строки между таймкодами
    $text = preg_replace('/\n{2,}/', "\n", $text);
    $text = nl2br(trim($text));
    // Remove <br> between consecutive timecodes
    $text = preg_replace('/<\/span>\s*(<br\s*\/?>)\s*<span class="gpp-timecode">/i', '</span><span class="gpp-timecode">', $text);
    return $text;
}

/**
 * Извлечь таймкоды из текста описания в JSON-массив.
 */
function gpp_extract_timecodes($text) {
    $tc = [];
    $raw = wp_strip_all_tags($text);
    preg_match_all('/(\d{1,2}:\d{2}(?::\d{2})?)\s+(.+?)(?:\r?\n|\n|$)/u', $raw, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $parts = explode(':', $m[1]);
        $secs = count($parts) === 3
            ? $parts[0]*3600 + $parts[1]*60 + $parts[2]
            : $parts[0]*60 + $parts[1];
        $tc[] = ['t' => $secs, 'l' => trim($m[2])];
    }
    return $tc;
}

/**
 * Скачать изображение и поставить как миниатюру поста.
 */
function gpp_set_episode_thumbnail($post_id, $image_url, $title = '') {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url, 30);
    if (is_wp_error($tmp)) return false;

    $ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
    $file = [
        'name'     => sanitize_file_name($title ?: 'cover') . '.' . $ext,
        'tmp_name' => $tmp,
    ];

    $attach_id = media_handle_sideload($file, $post_id, $title);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        return false;
    }

    set_post_thumbnail($post_id, $attach_id);
    return $attach_id;
}

// ═══ AJAX: ручной запуск синхронизации из настроек ═══
add_action('wp_ajax_gpp_manual_sync', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('Нет прав');
    check_ajax_referer('gpp_sync_nonce', 'nonce');

    $limit = intval($_POST['limit'] ?? 0);
    $result = gpp_rss_sync($limit);
    wp_send_json_success($result);
});
