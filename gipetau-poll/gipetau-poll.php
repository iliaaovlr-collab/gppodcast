<?php
/**
 * Plugin Name: Гипетау Опросы
 * Description: Опросы для подкаста Гипетау
 * Version: 2.0
 */
defined('ABSPATH') || exit;

/* ═══════════════════════════════════════════
   1. ТИП ЗАПИСИ «ОПРОС»
   ═══════════════════════════════════════════ */

add_action('init', function () {
    register_post_type('gpp_poll', [
        'labels' => [
            'name'               => 'Опросы',
            'singular_name'      => 'Опрос',
            'add_new'            => 'Добавить опрос',
            'add_new_item'       => 'Новый опрос',
            'edit_item'          => 'Редактировать опрос',
            'all_items'          => 'Все опросы',
            'search_items'       => 'Найти опрос',
            'not_found'          => 'Опросов не найдено',
            'not_found_in_trash' => 'В корзине пусто',
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'supports'          => ['title'],
        'menu_icon'         => 'dashicons-chart-bar',
        'menu_position'     => 25,
    ]);
});

/* ═══════════════════════════════════════════
   2. КОЛОНКИ В СПИСКЕ ОПРОСОВ
   ═══════════════════════════════════════════ */

add_filter('manage_gpp_poll_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $val) {
        $new[$key] = $val;
        if ($key === 'title') {
            $new['poll_episode'] = 'Эпизод';
            $new['poll_end']     = 'Окончание';
            $new['poll_votes']   = 'Голосов';
        }
    }
    unset($new['date']);
    return $new;
});

add_action('manage_gpp_poll_posts_custom_column', function ($column, $post_id) {
    if ($column === 'poll_episode') {
        $uid = get_post_meta($post_id, '_gpp_poll_episode_uid', true);
        if ($uid) {
            $ep = gpp_poll_find_episode_by_uid($uid);
            echo $ep ? esc_html(get_the_title($ep)) : '<em>' . esc_html(mb_substr($uid, 0, 30)) . '…</em>';
        } else {
            echo '—';
        }
    }
    if ($column === 'poll_end') {
        $d = get_post_meta($post_id, '_gpp_poll_end_date', true);
        if ($d) {
            $ended = strtotime($d . ' 23:59:59') < time();
            echo esc_html(date_i18n('d.m.Y', strtotime($d)));
            if ($ended) echo ' <span style="color:#c0352a">✕</span>';
        } else {
            echo '—';
        }
    }
    if ($column === 'poll_votes') {
        $v  = get_post_meta($post_id, '_gpp_poll_votes', true);
        $tg = get_post_meta($post_id, '_gpp_poll_tg_votes', true);
        $site_total = is_array($v) ? array_sum($v) : 0;
        $tg_total   = is_array($tg) ? array_sum($tg) : 0;
        if ($site_total > 0 && $tg_total > 0) {
            echo $site_total . ' + ' . $tg_total . ' ТГ';
        } else {
            echo $site_total + $tg_total;
        }
    }
}, 10, 2);

/* ═══════════════════════════════════════════
   3. ПОИСК ЭПИЗОДА ПО UID
   ═══════════════════════════════════════════ */

function gpp_poll_find_episode_by_uid($uid) {
    if (!$uid) return null;
    $posts = get_posts([
        'post_type'      => 'podcast_episode',
        'post_status'    => ['publish', 'draft'],
        'meta_key'       => '_gpp_uid',
        'meta_value'     => $uid,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);
    return $posts ? $posts[0] : null;
}

/* ═══════════════════════════════════════════
   4. МЕТАБОКС
   ═══════════════════════════════════════════ */

add_action('add_meta_boxes', function () {
    add_meta_box('gpp_poll_settings', 'Настройки опроса', 'gpp_poll_metabox_render', 'gpp_poll', 'normal', 'high');
});

function gpp_poll_metabox_render($post) {
    wp_nonce_field('gpp_poll_save', 'gpp_poll_nonce');

    $options   = get_post_meta($post->ID, '_gpp_poll_options', true);
    if (!is_array($options) || empty($options)) $options = ['', ''];
    $ep_uid    = get_post_meta($post->ID, '_gpp_poll_episode_uid', true);
    $end_date  = get_post_meta($post->ID, '_gpp_poll_end_date', true);
    $votes     = get_post_meta($post->ID, '_gpp_poll_votes', true);
    if (!is_array($votes)) $votes = [];
    $tg_votes  = get_post_meta($post->ID, '_gpp_poll_tg_votes', true);
    if (!is_array($tg_votes)) $tg_votes = [];

    // Все эпизоды для выпадающего списка
    $episodes = get_posts([
        'post_type'      => 'podcast_episode',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    ?>
    <style>
    .gpp-pm-row{margin-bottom:16px}
    .gpp-pm-row>label{display:block;font-weight:600;margin-bottom:6px}
    .gpp-pm-opt{display:flex;gap:8px;margin-bottom:6px;align-items:center}
    .gpp-pm-opt input[type="text"]{flex:1}
    .gpp-pm-opt .button{color:#b32d2e}
    .gpp-pm-votes{display:flex;gap:6px;align-items:center;margin-bottom:6px}
    .gpp-pm-votes label{min-width:120px;font-weight:400;color:#666}
    .gpp-pm-votes input{width:70px}
    .gpp-pm-votes .gpp-pm-vlabel{font-size:11px;color:#888}
    #gpp_poll_end_date{width:180px}
    #gpp_poll_episode_uid{width:100%;max-width:500px}
    </style>

    <div class="gpp-pm-row">
        <label>Варианты ответа</label>
        <div id="gppPollOpts">
        <?php foreach ($options as $i => $opt): ?>
            <div class="gpp-pm-opt">
                <input type="text" name="gpp_poll_options[]"
                       value="<?php echo esc_attr($opt); ?>"
                       placeholder="Вариант <?php echo $i + 1; ?>">
                <button type="button" class="button gpp-pm-del" title="Удалить">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="gppPollAddOpt">+ Добавить вариант</button>
    </div>

    <div class="gpp-pm-row">
        <label for="gpp_poll_episode_uid">Эпизод</label>
        <select name="gpp_poll_episode_uid" id="gpp_poll_episode_uid">
            <option value="">— Выберите эпизод —</option>
            <?php foreach ($episodes as $ep):
                $uid = get_post_meta($ep->ID, '_gpp_uid', true);
                if (!$uid) continue;
                $s = get_post_meta($ep->ID, '_gpp_season', true);
                $e = get_post_meta($ep->ID, '_gpp_episode', true);
                $se = $s ? "S{$s}E{$e}" : "#{$e}";
            ?>
            <option value="<?php echo esc_attr($uid); ?>" <?php selected($ep_uid, $uid); ?>>
                <?php echo esc_html($se . ' — ' . $ep->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="gpp-pm-row">
        <label for="gpp_poll_end_date">Дата окончания голосования</label>
        <input type="date" name="gpp_poll_end_date" id="gpp_poll_end_date"
               value="<?php echo esc_attr($end_date); ?>">
    </div>

    <div class="gpp-pm-row">
        <label>Голоса (ручная установка)</label>
        <p style="margin:0 0 8px;color:#666;font-size:12px">
            Сайт — реальные голоса + ручная корректировка. ТГ — вбивать вручную из Телеграма.
        </p>
        <?php foreach ($options as $i => $opt):
            if (empty($opt)) continue;
        ?>
        <div class="gpp-pm-votes">
            <label><?php echo esc_html($opt); ?></label>
            <span class="gpp-pm-vlabel">Сайт:</span>
            <input type="number" name="gpp_poll_manual_votes[<?php echo $i; ?>]"
                   value="<?php echo intval($votes[$i] ?? 0); ?>" min="0">
            <span class="gpp-pm-vlabel">ТГ:</span>
            <input type="number" name="gpp_poll_tg_votes[<?php echo $i; ?>]"
                   value="<?php echo intval($tg_votes[$i] ?? 0); ?>" min="0">
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    document.getElementById('gppPollAddOpt').onclick = function(){
        var n = document.querySelectorAll('#gppPollOpts .gpp-pm-opt').length + 1;
        var d = document.createElement('div');
        d.className = 'gpp-pm-opt';
        d.innerHTML = '<input type="text" name="gpp_poll_options[]" placeholder="Вариант '+n+'"> <button type="button" class="button gpp-pm-del" title="Удалить">✕</button>';
        document.getElementById('gppPollOpts').appendChild(d);
    };
    document.getElementById('gppPollOpts').addEventListener('click', function(e){
        if (!e.target.classList.contains('gpp-pm-del')) return;
        if (document.querySelectorAll('#gppPollOpts .gpp-pm-opt').length > 2)
            e.target.closest('.gpp-pm-opt').remove();
    });
    </script>
    <?php
}

/* ═══════════════════════════════════════════
   5. СОХРАНЕНИЕ
   ═══════════════════════════════════════════ */

add_action('save_post_gpp_poll', function ($post_id) {
    if (!isset($_POST['gpp_poll_nonce'])) return;
    if (!wp_verify_nonce($_POST['gpp_poll_nonce'], 'gpp_poll_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $opts = array_values(array_filter(
        array_map('sanitize_text_field', $_POST['gpp_poll_options'] ?? [])
    ));
    update_post_meta($post_id, '_gpp_poll_options',      $opts);
    update_post_meta($post_id, '_gpp_poll_episode_uid',  sanitize_text_field($_POST['gpp_poll_episode_uid'] ?? ''));
    update_post_meta($post_id, '_gpp_poll_end_date',     sanitize_text_field($_POST['gpp_poll_end_date'] ?? ''));

    // Ручные голоса сайт
    $manual = $_POST['gpp_poll_manual_votes'] ?? [];
    $clean_votes = [];
    foreach ($manual as $i => $v) $clean_votes[intval($i)] = max(0, intval($v));
    update_post_meta($post_id, '_gpp_poll_votes', $clean_votes);

    // Голоса ТГ
    $tg = $_POST['gpp_poll_tg_votes'] ?? [];
    $clean_tg = [];
    foreach ($tg as $i => $v) $clean_tg[intval($i)] = max(0, intval($v));
    update_post_meta($post_id, '_gpp_poll_tg_votes', $clean_tg);
});

/* ═══════════════════════════════════════════
   6. ИДЕНТИФИКАЦИЯ ГОЛОСУЮЩЕГО
   ═══════════════════════════════════════════ */

function gpp_poll_voter_hash() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return md5($ip . '|' . $ua);
}

function gpp_poll_ip_hash() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return md5('ip|' . $ip);
}

function gpp_poll_get_user_vote($poll_id) {
    // 1. Cookie — быстрая проверка
    if (isset($_COOKIE['gpp_voted_' . $poll_id])) {
        return intval($_COOKIE['gpp_voted_' . $poll_id]);
    }
    // 2. IP+UA — точная проверка
    $hash = gpp_poll_voter_hash();
    $vote = get_post_meta($poll_id, '_gpp_voter_' . $hash, true);
    if ($vote !== '' && $vote !== false) {
        return intval($vote);
    }
    // 3. Только IP — от смены браузера
    $ip_hash = gpp_poll_ip_hash();
    $vote = get_post_meta($poll_id, '_gpp_voter_' . $ip_hash, true);
    if ($vote !== '' && $vote !== false) {
        return intval($vote);
    }
    return false;
}

/* ═══════════════════════════════════════════
   7. AJAX-ГОЛОСОВАНИЕ
   ═══════════════════════════════════════════ */

add_action('wp_ajax_gpp_poll_vote',        'gpp_poll_vote_ajax');
add_action('wp_ajax_nopriv_gpp_poll_vote', 'gpp_poll_vote_ajax');

function gpp_poll_vote_ajax() {
    $poll_id = intval($_POST['poll_id'] ?? 0);
    $option  = intval($_POST['option']  ?? -1);

    if (!$poll_id || $option < 0)                    wp_send_json_error('Неверные данные');
    if (get_post_type($poll_id) !== 'gpp_poll')      wp_send_json_error('Неверный опрос');

    $end = get_post_meta($poll_id, '_gpp_poll_end_date', true);
    if ($end && strtotime($end . ' 23:59:59') < time()) wp_send_json_error('Голосование завершено');

    if (gpp_poll_get_user_vote($poll_id) !== false)  wp_send_json_error('Вы уже голосовали');

    $options = get_post_meta($poll_id, '_gpp_poll_options', true);
    if (!is_array($options) || !isset($options[$option])) wp_send_json_error('Неверный вариант');

    // Сохраняем голос — по IP+UA и по чистому IP
    $hash = gpp_poll_voter_hash();
    update_post_meta($poll_id, '_gpp_voter_' . $hash, $option);
    $ip_hash = gpp_poll_ip_hash();
    update_post_meta($poll_id, '_gpp_voter_' . $ip_hash, $option);

    // Обновляем счётчики
    $votes = get_post_meta($poll_id, '_gpp_poll_votes', true);
    if (!is_array($votes)) $votes = [];
    $votes[$option] = ($votes[$option] ?? 0) + 1;
    update_post_meta($poll_id, '_gpp_poll_votes', $votes);

    // Cookie 90 дней
    setcookie('gpp_voted_' . $poll_id, $option, time() + 90 * 86400, '/');

    wp_send_json_success(['voted' => $option]);
}

/* ═══════════════════════════════════════════
   8. ФРОНТЕНД: ПОКАЗ ОПРОСА
   ═══════════════════════════════════════════ */

add_action('gpp_after_player', 'gpp_poll_display');

function gpp_poll_display($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $ep_uid = get_post_meta($post_id, '_gpp_uid', true);
    if (!$ep_uid) return;

    $polls = get_posts([
        'post_type'      => 'gpp_poll',
        'post_status'    => 'publish',
        'meta_query'     => [['key' => '_gpp_poll_episode_uid', 'value' => $ep_uid]],
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    if (!$polls) return;

    foreach ($polls as $poll) {
        gpp_poll_render_card($poll);
    }
}

function gpp_poll_word($n) {
    $m = $n % 10; $m100 = $n % 100;
    if ($m === 1 && $m100 !== 11) return 'голос';
    if ($m >= 2 && $m <= 4 && ($m100 < 10 || $m100 >= 20)) return 'голоса';
    return 'голосов';
}

function gpp_poll_ru_date($date) {
    $months = ['','января','февраля','марта','апреля','мая','июня',
               'июля','августа','сентября','октября','ноября','декабря'];
    $d = intval(date('j', strtotime($date)));
    $m = $months[intval(date('n', strtotime($date)))];
    return $d . '&nbsp;' . $m;
}

// Умная дата: «5 минут назад», «3 дня назад», «01.04.2026»
function gpp_poll_smart_date($timestamp) {
    $now  = current_time('timestamp');
    $diff = $now - $timestamp;

    if ($diff < 60) return 'только что';

    if ($diff < 3600) {
        $m = floor($diff / 60);
        $w = gpp_poll_decline($m, 'минуту', 'минуты', 'минут');
        return $m . ' ' . $w . ' назад';
    }

    if ($diff < 86400) {
        $h = floor($diff / 3600);
        $w = gpp_poll_decline($h, 'час', 'часа', 'часов');
        return $h . ' ' . $w . ' назад';
    }

    if ($diff < 14 * 86400) {
        $d = floor($diff / 86400);
        $w = gpp_poll_decline($d, 'день', 'дня', 'дней');
        return $d . ' ' . $w . ' назад';
    }

    return date('d.m.Y', $timestamp);
}

function gpp_poll_decline($n, $one, $few, $many) {
    $m = $n % 10;
    $m100 = $n % 100;
    if ($m === 1 && $m100 !== 11) return $one;
    if ($m >= 2 && $m <= 4 && ($m100 < 10 || $m100 >= 20)) return $few;
    return $many;
}

/* ═══════════════════════════════════════════
   9. АКТУАЛЬНЫЙ ОПРОС НА СТРАНИЦЕ АРХИВА
   ═══════════════════════════════════════════ */

add_action('gpp_archive_top', 'gpp_poll_archive_top');

function gpp_poll_archive_top() {
    // Ищем один активный (не завершённый) опрос, самый свежий
    $polls = get_posts([
        'post_type'      => 'gpp_poll',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => '_gpp_poll_end_date', 'value' => '', 'compare' => '='],
            ['key' => '_gpp_poll_end_date', 'compare' => 'NOT EXISTS'],
            ['key' => '_gpp_poll_end_date', 'value' => date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'],
        ],
    ]);
    if (!$polls) return;

    gpp_poll_render_card($polls[0]);
}

/* ═══════════════════════════════════════════
   10. ШОРТКОД [gpp_polls] — ВСЕ ОПРОСЫ
   Создайте страницу в WP и вставьте [gpp_polls]
   ═══════════════════════════════════════════ */

add_shortcode('gpp_polls', 'gpp_polls_shortcode');

function gpp_polls_shortcode($atts = []) {
    $polls = get_posts([
        'post_type'      => 'gpp_poll',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    if (!$polls) return '<p style="text-align:center;color:var(--gp-textmuted)">Опросов пока нет.</p>';

    // Разделяем на активные и завершённые
    $active = $ended = [];
    foreach ($polls as $poll) {
        $end = get_post_meta($poll->ID, '_gpp_poll_end_date', true);
        if ($end && strtotime($end . ' 23:59:59') < time()) {
            $ended[] = $poll;
        } else {
            $active[] = $poll;
        }
    }

    ob_start();
    if ($active) {
        echo '<div class="gpp-polls-list">';
        foreach ($active as $poll) {
            gpp_poll_render_row($poll);
        }
        echo '</div>';
    }
    if ($ended) {
        echo '<h3 class="gpp-polls-section">Завершённые</h3>';
        echo '<div class="gpp-polls-list">';
        foreach ($ended as $poll) {
            gpp_poll_render_row($poll);
        }
        echo '</div>';
    }
    return ob_get_clean();
}

/* ═══════════════════════════════════════════
   11. РЕНДЕР: КАРТОЧКА (для эпизода / архива)
   ═══════════════════════════════════════════ */

function gpp_poll_render_card($poll) {
    $options = get_post_meta($poll->ID, '_gpp_poll_options', true);
    if (!is_array($options) || empty($options)) return;

    $end_date  = get_post_meta($poll->ID, '_gpp_poll_end_date', true);
    $ended     = $end_date && strtotime($end_date . ' 23:59:59') < time();
    $user_vote = gpp_poll_get_user_vote($poll->ID);

    $votes     = get_post_meta($poll->ID, '_gpp_poll_votes', true);
    if (!is_array($votes)) $votes = [];
    $tg_votes  = get_post_meta($poll->ID, '_gpp_poll_tg_votes', true);
    if (!is_array($tg_votes)) $tg_votes = [];

    $combined = [];
    foreach ($options as $i => $opt) {
        $combined[$i] = ($votes[$i] ?? 0) + ($tg_votes[$i] ?? 0);
    }
    $site_total = array_sum($votes);
    $tg_total   = array_sum($tg_votes);
    $total      = $site_total + $tg_total;
    $max_votes  = $total > 0 ? max($combined) : 0;

    echo '<div class="gpp-poll" data-poll-id="' . $poll->ID . '">';
    echo '<h3 class="gpp-poll-q">' . esc_html($poll->post_title) . '</h3>';
    echo '<div class="gpp-poll-opts">';
    foreach ($options as $i => $opt) {
        $count     = $combined[$i];
        $pct       = $total > 0 ? round($count / $total * 100) : 0;
        $is_winner = $total > 0 && $count === $max_votes;
        $is_user   = ($user_vote !== false && $user_vote === $i);

        if ($ended) {
            $cls = 'gpp-po gpp-po--res';
            if ($is_winner) $cls .= ' gpp-po--win';
            if ($is_user)   $cls .= ' gpp-po--my';
            echo '<div class="' . $cls . '" style="--pct:' . $pct . '%">';
            echo '<span class="gpp-po-text">' . esc_html($opt) . '</span>';
            echo '<span class="gpp-po-pct">' . $pct . '%</span>';
            echo '</div>';
        } elseif ($user_vote !== false) {
            $cls = 'gpp-po gpp-po--done';
            if ($is_user) $cls .= ' gpp-po--sel';
            echo '<div class="' . $cls . '">';
            echo '<span class="gpp-po-text">' . esc_html($opt) . '</span>';
            if ($is_user) echo '<svg class="gpp-po-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
            echo '</div>';
        } else {
            echo '<button class="gpp-po" data-option="' . $i . '">';
            echo '<span class="gpp-po-text">' . esc_html($opt) . '</span>';
            echo '</button>';
        }
    }
    echo '</div>';

    echo '<div class="gpp-poll-ft">';
    if ($ended) {
        echo '<span>Голосование завершено</span>';
        if ($total > 0) {
            if ($site_total > 0 && $tg_total > 0) {
                echo '<span class="gpp-poll-ft-sep">&middot;</span>';
                echo '<span>' . $site_total . '&nbsp;сайт + ' . $tg_total . '&nbsp;ТГ = ' . $total . '</span>';
            } else {
                echo '<span class="gpp-poll-ft-sep">&middot;</span>';
                echo '<span>' . $total . ' ' . gpp_poll_word($total) . '</span>';
            }
        }
    } else {
        if ($end_date) echo '<span>до ' . gpp_poll_ru_date($end_date) . '</span>';
        if ($user_vote !== false) {
            echo '<span class="gpp-poll-ft-sep">&middot;</span>';
            echo '<span>ваш голос принят</span>';
        }
    }
    echo '</div>';

    echo '</div>';
}

/* ═══════════════════════════════════════════
   11b. РЕНДЕР: КОМПАКТНАЯ СТРОКА (для [gpp_polls])
   ═══════════════════════════════════════════ */

function gpp_poll_render_row($poll) {
    $options = get_post_meta($poll->ID, '_gpp_poll_options', true);
    if (!is_array($options) || empty($options)) return;

    $end_date  = get_post_meta($poll->ID, '_gpp_poll_end_date', true);
    $ended     = $end_date && strtotime($end_date . ' 23:59:59') < time();
    $user_vote = gpp_poll_get_user_vote($poll->ID);

    $votes     = get_post_meta($poll->ID, '_gpp_poll_votes', true);
    if (!is_array($votes)) $votes = [];
    $tg_votes  = get_post_meta($poll->ID, '_gpp_poll_tg_votes', true);
    if (!is_array($tg_votes)) $tg_votes = [];

    $combined = [];
    foreach ($options as $i => $opt) {
        $combined[$i] = ($votes[$i] ?? 0) + ($tg_votes[$i] ?? 0);
    }
    $total     = array_sum($combined);
    $max_votes = $total > 0 ? max($combined) : 0;

    echo '<div class="gpp-pr' . ($ended ? ' gpp-pr--ended' : '') . '" data-poll-id="' . $poll->ID . '">';

    // Дата
    $post_time = get_post_time('U', false, $poll->ID);
    echo '<span class="gpp-pr-date">' . esc_html(gpp_poll_smart_date($post_time)) . '</span>';

    // Вопрос
    echo '<span class="gpp-pr-q">' . esc_html($poll->post_title) . '</span>';

    if ($ended) {
        // Завершён: результаты текстом — «Да 62% / Нет 38%»
        $parts = [];
        foreach ($options as $i => $opt) {
            $pct       = $total > 0 ? round($combined[$i] / $total * 100) : 0;
            $is_winner = $total > 0 && $combined[$i] === $max_votes;
            $cls = $is_winner ? 'gpp-pr-res gpp-pr-res--win' : 'gpp-pr-res';
            $parts[] = '<span class="' . $cls . '">' . esc_html($opt) . '&nbsp;' . $pct . '%</span>';
        }
        echo '<span class="gpp-pr-results">' . implode('<span class="gpp-pr-sep">/</span>', $parts) . '</span>';
    } elseif ($user_vote !== false) {
        // Уже проголосовал
        echo '<span class="gpp-pr-opts">';
        foreach ($options as $i => $opt) {
            $cls = 'gpp-pr-o gpp-pr-o--done';
            if ($user_vote === $i) $cls .= ' gpp-pr-o--sel';
            echo '<span class="' . $cls . '">' . esc_html($opt);
            if ($user_vote === $i) echo ' ✓';
            echo '</span>';
        }
        echo '</span>';
        echo '<span class="gpp-pr-st">голос принят</span>';
    } else {
        // Активное голосование — кнопки-пилюли
        echo '<span class="gpp-pr-opts">';
        foreach ($options as $i => $opt) {
            echo '<button class="gpp-pr-o" data-option="' . $i . '">' . esc_html($opt) . '</button>';
        }
        echo '</span>';
        if ($end_date) {
            echo '<span class="gpp-pr-st">до ' . gpp_poll_ru_date($end_date) . '</span>';
        }
    }

    echo '</div>';
}

/* ═══════════════════════════════════════════
   12. CSS — флаг для вывода
   ═══════════════════════════════════════════ */

// CSS/JS нужен на: эпизодах, архиве, и страницах с шорткодом
add_action('wp_head',   'gpp_poll_css');
add_action('wp_footer', 'gpp_poll_js');

function gpp_poll_need_assets() {
    if (is_singular('podcast_episode')) return true;
    if (is_post_type_archive('podcast_episode')) return true;
    if (is_tax('podcast_season')) return true;
    // Шорткод на любой странице
    global $post;
    if ($post && has_shortcode($post->post_content, 'gpp_polls')) return true;
    return false;
}

function gpp_poll_css() {
    if (!gpp_poll_need_assets()) return;
    ?>
    <style>
    /* Карточка */
    .gpp-poll{
      margin:28px 0;padding:24px 28px;text-align:center;
      background:var(--gp-surface);
      border:1px solid var(--gp-border);border-radius:6px;
      box-shadow:0 2px 12px rgba(0,0,0,.15);
    }

    /* Вопрос */
    .gpp-poll-q{
      font-family:var(--gp-font-heading);
      font-size:var(--gp-fs-h3);font-style:var(--gp-h-style);font-weight:var(--gp-h-weight);
      color:var(--gp-text);margin:0 0 20px;line-height:var(--gp-lh-heading);
    }

    /* Контейнер вариантов */
    .gpp-poll-opts{
      display:flex;flex-wrap:wrap;gap:10px;
      justify-content:center;
    }

    /* Вариант — базовый */
    .gpp-po{
      position:relative;overflow:hidden;
      padding:10px 22px;border:1px solid var(--gp-border2);border-radius:24px;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);
      color:var(--gp-text2);background:transparent;
      cursor:pointer;transition:all .2s;user-select:none;
      display:inline-flex;align-items:center;gap:7px;line-height:1.3;
    }

    /* Ховер при голосовании */
    button.gpp-po{
      transition:all .2s,transform .15s;
    }
    button.gpp-po:hover{
      border-color:var(--gp-accent);color:var(--gp-accent);
      background:var(--gp-accentbg);
      transform:translateY(-1px);
      box-shadow:0 4px 12px var(--gp-accentbg);
    }
    button.gpp-po:active{transform:scale(.97)}
    button.gpp-po:disabled{opacity:.5;pointer-events:none}

    /* Уже проголосовал (опрос активен) */
    .gpp-po--done{cursor:default}
    .gpp-po--sel{
      border-color:var(--gp-accent);background:var(--gp-accentbg);color:var(--gp-accent);
    }
    .gpp-po-check{flex-shrink:0}

    /* Результаты */
    .gpp-po--res{cursor:default}
    .gpp-po--res::before{
      content:'';position:absolute;left:0;top:0;bottom:0;
      width:var(--pct);background:var(--gp-accentbg);
      border-radius:24px;z-index:0;
      transition:width 1s cubic-bezier(.4,0,.2,1);
    }
    .gpp-po--res .gpp-po-text,
    .gpp-po--res .gpp-po-pct{position:relative;z-index:1}
    .gpp-po-pct{
      font-weight:700;font-size:var(--gp-fs-xs);
      color:var(--gp-textmuted);margin-left:2px;
    }

    /* Победитель */
    .gpp-po--win{border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-po--win::before{background:var(--gp-accentdim)}
    .gpp-po--win .gpp-po-pct{color:var(--gp-accent);font-size:var(--gp-fs-small)}

    /* Мой голос (в результатах) */
    .gpp-po--my{box-shadow:0 0 0 2px var(--gp-accent)}

    /* Подвал */
    .gpp-poll-ft{
      margin-top:16px;
      display:flex;align-items:center;justify-content:center;gap:0;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      color:var(--gp-textmuted);text-transform:uppercase;letter-spacing:.05em;
    }
    .gpp-poll-ft-sep{margin:0 8px;opacity:.4}

    /* Мобилка (карточка) */
    @media(max-width:768px){
      .gpp-poll{padding:18px 16px}
      .gpp-poll-opts{gap:8px}
      .gpp-po{padding:8px 16px;font-size:13px}
    }

    /* ═══ Компактный список опросов [gpp_polls] ═══ */

    /* Заголовок секции */
    .gpp-polls-section{
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      text-transform:uppercase;letter-spacing:.15em;
      color:var(--gp-textmuted);margin:28px 0 0;
    }

    .gpp-polls-list{
      display:flex;flex-direction:column;gap:0;
    }
    .gpp-pr{
      display:flex;flex-wrap:wrap;align-items:center;gap:8px;
      padding:12px 0;
      border-bottom:1px solid var(--gp-border);
    }
    .gpp-polls-list:first-child .gpp-pr:first-child{
      border-top:1px solid var(--gp-border);
    }
    .gpp-pr--ended{opacity:.7}

    .gpp-pr-date{
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      color:var(--gp-textmuted);white-space:nowrap;flex-shrink:0;
      min-width:90px;
    }

    .gpp-pr-q{
      font-family:var(--gp-font-body);font-size:var(--gp-fs-body);
      color:var(--gp-text);font-weight:500;
      margin-right:auto;
    }

    /* Кнопки-пилюли (активное голосование) */
    .gpp-pr-opts{
      display:inline-flex;flex-wrap:wrap;gap:6px;align-items:center;
      flex-shrink:0;
    }
    .gpp-pr-o{
      padding:4px 14px;border:1px solid var(--gp-border2);border-radius:16px;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      color:var(--gp-text2);background:transparent;
      cursor:pointer;transition:all .15s;white-space:nowrap;
    }
    button.gpp-pr-o:hover{
      border-color:var(--gp-accent);color:var(--gp-accent);background:var(--gp-accentbg);
    }
    button.gpp-pr-o:disabled{opacity:.5;pointer-events:none}
    .gpp-pr-o--done{cursor:default;border-color:var(--gp-border2)}
    .gpp-pr-o--sel{border-color:var(--gp-accent);color:var(--gp-accent)}

    /* Результаты завершённых (текст, не пилюли) */
    .gpp-pr-results{
      display:inline-flex;flex-wrap:wrap;align-items:center;gap:0;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      color:var(--gp-textmuted);white-space:nowrap;flex-shrink:0;
    }
    .gpp-pr-res{white-space:nowrap}
    .gpp-pr-res--win{color:var(--gp-accent);font-weight:600}
    .gpp-pr-sep{margin:0 6px;opacity:.35}

    /* Статус */
    .gpp-pr-st{
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      color:var(--gp-textmuted);text-transform:uppercase;letter-spacing:.05em;
      white-space:nowrap;flex-shrink:0;
    }

    @media(max-width:768px){
      .gpp-pr{gap:6px}
      .gpp-pr-q{width:100%;margin-right:0}
      .gpp-pr-o{padding:4px 10px;font-size:12px}
    }
    </style>
    <?php
}

/* ═══════════════════════════════════════════
   13. JS (ГОЛОСОВАНИЕ)
   ═══════════════════════════════════════════ */

function gpp_poll_js() {
    if (!gpp_poll_need_assets()) return;
    ?>
    <script>
    (function(){
      document.querySelectorAll('.gpp-poll').forEach(function(poll){
        poll.addEventListener('click', function(e){
          var btn = e.target.closest('button.gpp-po');
          if (!btn) return;
          btn.disabled = true;

          var fd = new FormData();
          fd.append('action', 'gpp_poll_vote');
          fd.append('poll_id', poll.dataset.pollId);
          fd.append('option', btn.dataset.option);

          fetch('<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body:fd})
            .then(function(r){ return r.json(); })
            .then(function(resp){
              if (resp.success) {
                poll.querySelectorAll('button.gpp-po').forEach(function(b){
                  var div = document.createElement('div');
                  div.className = 'gpp-po gpp-po--done';
                  if (b.dataset.option === String(resp.data.voted)) {
                    div.classList.add('gpp-po--sel');
                    div.innerHTML = b.innerHTML + '<svg class="gpp-po-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
                  } else {
                    div.innerHTML = b.innerHTML;
                  }
                  b.replaceWith(div);
                });
                var ft = poll.querySelector('.gpp-poll-ft');
                if (ft && !ft.textContent.includes('голос')) {
                  ft.innerHTML += '<span class="gpp-poll-ft-sep">&middot;</span><span>ваш голос принят</span>';
                }
              } else {
                alert(resp.data || 'Ошибка');
                btn.disabled = false;
              }
            })
            .catch(function(){ alert('Ошибка сети'); btn.disabled = false; });
        });
      });
    })();

    // Компактный список [gpp_polls]
    document.querySelectorAll('.gpp-pr').forEach(function(row){
      row.addEventListener('click', function(e){
        var btn = e.target.closest('button.gpp-pr-o');
        if (!btn) return;
        btn.disabled = true;

        var fd = new FormData();
        fd.append('action', 'gpp_poll_vote');
        fd.append('poll_id', row.dataset.pollId);
        fd.append('option', btn.dataset.option);

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body:fd})
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (resp.success) {
              row.querySelectorAll('button.gpp-pr-o').forEach(function(b){
                var s = document.createElement('span');
                s.className = 'gpp-pr-o gpp-pr-o--done';
                if (b.dataset.option === String(resp.data.voted)) {
                  s.classList.add('gpp-pr-o--sel');
                  s.innerHTML = b.textContent + ' ✓';
                } else {
                  s.textContent = b.textContent;
                }
                b.replaceWith(s);
              });
              var st = row.querySelector('.gpp-pr-st');
              if (!st) {
                st = document.createElement('span');
                st.className = 'gpp-pr-st';
                row.appendChild(st);
              }
              st.textContent = 'голос принят';
            } else {
              alert(resp.data || 'Ошибка');
              btn.disabled = false;
            }
          })
          .catch(function(){ alert('Ошибка сети'); btn.disabled = false; });
      });
    });
    </script>
    <?php
}
