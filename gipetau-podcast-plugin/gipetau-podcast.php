<?php
/**
 * Plugin Name: GipetauPodcast
 * Description: Подкаст-платформа: выпуски из RSS, плеер, авторизация, комментарии.
 * Version: 1.0.0
 * Author: Ilya V. Aksenov
 * Text Domain: gipetau-podcast
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

define('GPP_VERSION', '1.0.0');
define('GPP_DIR', plugin_dir_path(__FILE__));
define('GPP_URL', plugin_dir_url(__FILE__));

// Модуль 1: Подкасты (CPT + RSS sync)
require_once GPP_DIR . 'includes/cpt-podcast.php';
require_once GPP_DIR . 'includes/rss-sync.php';
require_once GPP_DIR . 'includes/settings.php';
require_once GPP_DIR . 'includes/episode-display.php';
require_once GPP_DIR . 'includes/player.php';
require_once GPP_DIR . 'includes/user-progress.php';
require_once GPP_DIR . 'includes/seo.php';

// Шаблоны — из плагина
add_filter('single_template', function($template) {
    if (get_post_type() === 'podcast_episode') {
        $plugin_tpl = GPP_DIR . 'templates/single-podcast_episode.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    return $template;
});

add_filter('archive_template', function($template) {
    if (is_post_type_archive('podcast_episode') || is_tax('podcast_season')) {
        $plugin_tpl = GPP_DIR . 'templates/archive-podcast_episode.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    return $template;
});

// Allow data-time attribute on <a> and <span> in post content
add_filter('wp_kses_allowed_html', function($tags, $context) {
    if ($context === 'post') {
        $tags['a']['data-time'] = true;
        $tags['span']['data-time'] = true;
    }
    return $tags;
}, 10, 2);

/**
 * Get episode cover URL with fallback to podcast cover.
 */
function gpp_episode_cover($post_id, $size = 'thumbnail') {
    $url = get_the_post_thumbnail_url($post_id, $size);
    if ($url) return $url;
    // Fallback: podcast cover
    return get_option('gpp_podcast_cover', '');
}

// Активация / деактивация
register_activation_hook(__FILE__, 'gpp_activate');
register_deactivation_hook(__FILE__, 'gpp_deactivate');

function gpp_activate() {
    gpp_register_podcast_cpt();
    flush_rewrite_rules();
    // Планировщик — каждый час
    if (!wp_next_scheduled('gpp_rss_sync_hook')) {
        wp_schedule_event(time(), 'hourly', 'gpp_rss_sync_hook');
    }
}

function gpp_deactivate() {
    wp_clear_scheduled_hook('gpp_rss_sync_hook');
    flush_rewrite_rules();
}

// AJAX: lazy load episodes for "All" page
add_action('wp_ajax_gpp_load_episodes', 'gpp_load_episodes_ajax');
add_action('wp_ajax_nopriv_gpp_load_episodes', 'gpp_load_episodes_ajax');

function gpp_load_episodes_ajax() {
    $page = intval($_POST['page'] ?? 2);
    $eps = new WP_Query([
        'post_type' => 'podcast_episode', 'posts_per_page' => 10,
        'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
        'paged' => $page,
    ]);
    ob_start();
    while ($eps->have_posts()): $eps->the_post();
        $id = get_the_ID();
        $dur = intval(get_post_meta($id, '_gpp_duration', true));
        $audio = get_post_meta($id, '_gpp_audio_url', true);
        $s = get_post_meta($id, '_gpp_season', true);
        $e = get_post_meta($id, '_gpp_episode', true);
        $se = $s ? "S{$s}E{$e}" : "#{$e}";
        $date = get_the_date('j M');
        if (get_the_date('Y') !== date('Y')) $date = get_the_date('j M Y');
        $cover = gpp_episode_cover($id);
        $tc_json = get_post_meta($id, '_gpp_timecodes', true) ?: '[]';
        ?>
        <div class="gpp-ep-row"
             data-episode-audio="<?php echo esc_attr($audio); ?>"
             data-episode-title="<?php echo esc_attr(get_the_title()); ?>"
             data-episode-se="<?php echo esc_attr($se); ?>"
             data-episode-cover="<?php echo esc_attr($cover); ?>"
             data-episode-duration="<?php echo $dur; ?>"
             data-episode-tc='<?php echo esc_attr($tc_json); ?>'>
          <div class="gpp-ep-cover" onclick="gppListPlay(this.closest('.gpp-ep-row'))">
            <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt=""><?php else: ?><div class="gpp-ep-cover-ph">🎙</div><?php endif; ?>
            <svg class="gpp-ep-cover-ring" viewBox="0 0 48 48">
              <circle cx="24" cy="24" r="22" class="gpp-ep-ring-bg"/>
              <circle cx="24" cy="24" r="22" class="gpp-ep-ring-fill"/>
            </svg>
            <span class="gpp-ep-play-icon" data-src="<?php echo esc_attr($audio); ?>">▶</span>
          </div>
          <div class="gpp-ep-info">
            <div class="gpp-ep-title"><span class="gpp-ep-se"><?php echo esc_html($se); ?></span> <a href="<?php the_permalink(); ?>" class="gpp-ep-link"><?php the_title(); ?></a></div>
            <div class="gpp-ep-date"><?php echo esc_html($date); ?></div>
          </div>
          <div class="gpp-ep-remaining">
            <span class="gpp-ep-remain-text" data-src="<?php echo esc_attr($audio); ?>" data-dur="<?php echo $dur; ?>"><?php echo esc_html(gpp_format_duration($dur)); ?></span>
          </div>
        </div>
        <?php
    endwhile;
    wp_reset_postdata();
    wp_send_json_success(['html' => ob_get_clean()]);
}
