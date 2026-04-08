<?php
/**
 * Отображение выпуска: обложка + комментарии в правой панели.
 * Форма комментариев — AJAX, без перезагрузки (в центре).
 */
defined('ABSPATH') || exit;

// Cover in right panel (priority 10)
add_action('gp_right_panel', function() {
    if (!is_singular('podcast_episode')) return;
    $cover_url = gpp_episode_cover(get_the_ID(), 'large');
    if ($cover_url): ?>
    <div class="gpp-sidebar-cover">
        <img class="gpp-cover-img" src="<?php echo esc_url($cover_url); ?>" alt="">
    </div>
    <?php endif;
}, 10);

// Comments in right panel (priority 20 — after share@15)
add_action('gp_right_panel', function() {
    if (!is_singular('podcast_episode')) return;
    $post_id = get_the_ID();
    ?>
    <div class="gpp-sidebar-comments">
        <h3 class="gpp-sidebar-title">Комментарии</h3>
        <?php
        $comments = get_comments([
            'post_id' => $post_id, 'status' => 'approve',
            'number' => 50, 'orderby' => 'comment_date', 'order' => 'DESC',
        ]);
        if ($comments): ?>
            <div class="gpp-comments-list" id="gppCommentsList">
            <?php foreach ($comments as $c):
                $audio_time = get_comment_meta($c->comment_ID, '_gpp_audio_time', true);
            ?>
                <div class="gpp-comment" <?php if ($audio_time !== '' && $audio_time !== false) echo 'data-time="' . intval($audio_time) . '"'; ?>>
                    <span class="gpp-comment-author"><?php echo esc_html($c->comment_author); ?></span>
                    <span class="gpp-comment-time">[<?php echo human_time_diff(strtotime($c->comment_date), current_time('timestamp')); ?> назад]</span>
                    <div class="gpp-comment-text"><?php echo esc_html($c->comment_content); ?></div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="gpp-no-comments">Пока нет комментариев.</p>
        <?php endif; ?>
    </div>
    <?php
}, 20);

// Сохраняем метку времени аудио при отправке комментария
add_action('comment_post', function($comment_id) {
    if (isset($_POST['gpp_audio_time']) && $_POST['gpp_audio_time'] !== '') {
        update_comment_meta($comment_id, '_gpp_audio_time', intval($_POST['gpp_audio_time']));
    }
});

// AJAX комментарий (без перезагрузки)
add_action('wp_ajax_gpp_comment', 'gpp_ajax_comment');
add_action('wp_ajax_nopriv_gpp_comment', 'gpp_ajax_comment');

function gpp_ajax_comment() {
    check_ajax_referer('gpp_comment_nonce', 'nonce');

    $post_id = intval($_POST['comment_post_ID'] ?? 0);
    if (!$post_id) wp_send_json_error('Нет ID записи');
    if (!is_user_logged_in()) wp_send_json_error('Требуется авторизация');

    $user = wp_get_current_user();
    $text = sanitize_textarea_field($_POST['comment'] ?? '');
    if (empty($text)) wp_send_json_error('Пустой комментарий');

    // Проверка дубликата
    global $wpdb;
    $dupe = $wpdb->get_var($wpdb->prepare(
        "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID=%d AND comment_author=%s AND comment_content=%s LIMIT 1",
        $post_id, $user->display_name, $text
    ));
    if ($dupe) wp_send_json_error('Уже отправлено');

    // Flood check (15 сек)
    $last = $wpdb->get_var($wpdb->prepare(
        "SELECT comment_date_gmt FROM $wpdb->comments WHERE comment_author=%s ORDER BY comment_date_gmt DESC LIMIT 1",
        $user->display_name
    ));
    if ($last && (time() - strtotime($last)) < 15) wp_send_json_error('Подождите');

    $comment_id = wp_insert_comment([
        'comment_post_ID' => $post_id,
        'comment_content'  => $text,
        'comment_author'   => $user->display_name,
        'comment_author_email' => $user->user_email,
        'user_id'          => $user->ID,
        'comment_approved' => 1,
    ]);

    if (!$comment_id) wp_send_json_error('Ошибка');

    // Метка времени аудио
    $audio_time = $_POST['gpp_audio_time'] ?? '';
    if ($audio_time !== '') {
        update_comment_meta($comment_id, '_gpp_audio_time', intval($audio_time));
    }

    $c = get_comment($comment_id);
    $html = '<div class="gpp-comment"' . ($audio_time !== '' ? ' data-time="' . intval($audio_time) . '"' : '') . '>'
          . '<span class="gpp-comment-author">' . esc_html($c->comment_author) . '</span>'
          . '<span class="gpp-comment-time">[только что]</span>'
          . '<div class="gpp-comment-text">' . esc_html($c->comment_content) . '</div>'
          . '</div>';

    wp_send_json_success([
        'html'     => $html,
        'newNonce' => wp_create_nonce('gpp_comment_nonce'),
    ]);
}

// Подключаем AJAX данные для фронта
add_action('wp_head', function() {
    if (!is_singular('podcast_episode')) return;
    echo '<script>var gppAjax=' . json_encode([
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gpp_comment_nonce'),
    ]) . ';</script>';
}, 5);

// CSS — always output (needed after AJAX navigation too)
add_action('wp_head', function() {
    ?>
    <style>
    .gpp-sidebar-cover{margin-bottom:16px}
    .gpp-cover-img{width:100%;height:auto;aspect-ratio:1;object-fit:cover;border-radius:4px;border:1px solid var(--gp-border)}
    .gpp-sidebar-title{font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);letter-spacing:.25em;text-transform:uppercase;color:var(--gp-accent);margin-bottom:12px}
    .gpp-comments-list{max-height:60vh;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.08) transparent}
    .gpp-comment{padding:8px 0;border-bottom:1px solid var(--gp-border);transition:opacity .2s}
    .gpp-comment-author{font-weight:600;color:var(--gp-accent);font-size:15px}
    .gpp-comment-time{font-family:var(--gp-font-ui);font-size:11px;color:var(--gp-textfaint);margin-left:4px}
    .gpp-comment-text{font-size:15px;color:var(--gp-text2);margin-top:4px;line-height:1.5}
    .gpp-no-comments{font-style:italic;color:var(--gp-textmuted);font-size:15px}


    /* 1. Таймкоды: без отступов, одинаковый шрифт, плашка */
    .gpp-timecode{display:block;padding:0;margin:0;line-height:1.6;font-family:var(--gp-font-body);font-size:var(--gp-fs-body)}
    .gpp-timecode+br{display:none}
    .gpp-tc-time{font-family:var(--gp-font-body);font-size:var(--gp-fs-body);color:var(--gp-accent);text-decoration:none;margin-right:6px;transition:all .15s}
    .gpp-tc-time:hover{text-decoration:underline}
    .gpp-tc-active{background:var(--gp-accent);color:#fff!important;padding:1px 6px;border-radius:3px;text-decoration:none!important}

    /* 2. Prev/next — крупнее */
    .gpp-episode-nav{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;margin-top:24px;padding-top:16px;border-top:1px solid var(--gp-border)}
    .gpp-nav-prev,.gpp-nav-next{font-family:var(--gp-font-body);font-style:italic;font-size:var(--gp-fs-body);color:var(--gp-textmuted);text-decoration:none;max-width:100%}
    .gpp-nav-prev:hover,.gpp-nav-next:hover{color:var(--gp-accent)}
    .gpp-nav-next{margin-left:auto;text-align:right}

    /* Форма комментария (центр) */
    .gpp-comment-form-center{margin-top:20px}
    .gpp-cf-row{display:flex;gap:8px;align-items:flex-end}
    .gpp-cf-row textarea{flex:1;background:var(--gp-surface);border:1px solid var(--gp-border2);border-radius:4px;padding:10px 14px;color:var(--gp-text);font-family:var(--gp-font-body);font-size:15px;resize:none;outline:none}
    .gpp-cf-row textarea:focus{border-color:var(--gp-accentdim)}
    .gpp-cf-send{background:var(--gp-accent);color:#fff;border:none;border-radius:4px;width:40px;height:40px;font-size:18px;cursor:pointer;flex-shrink:0}
    .gpp-cf-send:hover{background:#d03a2e}
    .gpp-comment-form-center p{margin:0 0 8px}
    .gpp-comment-form-center .logged-in-as{font-size:14px;color:var(--gp-text2)}
    .gpp-comment-form-center .logged-in-as a{color:var(--gp-accent)}
    .gpp-cf-status{font-size:13px;color:var(--gp-textmuted);margin-top:4px;min-height:18px}
    #reply-title{display:none}
    .single-podcast_episode .entry-thumbnail{display:none}

    /* Chapter navigation above player */

    /* 3+4. Mobile: cover before player + comments after form */
    .gpp-mobile-cover{display:none}
    .gpp-mobile-comments{display:none}

    @media(max-width:768px){
        .gpp-mobile-cover{display:block;text-align:center;margin-bottom:16px}
        .gpp-mobile-cover img{max-width:250px;width:100%;height:auto;aspect-ratio:1;object-fit:cover;border-radius:4px;border:1px solid var(--gp-border);margin:0 auto}
        .gpp-mobile-comments{display:block;margin-top:24px;padding-top:16px;border-top:1px solid var(--gp-border)}
    }
    </style>
    <?php
});

// Archive CSS — always output
add_action('wp_head', function() {
    ?>
    <style>
    .gpp-archive-header{margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .gpp-archive-title{font-family:var(--gp-font-heading);font-style:italic;font-size:var(--gp-fs-h1);color:var(--gp-text);margin:0}

    /* List controls — prominent */
    .gpp-list-controls{display:flex;gap:10px;margin-bottom:20px}
    .gpp-lc-main{
        width:42px;height:42px;border-radius:50%;border:2px solid var(--gp-border2);
        background:none;color:var(--gp-text);cursor:pointer;
        display:flex;align-items:center;justify-content:center;transition:all .15s;
    }
    .gpp-lc-main:first-child{width:48px;height:48px;border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-lc-main:first-child:hover{background:var(--gp-accent);color:#fff}
    .gpp-lc-main:hover{border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-lc-main.gpp-lc-active{border-color:var(--gp-accent);color:var(--gp-accent);background:var(--gp-accentbg)}

    .gpp-episodes-table{display:flex;flex-direction:column}
    .gpp-ep-row{
        display:flex;align-items:center;gap:14px;padding:12px 0;
        border-bottom:1px solid var(--gp-border);transition:background .15s;
    }
    .gpp-ep-row:hover{background:var(--gp-surface)}
    .gpp-ep-row.gpp-ep-active{background:rgba(192,53,42,.08);border-left:3px solid var(--gp-accent);padding-left:11px}

    /* Season navigation */
    .gpp-season-nav{display:flex;gap:6px;margin-bottom:20px;align-items:center;flex-wrap:wrap}
    .gpp-season-label{font-family:var(--gp-font-ui);font-size:12px;color:var(--gp-textfaint);text-transform:uppercase;letter-spacing:.1em;margin-right:4px}
    .gpp-season-num{
        font-family:var(--gp-font-ui);font-size:13px;color:var(--gp-textmuted);
        text-decoration:none;padding:3px 8px;border:1px solid var(--gp-border);
        border-radius:4px;transition:all .15s;min-width:28px;text-align:center;
    }
    .gpp-season-num:hover{border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-season-num.gpp-season-active{border-color:var(--gp-accent);background:var(--gp-accentbg);color:var(--gp-accent)}
    .gpp-season-dots{color:var(--gp-textfaint);font-size:12px}

    /* Cover with play overlay */
    .gpp-ep-cover{position:relative;width:48px;height:48px;flex-shrink:0;cursor:pointer}
    .gpp-ep-cover img{width:48px;height:48px;border-radius:4px;object-fit:cover;border:1px solid var(--gp-border)}
    .gpp-ep-cover-ph{width:48px;height:48px;border-radius:4px;background:var(--gp-surface);display:flex;align-items:center;justify-content:center;font-size:18px}
    .gpp-ep-cover-ring{position:absolute;inset:0;width:48px;height:48px;transform:rotate(-90deg);pointer-events:none}
    .gpp-ep-play-icon{
        position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
        font-size:16px;color:#fff;background:rgba(0,0,0,.35);border-radius:4px;
        opacity:0;transition:opacity .15s;
    }
    .gpp-ep-cover:hover .gpp-ep-play-icon{opacity:1}

    .gpp-ep-info{flex:1;min-width:0}
    .gpp-ep-title{font-family:var(--gp-font-body);font-size:17px;color:var(--gp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .gpp-ep-se{font-size:13px;color:var(--gp-textmuted);margin-right:3px}
    .gpp-ep-link{color:inherit;text-decoration:none}
    .gpp-ep-link:hover{color:var(--gp-accent);text-decoration:underline}
    .gpp-ep-date{font-family:var(--gp-font-ui);font-size:12px;color:var(--gp-textfaint);margin-top:3px}

    .gpp-ep-remaining{flex-shrink:0;text-align:right;padding-right:8px}
    .gpp-ep-ring-bg{fill:none;stroke:var(--gp-border2);stroke-width:2}
    .gpp-ep-ring-fill{fill:none;stroke:var(--gp-accent);stroke-width:2;stroke-linecap:round}
    .gpp-ep-remain-text{font-family:var(--gp-font-ui);font-size:12px;color:var(--gp-textmuted);white-space:nowrap}

    /* Load more */
    .gpp-load-more-wrap{text-align:center;padding:20px 0}
    .gpp-load-more-btn{
        font-family:var(--gp-font-ui);font-size:13px;color:var(--gp-textmuted);
        background:none;border:1px solid var(--gp-border);border-radius:20px;
        padding:8px 24px;cursor:pointer;transition:all .15s;
    }
    .gpp-load-more-btn:hover{border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-load-more-btn:disabled{opacity:.5;cursor:wait}

    @media(max-width:768px){
        .gpp-ep-remaining{display:none}
        .gpp-ep-title{font-size:15px}
    }
    </style>
    <?php
});
