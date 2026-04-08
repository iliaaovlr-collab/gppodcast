<?php
/**
 * Настройки плагина GipetauPodcast
 */
defined('ABSPATH') || exit;

add_action('admin_menu', function() {
    add_menu_page(
        'GipetauPodcast',
        'GipetauPodcast',
        'manage_options',
        'gipetau-podcast',
        'gpp_settings_page',
        'dashicons-microphone',
        26
    );
});

add_action('admin_init', function() {
    register_setting('gpp_settings', 'gpp_rss_url', ['sanitize_callback' => 'esc_url_raw']);
});

function gpp_settings_page() {
    if (!current_user_can('manage_options')) return;

    $rss_url    = get_option('gpp_rss_url', '');
    $last_sync  = get_option('gpp_last_sync', '');
    $last_stats = get_option('gpp_last_sync_stats', []);
    $about_id   = get_option('gpp_about_page_id', 0);

    // Считаем выпуски
    $total = wp_count_posts('podcast_episode');
    $published = $total->publish ?? 0;
    $drafts    = $total->draft ?? 0;
    ?>
    <div class="wrap">
        <h1>GipetauPodcast — Настройки</h1>

        <form method="post" action="options.php">
            <?php settings_fields('gpp_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="gpp_rss_url">RSS-лента подкаста</label></th>
                    <td>
                        <input type="url" id="gpp_rss_url" name="gpp_rss_url"
                               value="<?php echo esc_attr($rss_url); ?>"
                               class="regular-text" placeholder="https://cloud.mave.digital/46508">
                        <p class="description">URL RSS-ленты. Mave, Anchor, Buzzsprout, etc.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Сохранить'); ?>
        </form>

        <hr>

        <h2>Синхронизация</h2>
        <table class="widefat" style="max-width:500px">
            <tr><th>Выпусков</th><td><?php echo $published; ?> опубликовано, <?php echo $drafts; ?> черновиков</td></tr>
            <tr><th>Последняя синхр.</th><td><?php echo $last_sync ?: 'Ещё не было'; ?></td></tr>
            <?php if ($last_stats): ?>
            <tr><th>Результат</th><td>
                Создано: <?php echo $last_stats['created'] ?? 0; ?>,
                обновлено: <?php echo $last_stats['updated'] ?? 0; ?>,
                в черновик: <?php echo $last_stats['drafted'] ?? 0; ?>
            </td></tr>
            <?php endif; ?>
            <?php if ($about_id): ?>
            <tr><th>Страница «О подкасте»</th><td><a href="<?php echo get_edit_post_link($about_id); ?>">Редактировать</a></td></tr>
            <?php endif; ?>
            <tr><th>Следующая авто</th><td><?php
                $next = wp_next_scheduled('gpp_rss_sync_hook');
                echo $next ? date_i18n('d.m.Y H:i:s', $next) : 'Не запланирована';
            ?></td></tr>
        </table>

        <div style="margin-top:20px">
            <h3>Ручной запуск</h3>
            <p class="description">
                <strong>Первичный импорт:</strong> укажите лимит (0 = все). После первого импорта используйте «Синхронизировать» без лимита.<br>
                При первом запуске создаётся страница «О подкасте» с описанием и обложкой канала.
            </p>
            <p>
                <label>Лимит выпусков: <input type="number" id="gpp-sync-limit" value="0" min="0" style="width:60px"></label>
                <button type="button" class="button button-primary" id="gpp-sync-btn">Синхронизировать</button>
                <span id="gpp-sync-status" style="margin-left:10px"></span>
            </p>
        </div>
    </div>

    <script>
    jQuery(function($){
        $('#gpp-sync-btn').on('click', function(){
            var btn = $(this);
            var status = $('#gpp-sync-status');
            var limit = parseInt($('#gpp-sync-limit').val()) || 0;

            btn.prop('disabled', true);
            status.text('Синхронизация...');

            $.post(ajaxurl, {
                action: 'gpp_manual_sync',
                nonce: '<?php echo wp_create_nonce('gpp_sync_nonce'); ?>',
                limit: limit
            }, function(resp) {
                btn.prop('disabled', false);
                if (resp.success) {
                    var d = resp.data;
                    if (d.error) {
                        status.text('Ошибка: ' + d.error);
                    } else {
                        status.text('Готово! Создано: ' + d.created + ', обновлено: ' + d.updated + ', в черновик: ' + d.drafted);
                        setTimeout(function(){ location.reload(); }, 2000);
                    }
                } else {
                    status.text('Ошибка: ' + (resp.data || 'неизвестная'));
                }
            }).fail(function(){
                btn.prop('disabled', false);
                status.text('Ошибка сети');
            });
        });
    });
    </script>
    <?php
}
