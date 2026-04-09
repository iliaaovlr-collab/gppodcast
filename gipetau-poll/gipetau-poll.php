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

    // Ищем опросы, привязанные к этому эпизоду по UID
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
        $options = get_post_meta($poll->ID, '_gpp_poll_options', true);
        if (!is_array($options) || empty($options)) continue;

        $end_date  = get_post_meta($poll->ID, '_gpp_poll_end_date', true);
        $ended     = $end_date && strtotime($end_date . ' 23:59:59') < time();
        $user_vote = gpp_poll_get_user_vote($poll->ID);

        $votes     = get_post_meta($poll->ID, '_gpp_poll_votes', true);
        if (!is_array($votes)) $votes = [];
        $tg_votes  = get_post_meta($poll->ID, '_gpp_poll_tg_votes', true);
        if (!is_array($tg_votes)) $tg_votes = [];

        // Сумма сайт + ТГ
        $combined = [];
        foreach ($options as $i => $opt) {
            $combined[$i] = ($votes[$i] ?? 0) + ($tg_votes[$i] ?? 0);
        }
        $site_total = array_sum($votes);
        $tg_total   = array_sum($tg_votes);
        $total      = $site_total + $tg_total;
        $max_votes  = $total > 0 ? max($combined) : 0;

        echo '<div class="gpp-poll" data-poll-id="' . $poll->ID . '">';

        // Вопрос
        echo '<h3 class="gpp-poll-q">' . esc_html($poll->post_title) . '</h3>';

        // Варианты
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

        // Подвал
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

/* ═══════════════════════════════════════════
   9. CSS
   ═══════════════════════════════════════════ */

add_action('wp_head', 'gpp_poll_css');
function gpp_poll_css() {
    if (!is_singular('podcast_episode')) return;
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

    /* Мобилка */
    @media(max-width:768px){
      .gpp-poll{padding:18px 16px}
      .gpp-poll-opts{gap:8px}
      .gpp-po{padding:8px 16px;font-size:13px}
    }
    </style>
    <?php
}

/* ═══════════════════════════════════════════
   10. JS (ГОЛОСОВАНИЕ)
   ═══════════════════════════════════════════ */

add_action('wp_footer', 'gpp_poll_js');
function gpp_poll_js() {
    if (!is_singular('podcast_episode')) return;
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
    </script>
    <?php
}
