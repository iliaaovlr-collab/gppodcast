<?php
/**
 * GipetauPodcast Theme Functions
 * Minimal. No business logic. Plugins handle the rest.
 *
 * @package GipetauPodcast
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

define('GP_VERSION', wp_get_theme()->get('Version'));
define('GP_DIR', get_template_directory());
define('GP_URI', get_template_directory_uri());

// Customizer
require_once GP_DIR . '/inc/customizer.php';

// ══════════════════════════════════════════
// SETUP
// ══════════════════════════════════════════
add_action('after_setup_theme', function(){
    load_theme_textdomain('gipetau-podcast', GP_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['comment-form','comment-list','search-form','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    register_nav_menus([
        'primary' => __('Основное меню', 'gipetau-podcast'),
        'footer'  => __('Меню в подвале', 'gipetau-podcast'),
    ]);

    // Thumbnail sizes
    set_post_thumbnail_size(720, 400, true);
    add_image_size('gp-wide', 1200, 600, true);
    add_image_size('gp-square', 400, 400, true);
});

// ══════════════════════════════════════════
// ASSETS — zero JS by default, CSS < 20KB
// ══════════════════════════════════════════
add_action('wp_enqueue_scripts', function(){
    // Google Fonts — only what the selected preset needs
    $fonts_url = gp_get_google_fonts_url();
    if ($fonts_url) {
        wp_enqueue_style('gp-fonts', $fonts_url, [], null);
    }

    wp_enqueue_style('gp-style', get_stylesheet_uri(), $fonts_url ? ['gp-fonts'] : [], GP_VERSION);

    // JS: only mobile nav, only when menu exists, only in footer
    if (has_nav_menu('primary')) {
        wp_enqueue_script('gp-nav', GP_URI . '/assets/js/navigation.js', [], GP_VERSION, true);
    }

    // Remove WP bloat
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
});

// Block editor styles
add_action('enqueue_block_editor_assets', function(){
    wp_enqueue_style('gp-editor', GP_URI . '/assets/css/editor.css', [], GP_VERSION);
});

// ══════════════════════════════════════════
// CLEANUP — remove WP cruft from <head>
// ══════════════════════════════════════════
add_action('init', function(){
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
});

// Disable emoji
add_filter('emoji_svg_url', '__return_false');

// Body class: add layout type for CSS targeting
add_filter('body_class', function($classes){
    $layout = get_theme_mod('gp_layout_type', 'three-column');
    $classes[] = 'gp-layout-' . $layout;
    return $classes;
});

// ══════════════════════════════════════════
// WIDGETS
// ══════════════════════════════════════════
add_action('widgets_init', function(){
    $areas = [
        'sidebar-left'   => 'Левая панель',
        'sidebar-right'  => 'Правая панель',
        'before-content' => 'Перед контентом',
        'after-content'  => 'После контента',
        'footer-1'       => 'Подвал — колонка 1',
        'footer-2'       => 'Подвал — колонка 2',
        'footer-3'       => 'Подвал — колонка 3',
    ];
    foreach ($areas as $id => $name) {
        // Skip if disabled in Customizer
        if (!get_theme_mod('gp_widget_area_' . $id, true)) continue;
        register_sidebar([
            'name'          => __($name, 'gipetau-podcast'),
            'id'            => $id,
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ]);
    }
});

// ══════════════════════════════════════════
// EXCERPT
// ══════════════════════════════════════════
add_filter('excerpt_length', function(){ return 30; });
add_filter('excerpt_more', function(){ return '&hellip;'; });

// ══════════════════════════════════════════
// COMMENT WALKER HOOK (for plugins to override)
// ══════════════════════════════════════════
function gp_comment_callback($comment, $args, $depth) {
    // Plugins can override via 'gp_comment_callback' filter
    $callback = apply_filters('gp_comment_callback', null);
    if ($callback && is_callable($callback)) {
        call_user_func($callback, $comment, $args, $depth);
        return;
    }
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class('comment-body'); ?>>
        <div class="comment-author"><?php comment_author(); ?></div>
        <div class="comment-meta">
            <time datetime="<?php comment_time('c'); ?>">
                <?php echo human_time_diff(get_comment_time('U'), current_time('timestamp')); ?> назад
            </time>
        </div>
        <div class="comment-content"><?php comment_text(); ?></div>
    <?php
}

// ══════════════════════════════════════════
// HELPERS (available to plugins)
// ══════════════════════════════════════════
function gp_posted_on() {
    printf(
        '<time datetime="%1$s">%2$s</time>',
        esc_attr(get_the_date('c')),
        esc_html(get_the_date())
    );
}

function gp_entry_meta() {
    echo '<div class="entry-meta">';
    echo '<span>' . esc_html(get_the_date()) . '</span>';
    if (has_category()) {
        echo '<span>' . get_the_category_list(', ') . '</span>';
    }
    echo '</div>';
}

// Theme switcher JS
add_action('wp_footer', function() { ?>
<script>
(function(){
  var saved = localStorage.getItem('gp_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved);
  function mark(t){
    document.querySelectorAll('.gp-ts-btn').forEach(function(b){
      b.classList.toggle('gp-ts-active', b.dataset.theme === t);
    });
  }
  mark(saved);
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.gp-ts-btn');
    if (!btn) return;
    var t = btn.dataset.theme;
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('gp_theme', t);
    mark(t);
  });
})();
</script>
<?php }, 99);

// Apply theme early (prevent flash)
add_action('wp_head', function() { ?>
<script>
(function(){var t=localStorage.getItem('gp_theme');if(t)document.documentElement.setAttribute('data-theme',t);})();
</script>
<?php }, 0);

// Copyright helper
function gp_copyright() {
    $tpl = get_theme_mod('gp_copyright', '© {year} {site}');
    $tpl = str_replace('{year}', date('Y'), $tpl);
    $tpl = str_replace('{site}', get_bloginfo('name'), $tpl);
    echo '<div class="footer-copy">' . esc_html($tpl) . '</div>';
}
