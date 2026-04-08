<?php
/**
 * SEO: OG tags, sharing buttons, auto-excerpt, keywords support.
 * oEmbed player for social sharing.
 */
defined('ABSPATH') || exit;

// ═══ Add keyword (tag) support to podcast_episode ═══
add_action('init', function() {
    register_taxonomy_for_object_type('post_tag', 'podcast_episode');
});

// ═══ Auto-generate excerpt from description (first non-timecode paragraph) ═══
add_filter('wp_insert_post_data', function($data, $postarr) {
    if ($data['post_type'] !== 'podcast_episode') return $data;
    if (!empty($data['post_excerpt'])) return $data;
    $content = wp_strip_all_tags($data['post_content']);
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if (preg_match('/^\d{1,2}:\d{2}/', $line)) continue; // skip timecodes
        $data['post_excerpt'] = mb_substr($line, 0, 300);
        break;
    }
    return $data;
}, 10, 2);

// ═══ Open Graph meta tags ═══
add_action('wp_head', function() {
    if (!is_singular('podcast_episode')) return;
    $id = get_the_ID();
    $title = get_the_title($id);
    $desc = get_the_excerpt($id) ?: mb_substr(wp_strip_all_tags(get_the_content()), 0, 200);
    $cover = gpp_episode_cover($id, 'large');
    $url = get_permalink($id);
    $audio = get_post_meta($id, '_gpp_audio_url', true);
    $s = get_post_meta($id, '_gpp_season', true);
    $e = get_post_meta($id, '_gpp_episode', true);
    $site = get_bloginfo('name');
    ?>
    <!-- OG -->
    <meta property="og:type" content="music.song">
    <meta property="og:title" content="<?php echo esc_attr($title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($desc); ?>">
    <meta property="og:image" content="<?php echo esc_url($cover); ?>">
    <meta property="og:image:width" content="1400">
    <meta property="og:image:height" content="1400">
    <meta property="og:url" content="<?php echo esc_url($url); ?>">
    <meta property="og:site_name" content="<?php echo esc_attr($site); ?>">
    <meta property="og:audio" content="<?php echo esc_url($audio); ?>">
    <meta property="og:audio:type" content="audio/mpeg">
    <!-- Twitter/VK player card -->
    <meta name="twitter:card" content="player">
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($desc); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($cover); ?>">
    <meta name="twitter:player" content="<?php echo esc_url($url . '?embed=1'); ?>">
    <meta name="twitter:player:width" content="480">
    <meta name="twitter:player:height" content="120">
    <meta name="twitter:player:stream" content="<?php echo esc_url($audio); ?>">
    <meta name="twitter:player:stream:content_type" content="audio/mpeg">
    <!-- Telegram audio -->
    <meta property="og:audio:secure_url" content="<?php echo esc_url($audio); ?>">
    <?php
    // Keywords from tags
    $tags = get_the_tags($id);
    if ($tags): ?>
    <meta name="keywords" content="<?php echo esc_attr(implode(', ', wp_list_pluck($tags, 'name'))); ?>">
    <?php endif; ?>
    <?php
}, 1);

// ═══ Embed player (for social sharing) ═══
add_action('template_redirect', function() {
    if (!isset($_GET['embed']) || !is_singular('podcast_episode')) return;
    $id = get_the_ID();
    $audio = get_post_meta($id, '_gpp_audio_url', true);
    $title = get_the_title($id);
    $cover = gpp_episode_cover($id, 'thumbnail');
    $s = get_post_meta($id, '_gpp_season', true);
    $e = get_post_meta($id, '_gpp_episode', true);
    $se = $s ? "S{$s}E{$e}" : "#{$e}";
    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html><html><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:#111;color:#f2ede4;font-family:system-ui;display:flex;align-items:center;padding:12px;gap:12px;height:120px}
    img{width:80px;height:80px;border-radius:4px;object-fit:cover;flex-shrink:0}
    .info{flex:1;min-width:0}
    .se{font-size:11px;color:#8c857c;margin-bottom:2px}
    .t{font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    audio{width:100%;margin-top:8px}
    </style></head><body>
    <img src="<?php echo esc_url($cover);?>">
    <div class="info">
      <div class="se"><?php echo esc_html($se);?></div>
      <div class="t"><?php echo esc_html($title);?></div>
      <audio controls preload="none" src="<?php echo esc_url($audio);?>"></audio>
    </div>
    </body></html><?php
    exit;
});

// ═══ oEmbed discovery ═══
add_action('wp_head', function() {
    if (!is_singular('podcast_episode')) return;
    $url = get_permalink();
    echo '<link rel="alternate" type="application/json+oembed" href="' . esc_url(home_url('/wp-json/gpp/v1/oembed?url=' . urlencode($url))) . '">' . "\n";
});

add_action('rest_api_init', function() {
    register_rest_route('gpp/v1', '/oembed', [
        'methods' => 'GET',
        'callback' => function($request) {
            $url = $request->get_param('url');
            $post_id = url_to_postid($url);
            if (!$post_id || get_post_type($post_id) !== 'podcast_episode') return new WP_Error('not_found', '', ['status' => 404]);
            $title = get_the_title($post_id);
            return [
                'version' => '1.0',
                'type' => 'rich',
                'title' => $title,
                'author_name' => get_bloginfo('name'),
                'author_url' => home_url(),
                'provider_name' => get_bloginfo('name'),
                'provider_url' => home_url(),
                'html' => '<iframe src="' . esc_url($url . '?embed=1') . '" width="480" height="120" frameborder="0" allowfullscreen></iframe>',
                'width' => 480,
                'height' => 120,
                'thumbnail_url' => gpp_episode_cover($post_id, 'large'),
                'thumbnail_width' => 1400,
                'thumbnail_height' => 1400,
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});

// ═══ Share buttons ═══
// Share buttons — in right panel (after cover) + mobile
add_action('gp_right_panel', function() {
    if (!is_singular('podcast_episode')) return;
    $post_id = get_the_ID();
    $url = urlencode(get_permalink($post_id));
    $title = urlencode(get_the_title($post_id));
    ?>
    <div class="gpp-share">
      <span class="gpp-share-label">Поделиться:</span>
      <a href="https://t.me/share/url?url=<?php echo $url;?>&text=<?php echo $title;?>" target="_blank" rel="noopener" class="gpp-share-btn" title="Telegram">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 1 0 24 12.056A12.01 12.01 0 0 0 11.944 0Zm5.654 8.22-1.96 9.22c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.566-4.458c.537-.194 1.006.13.826.998Z"/></svg>
      </a>
      <a href="https://vk.com/share.php?url=<?php echo $url;?>&title=<?php echo $title;?>" target="_blank" rel="noopener" class="gpp-share-btn" title="ВКонтакте">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.77 19.15h1.33s.4-.04.6-.26c.19-.2.18-.56.18-.56s-.03-1.71.77-1.96c.78-.25 1.79 1.66 2.86 2.39.81.55 1.42.43 1.42.43l2.86-.04s1.5-.09.79-1.27c-.06-.1-.42-.88-2.15-2.48-1.82-1.68-1.57-1.41.61-4.32 1.33-1.77 1.86-2.85 1.7-3.31-.16-.44-1.13-.32-1.13-.32l-3.22.02s-.24-.03-.42.07c-.17.1-.28.35-.28.35s-.51 1.35-1.18 2.5c-1.43 2.43-2 2.56-2.23 2.41-.55-.36-.41-1.43-.41-2.2 0-2.38.36-3.38-.71-3.64-.36-.09-.62-.14-1.53-.15-.16 0-1.17 0-1.17 0s-.69.02-1.04.36c-.31.3 0 .36 0 .36s.94.18 1.28 1.64c.11.45.05 2.88.05 2.88s-.64 2.38-1.98-.79A21.7 21.7 0 016.3 7.8s-.17-.41-.47-.63c-.37-.27-.89-.36-.89-.36L1.86 6.84s-.55.02-.75.26c-.18.21 0 .65 0 .65s2.37 5.55 5.05 8.35c2.46 2.57 5.25 2.4 5.25 2.4h1.37z"/></svg>
      </a>
      <a href="https://connect.ok.ru/offer?url=<?php echo $url;?>" target="_blank" rel="noopener" class="gpp-share-btn" title="Одноклассники">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm4.47 8.38a7.06 7.06 0 0 1-3.22 1.14l2.88 2.87a1.25 1.25 0 1 1-1.77 1.77L12 16.3l-2.36 2.36a1.25 1.25 0 1 1-1.77-1.77l2.88-2.87a7.06 7.06 0 0 1-3.22-1.14 1.25 1.25 0 1 1 1.4-2.08 4.56 4.56 0 0 0 5.14 0 1.25 1.25 0 1 1 1.4 2.08z"/></svg>
      </a>
    </div>
    <?php
}, 15); // priority 15 = after cover, before comments

// Share CSS
add_action('wp_head', function() {
    ?>
    <style>
    .gpp-share{display:flex;align-items:center;gap:10px;margin:12px 0;padding-bottom:16px;border-bottom:1px solid var(--gp-border)}
    .gpp-share-label{font-family:var(--gp-font-ui);font-size:12px;color:var(--gp-textfaint);text-transform:uppercase;letter-spacing:.1em}
    .gpp-share-btn{width:32px;height:32px;border-radius:50%;border:1px solid var(--gp-border);display:flex;align-items:center;justify-content:center;color:var(--gp-textmuted);transition:all .15s;text-decoration:none}
    .gpp-share-btn:hover{border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-mobile-share{display:none}
    @media(max-width:768px){.gpp-mobile-share{display:flex}}
    </style>
    <?php
});

// ═══ Auto-link URLs in pages/posts + target=_blank for external ═══
// ═══ Convert <br><br> to proper <p> for text-indent (красная строка) ═══
// Runs AFTER wpautop (priority 10) which already wraps in <p>
add_filter('the_content', function($content) {
    // <br> followed by <br> → close and reopen paragraph
    $content = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/i', '</p><p>', $content);
    // Single trailing <br> before </p> can also get indent
    $content = preg_replace('/<br\s*\/?>\s*(?=[А-Яа-яA-Za-z0-9«"])/iu', '</p><p>', $content);
    // Clean up empty paragraphs
    $content = preg_replace('/<p>\s*<\/p>/', '', $content);
    return $content;
}, 11);

add_filter('the_content', function($content) {
    // WP's built-in make_clickable handles all edge cases
    $content = make_clickable($content);

    // Add target=_blank to external links that don't have it
    $content = preg_replace_callback(
        '/<a\s([^>]*)href=["\']((https?:\/\/)[^"\']+)["\']([^>]*)>/i',
        function($m) {
            $full = $m[0];
            $href = $m[2];
            if (strpos($full, 'target=') !== false) return $full;
            $site_host = parse_url(home_url(), PHP_URL_HOST);
            $link_host = parse_url($href, PHP_URL_HOST);
            if ($link_host && $link_host !== $site_host) {
                return str_replace('<a ', '<a target="_blank" rel="noopener" ', $full);
            }
            return $full;
        },
        $content
    );
    return $content;
}, 20);
