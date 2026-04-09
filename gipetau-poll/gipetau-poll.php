<?php
/**
 * Plugin Name: Гипетау Опросы
 * Description: Опросы для подкаста Гипетау
 * Version: 1.0
 */
defined('ABSPATH') || exit;

/* ═══ 1. ТИП ЗАПИСИ «ОПРОС» ═══ */

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

/* ═══ 2. КОЛОНКИ В СПИСКЕ ОПРОСОВ ═══ */

add_filter('manage_gpp_poll_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $val) {
        $new[$key] = $val;
        if ($key === 'title') {
            $new['poll_season'] = 'Сезон';
            $new['poll_end']    = 'Окончание';
            $new['poll_votes']  = 'Голосов';
        }
    }
    unset($new['date']);
    return $new;
});

add_action('manage_gpp_poll_posts_custom_column', function ($column, $post_id) {
    if ($column === 'poll_season') {
        $s = get_post_meta($post_id, '_gpp_poll_season', true);
        echo $s ? ('Сезон ' . esc_html($s)) : '—';
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
        $v = get_post_meta($post_id, '_gpp_poll_votes', true);
        echo is_array($v) ? array_sum($v) : 0;
    }
}, 10, 2);

/* ═══ 3. МЕТАБОКС ═══ */

add_action('add_meta_boxes', function () {
    add_meta_box('gpp_poll_settings', 'Настройки опроса', 'gpp_poll_metabox_render', 'gpp_poll', 'normal', 'high');
});

function gpp_poll_metabox_render($post) {
    wp_nonce_field('gpp_poll_save', 'gpp_poll_nonce');

    $options  = get_post_meta($post->ID, '_gpp_poll_options', true);
    if (!is_array($options) || empty($options)) $options = ['', ''];
    $season   = get_post_meta($post->ID, '_gpp_poll_season', true);
    $end_date = get_post_meta($post->ID, '_gpp_poll_end_date', true);

    global $wpdb;
    $seasons = $wpdb->get_col(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
         WHERE meta_key = '_gpp_season' AND meta_value != ''
         ORDER BY CAST(meta_value AS UNSIGNED) ASC"
    );
    ?>
    <style>
    .gpp-pm-row{margin-bottom:16px}
    .gpp-pm-row label{display:block;font-weight:600;margin-bottom:4px}
    .gpp-pm-opt{display:flex;gap:8px;margin-bottom:6px;align-items:center}
    .gpp-pm-opt input[type="text"]{flex:1}
    .gpp-pm-opt .button{color:#b32d2e}
    #gpp_poll_end_date{width:180px}
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
        <label for="gpp_poll_season">Сезон</label>
        <select name="gpp_poll_season" id="gpp_poll_season">
            <option value="">— Выберите сезон —</option>
            <?php foreach ($seasons as $s): ?>
            <option value="<?php echo esc_attr($s); ?>" <?php selected($season, $s); ?>>
                Сезон <?php echo esc_html($s); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="gpp-pm-row">
        <label for="gpp_poll_end_date">Дата окончания голосования</label>
        <input type="date" name="gpp_poll_end_date" id="gpp_poll_end_date"
               value="<?php echo esc_attr($end_date); ?>">
    </div>

    <?php
    $votes = get_post_meta($post->ID, '_gpp_poll_votes', true);
    if (is_array($votes) && array_sum($votes) > 0):
        $total = array_sum($votes);
    ?>
    <div class="gpp-pm-row">
        <label>Результаты (<?php echo $total; ?> голосов)</label>
        <ul style="margin:0">
        <?php foreach ($options as $i => $opt):
            $c = $votes[$i] ?? 0;
            $p = $total > 0 ? round($c / $total * 100) : 0;
        ?>
            <li><?php echo esc_html($opt); ?>: <?php echo $c; ?> (<?php echo $p; ?>%)</li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

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
        if (document.querySelectorAll('#gppPollOpts .gpp-pm-opt').length > 2) {
            e.target.closest('.gpp-pm-opt').remove();
        }
    });
    </script>
    <?php
}

/* ═══ 4. СОХРАНЕНИЕ ═══ */

add_action('save_post_gpp_poll', function ($post_id) {
    if (!isset($_POST['gpp_poll_nonce'])) return;
    if (!wp_verify_nonce($_POST['gpp_poll_nonce'], 'gpp_poll_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $opts = array_values(array_filter(
        array_map('sanitize_text_field', $_POST['gpp_poll_options'] ?? [])
    ));
    update_post_meta($post_id, '_gpp_poll_options',  $opts);
    update_post_meta($post_id, '_gpp_poll_season',   sanitize_text_field($_POST['gpp_poll_season'] ?? ''));
    update_post_meta($post_id, '_gpp_poll_end_date', sanitize_text_field($_POST['gpp_poll_end_date'] ?? ''));
});

/* ═══ 5. ИДЕНТИФИКАЦИЯ ГОЛОСУЮЩЕГО ═══ */

function gpp_poll_voter_hash() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return md5($ip . '|' . $ua);
}

function gpp_poll_get_user_vote($poll_id) {
    if (isset($_COOKIE['gpp_voted_' . $poll_id])) {
        return intval($_COOKIE['gpp_voted_' . $poll_id]);
    }
    $hash = gpp_poll_voter_hash();
    $vote = get_post_meta($poll_id, '_gpp_voter_' . $hash, true);
    if ($vote !== '' && $vote !== false) {
        return intval($vote);
    }
    return false;
}

/* ═══ 6. AJAX-ГОЛОСОВАНИЕ ═══ */

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

    // Сохраняем голос по хэшу IP+UA
    $hash = gpp_poll_voter_hash();
    update_post_meta($poll_id, '_gpp_voter_' . $hash, $option);

    // Обновляем счётчики
    $votes = get_post_meta($poll_id, '_gpp_poll_votes', true);
    if (!is_array($votes)) $votes = [];
    $votes[$option] = ($votes[$option] ?? 0) + 1;
    update_post_meta($poll_id, '_gpp_poll_votes', $votes);

    // Cookie 90 дней
    setcookie('gpp_voted_' . $poll_id, $option, time() + 90 * 86400, '/');

    wp_send_json_success(['voted' => $option]);
}

/* ═══ 7. ФРОНТЕНД: ПОКАЗ ОПРОСА ═══ */

add_action('gpp_after_player', 'gpp_poll_display');

function gpp_poll_display($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $season = get_post_meta($post_id, '_gpp_season', true);
    if (!$season) return;

    $polls = get_posts([
        'post_type'      => 'gpp_poll',
        'post_status'    => 'publish',
        'meta_query'     => [['key' => '_gpp_poll_season', 'value' => $season]],
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
        $total     = array_sum($votes);
        $max_votes = $total > 0 ? max($votes) : 0;

        echo '<div class="gpp-poll" data-poll-id="' . $poll->ID . '">';
        echo '<div class="gpp-poll-question">' . esc_html($poll->post_title) . '</div>';
        echo '<div class="gpp-poll-options">';

        foreach ($options as $i => $opt) {
            $count     = $votes[$i] ?? 0;
            $pct       = $total > 0 ? round($count / $total * 100) : 0;
            $is_winner = $total > 0 && $count === $max_votes;
            $is_user   = ($user_vote !== false && $user_vote === $i);

            if ($ended) {
                /* --- Результаты --- */
                $cls = 'gpp-poll-opt gpp-poll-opt--result';
                if ($is_winner) $cls .= ' gpp-poll-opt--winner';
                if ($is_user)   $cls .= ' gpp-poll-opt--mine';
                echo '<div class="' . $cls . '" style="--pct:' . $pct . '%">';
                echo '<span class="gpp-poll-opt-label">' . esc_html($opt) . '</span>';
                echo '<span class="gpp-poll-opt-pct">' . $pct . '%</span>';
                echo '</div>';

            } elseif ($user_vote !== false) {
                /* --- Уже проголосовал, опрос активен --- */
                $cls = 'gpp-poll-opt gpp-poll-opt--locked';
                if ($is_user) $cls .= ' gpp-poll-opt--voted';
                echo '<div class="' . $cls . '">';
                echo '<span class="gpp-poll-opt-label">' . esc_html($opt) . '</span>';
                if ($is_user) echo '<span class="gpp-poll-opt-check">✓</span>';
                echo '</div>';

            } else {
                /* --- Можно голосовать --- */
                echo '<button class="gpp-poll-opt" data-option="' . $i . '">';
                echo '<span class="gpp-poll-opt-label">' . esc_html($opt) . '</span>';
                echo '</button>';
            }
        }

        echo '</div>';

        /* Подвал */
        echo '<div class="gpp-poll-footer">';
        if ($ended) {
            echo 'Голосование завершено';
            if ($total > 0) echo ' &middot; ' . $total . ' ' . gpp_poll_word($total);
        } else {
            if ($end_date) echo 'Голосование до ' . gpp_poll_ru_date($end_date);
            if ($user_vote !== false) echo ' &middot; Ваш голос принят';
        }
        echo '</div>';

        echo '</div>';
    }
}

/* Склонение слова «голос» */
function gpp_poll_word($n) {
    $m = $n % 10; $m100 = $n % 100;
    if ($m === 1 && $m100 !== 11) return 'голос';
    if ($m >= 2 && $m <= 4 && ($m100 < 10 || $m100 >= 20)) return 'голоса';
    return 'голосов';
}

/* Дата по-русски: «15 апреля» */
function gpp_poll_ru_date($date) {
    $months = ['','января','февраля','марта','апреля','мая','июня',
               'июля','августа','сентября','октября','ноября','декабря'];
    $d = intval(date('j', strtotime($date)));
    $m = $months[intval(date('n', strtotime($date)))];
    return $d . ' ' . $m;
}

/* ═══ 8. CSS ═══ */

add_action('wp_head', 'gpp_poll_css');
function gpp_poll_css() {
    if (!is_singular('podcast_episode')) return;
    ?>
    <style>
    .gpp-poll{margin:24px 0;padding:20px;background:var(--gp-surface);border:1px solid var(--gp-border);border-radius:4px}
    .gpp-poll-question{font-family:var(--gp-font-heading);font-size:var(--gp-fs-h3);font-style:var(--gp-h-style);font-weight:var(--gp-h-weight);color:var(--gp-text);margin:0 0 16px;line-height:var(--gp-lh-heading)}
    .gpp-poll-options{display:flex;flex-wrap:wrap;gap:8px}

    .gpp-poll-opt{padding:8px 18px;border:1px solid var(--gp-border2);border-radius:20px;font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);color:var(--gp-text2);background:transparent;cursor:pointer;transition:all .15s;user-select:none;display:inline-flex;align-items:center;gap:6px;line-height:1.3}
    button.gpp-poll-opt:hover{border-color:var(--gp-accent);color:var(--gp-accent);background:var(--gp-accentbg)}
    button.gpp-poll-opt:disabled{opacity:.5;pointer-events:none}

    .gpp-poll-opt--locked{cursor:default}
    .gpp-poll-opt--voted{border-color:var(--gp-accent);background:var(--gp-accentbg);color:var(--gp-accent)}
    .gpp-poll-opt-check{font-size:11px;line-height:1}

    .gpp-poll-opt--result{position:relative;overflow:hidden;cursor:default}
    .gpp-poll-opt--result::before{content:'';position:absolute;left:0;top:0;bottom:0;width:var(--pct);background:var(--gp-accentbg);border-radius:20px;z-index:0;transition:width .8s ease}
    .gpp-poll-opt--result .gpp-poll-opt-label,
    .gpp-poll-opt--result .gpp-poll-opt-pct{position:relative;z-index:1}
    .gpp-poll-opt-pct{font-weight:600;font-size:var(--gp-fs-xs);color:var(--gp-textmuted)}
    .gpp-poll-opt--winner{border-color:var(--gp-accent);color:var(--gp-accent)}
    .gpp-poll-opt--winner::before{background:var(--gp-accentdim)}
    .gpp-poll-opt--winner .gpp-poll-opt-pct{color:var(--gp-accent)}
    .gpp-poll-opt--mine{box-shadow:inset 0 0 0 1px var(--gp-accent)}

    .gpp-poll-footer{margin-top:12px;font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);color:var(--gp-textmuted)}
    </style>
    <?php
}

/* ═══ 9. JS (ГОЛОСОВАНИЕ) ═══ */

add_action('wp_footer', 'gpp_poll_js');
function gpp_poll_js() {
    if (!is_singular('podcast_episode')) return;
    ?>
    <script>
    (function(){
      document.querySelectorAll('.gpp-poll').forEach(function(poll){
        poll.addEventListener('click', function(e){
          var btn = e.target.closest('button.gpp-poll-opt');
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
                poll.querySelectorAll('button.gpp-poll-opt').forEach(function(b){
                  var div = document.createElement('div');
                  div.className = 'gpp-poll-opt gpp-poll-opt--locked';
                  if (b.dataset.option === String(resp.data.voted)) {
                    div.classList.add('gpp-poll-opt--voted');
                    div.innerHTML = b.innerHTML + '<span class="gpp-poll-opt-check">\u2713</span>';
                  } else {
                    div.innerHTML = b.innerHTML;
                  }
                  b.replaceWith(div);
                });
                var footer = poll.querySelector('.gpp-poll-footer');
                if (footer && !footer.textContent.includes('голос принят')) {
                  footer.innerHTML += ' &middot; Ваш голос принят';
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
