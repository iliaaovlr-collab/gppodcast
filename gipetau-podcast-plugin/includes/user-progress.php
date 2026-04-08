<?php
/**
 * User listening progress — DB for logged-in, localStorage fallback.
 * Saves per-episode positions to user meta.
 */
defined('ABSPATH') || exit;

// Save position (AJAX)
add_action('wp_ajax_gpp_save_progress', function() {
    $src = sanitize_text_field($_POST['src'] ?? '');
    $pos = intval($_POST['pos'] ?? 0);
    if (!$src) wp_send_json_error();

    $user_id = get_current_user_id();
    $progress = get_user_meta($user_id, '_gpp_progress', true) ?: [];
    $progress[$src] = $pos;
    update_user_meta($user_id, '_gpp_progress', $progress);
    wp_send_json_success();
});

// Load all positions (AJAX)
add_action('wp_ajax_gpp_load_progress', function() {
    $progress = get_user_meta(get_current_user_id(), '_gpp_progress', true) ?: [];
    wp_send_json_success($progress);
});

// Output user progress as JS variable + sync helpers
add_action('wp_head', function() {
    if (!is_user_logged_in()) return;
    $progress = get_user_meta(get_current_user_id(), '_gpp_progress', true) ?: [];
    ?>
    <script>
    var gppUserProgress = <?php echo json_encode($progress, JSON_UNESCAPED_UNICODE); ?>;
    var gppLoggedIn = true;
    var gppAjaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
    </script>
    <?php
}, 1);

add_action('wp_head', function() {
    if (is_user_logged_in()) return;
    echo '<script>var gppUserProgress=null;var gppLoggedIn=false;</script>';
}, 1);
