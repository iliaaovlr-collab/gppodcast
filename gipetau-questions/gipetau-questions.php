<?php
/**
 * Plugin Name: Гипетау Вопросы
 * Description: Форма для отправки тем и вопросов к следующему выпуску подкаста
 * Version: 1.0
 */
defined('ABSPATH') || exit;

/* ═══════════════════════════════════════════
   1. ТИП ЗАПИСИ «ВОПРОС»
   ═══════════════════════════════════════════ */

add_action('init', function () {
    register_post_type('gpp_question', [
        'labels' => [
            'name'               => 'Вопросы',
            'singular_name'      => 'Вопрос',
            'add_new'            => 'Добавить',
            'add_new_item'       => 'Новый вопрос',
            'edit_item'          => 'Просмотр вопроса',
            'all_items'          => 'Все вопросы',
            'search_items'       => 'Найти вопрос',
            'not_found'          => 'Вопросов не найдено',
            'not_found_in_trash' => 'В корзине пусто',
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'supports'          => ['title'],
        'menu_icon'         => 'dashicons-format-chat',
        'menu_position'     => 26,
    ]);
});

/* ═══════════════════════════════════════════
   2. КОЛОНКИ В СПИСКЕ ВОПРОСОВ
   ═══════════════════════════════════════════ */

add_filter('manage_gpp_question_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $val) {
        if ($key === 'title') {
            $new[$key] = 'Сообщение';
        } else {
            $new[$key] = $val;
        }
        if ($key === 'title') {
            $new['q_name']     = 'Имя';
            $new['q_contact']  = 'Контакт';
            $new['q_category'] = 'Категория';
        }
    }
    return $new;
});

add_action('manage_gpp_question_posts_custom_column', function ($column, $post_id) {
    if ($column === 'q_name') {
        echo esc_html(get_post_meta($post_id, '_gppq_name', true) ?: '—');
    }
    if ($column === 'q_contact') {
        $type = get_post_meta($post_id, '_gppq_contact_type', true);
        $val  = get_post_meta($post_id, '_gppq_contact_value', true);
        if ($type && $val) {
            echo esc_html($type . ': ' . $val);
        } else {
            echo '—';
        }
    }
    if ($column === 'q_category') {
        $cat = get_post_meta($post_id, '_gppq_category', true);
        echo esc_html($cat ?: '—');
    }
}, 10, 2);

/* ═══════════════════════════════════════════
   3. МЕТАБОКС (ПРОСМОТР В АДМИНКЕ)
   ═══════════════════════════════════════════ */

add_action('add_meta_boxes', function () {
    add_meta_box('gppq_details', 'Детали вопроса', 'gppq_metabox_render', 'gpp_question', 'normal', 'high');
});

function gppq_metabox_render($post) {
    $name     = get_post_meta($post->ID, '_gppq_name', true);
    $ct       = get_post_meta($post->ID, '_gppq_contact_type', true);
    $cv       = get_post_meta($post->ID, '_gppq_contact_value', true);
    $cat      = get_post_meta($post->ID, '_gppq_category', true);
    $message  = get_post_meta($post->ID, '_gppq_message', true);
    $email    = get_post_meta($post->ID, '_gppq_verify_email', true);
    ?>
    <style>
    .gppq-detail{margin-bottom:12px}
    .gppq-detail label{display:block;font-weight:600;margin-bottom:2px;color:#333}
    .gppq-detail .val{padding:8px 12px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px}
    </style>
    <div class="gppq-detail">
        <label>Имя</label>
        <div class="val"><?php echo esc_html($name ?: '—'); ?></div>
    </div>
    <div class="gppq-detail">
        <label>Контакт</label>
        <div class="val"><?php echo esc_html($ct && $cv ? "$ct: $cv" : '—'); ?></div>
    </div>
    <?php if ($email): ?>
    <div class="gppq-detail">
        <label>Email для верификации</label>
        <div class="val"><?php echo esc_html($email); ?></div>
    </div>
    <?php endif; ?>
    <div class="gppq-detail">
        <label>Категория</label>
        <div class="val"><?php echo esc_html($cat ?: '—'); ?></div>
    </div>
    <div class="gppq-detail">
        <label>Сообщение</label>
        <div class="val"><?php echo nl2br(esc_html($message ?: '—')); ?></div>
    </div>
    <?php
}

/* ═══════════════════════════════════════════
   4. AJAX: ШАГ 1 — ОТПРАВКА КОДА
   ═══════════════════════════════════════════ */

add_action('wp_ajax_gppq_send_code',        'gppq_send_code');
add_action('wp_ajax_nopriv_gppq_send_code', 'gppq_send_code');

function gppq_send_code() {
    // Проверяем все поля
    $name    = sanitize_text_field($_POST['name'] ?? '');
    $ct      = sanitize_text_field($_POST['contact_type'] ?? '');
    $cv      = sanitize_text_field($_POST['contact_value'] ?? '');
    $cat     = sanitize_text_field($_POST['category'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $email   = sanitize_email($_POST['verify_email'] ?? '');

    if (!$name || !$ct || !$cv || !$cat || !$message) {
        wp_send_json_error('Заполните все поля');
    }

    // Для email-контакта: код отправляем на сам email
    if ($ct === 'Email') {
        $email = sanitize_email($cv);
    }

    if (!$email || !is_email($email)) {
        wp_send_json_error('Укажите корректный email для подтверждения');
    }

    // Защита от спама: не чаще 1 кода в 60 секунд с одного IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rate_key = 'gppq_rate_' . md5($ip);
    if (get_transient($rate_key)) {
        wp_send_json_error('Подождите минуту перед повторной отправкой');
    }
    set_transient($rate_key, 1, 60);

    // Генерируем 6-значный код
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Сохраняем в transient (10 минут)
    $token = md5($email . wp_generate_password(12, false));
    set_transient('gppq_code_' . $token, [
        'code'    => $code,
        'email'   => $email,
        'name'    => $name,
        'ct'      => $ct,
        'cv'      => $cv,
        'cat'     => $cat,
        'message' => $message,
    ], 600);

    // Отправляем письмо
    $subject = 'Код подтверждения — Гипетау Подкаст';
    $body    = "Ваш код подтверждения: $code\n\nКод действителен 10 минут.\nЕсли вы не отправляли вопрос — просто проигнорируйте это письмо.";

    $sent = wp_mail($email, $subject, $body);
    if (!$sent) {
        wp_send_json_error('Не удалось отправить письмо. Попробуйте позже');
    }

    wp_send_json_success(['token' => $token]);
}

/* ═══════════════════════════════════════════
   5. AJAX: ШАГ 2 — ПРОВЕРКА КОДА И СОХРАНЕНИЕ
   ═══════════════════════════════════════════ */

add_action('wp_ajax_gppq_verify',        'gppq_verify');
add_action('wp_ajax_nopriv_gppq_verify', 'gppq_verify');

function gppq_verify() {
    $token = sanitize_text_field($_POST['token'] ?? '');
    $code  = sanitize_text_field($_POST['code'] ?? '');

    if (!$token || !$code) {
        wp_send_json_error('Укажите код подтверждения');
    }

    $data = get_transient('gppq_code_' . $token);
    if (!$data) {
        wp_send_json_error('Код истёк. Отправьте форму заново');
    }

    if ($data['code'] !== $code) {
        wp_send_json_error('Неверный код');
    }

    // Код верный — удаляем transient
    delete_transient('gppq_code_' . $token);

    // Создаём запись
    $post_id = wp_insert_post([
        'post_type'   => 'gpp_question',
        'post_status' => 'publish',
        'post_title'  => mb_substr($data['message'], 0, 80),
    ]);

    if (is_wp_error($post_id)) {
        wp_send_json_error('Ошибка сохранения');
    }

    update_post_meta($post_id, '_gppq_name',          $data['name']);
    update_post_meta($post_id, '_gppq_contact_type',   $data['ct']);
    update_post_meta($post_id, '_gppq_contact_value',  $data['cv']);
    update_post_meta($post_id, '_gppq_category',       $data['cat']);
    update_post_meta($post_id, '_gppq_message',        $data['message']);
    update_post_meta($post_id, '_gppq_verify_email',   $data['email']);

    // Уведомление администратору
    $admin_email = get_option('admin_email');
    $subj = 'Новый вопрос на Гипетау: ' . $data['cat'];
    $body = "Имя: {$data['name']}\n"
          . "Контакт: {$data['ct']}: {$data['cv']}\n"
          . "Категория: {$data['cat']}\n\n"
          . "Сообщение:\n{$data['message']}\n\n"
          . "Посмотреть: " . admin_url('post.php?post=' . $post_id . '&action=edit');
    wp_mail($admin_email, $subj, $body);

    wp_send_json_success();
}

/* ═══════════════════════════════════════════
   6. ФРОНТЕНД: ФОРМА НА СТРАНИЦЕ ЭПИЗОДА
   ═══════════════════════════════════════════ */

add_action('gp_after_content', 'gppq_display_form');

function gppq_display_form($post_id = null) {
    if (!is_singular('podcast_episode')) return;
    ?>
    <div class="gppq-wrap" id="gppqWrap">
      <h3 class="gppq-title">Предложить тему или задать вопрос</h3>
      <p class="gppq-desc">Ваше сообщение попадёт к ведущим для следующего выпуска</p>

      <!-- Шаг 1: Форма -->
      <div id="gppqFormStep" class="gppq-step">
        <div class="gppq-field">
          <label for="gppqName">Имя</label>
          <input type="text" id="gppqName" placeholder="Как вас зовут">
        </div>

        <div class="gppq-field">
          <label>Контактные данные</label>
          <div class="gppq-contact-row">
            <select id="gppqCType">
              <option value="Email">Email</option>
              <option value="Телефон">Телефон</option>
              <option value="Telegram">Telegram</option>
            </select>
            <input type="text" id="gppqCValue" placeholder="email@example.com">
          </div>
        </div>

        <div class="gppq-field gppq-verify-email-field" id="gppqEmailField" style="display:none">
          <label for="gppqVerifyEmail">Email для подтверждения</label>
          <input type="email" id="gppqVerifyEmail" placeholder="email@example.com">
          <span class="gppq-hint">На этот адрес придёт код подтверждения</span>
        </div>

        <div class="gppq-field">
          <label>Категория</label>
          <div class="gppq-cats">
            <label class="gppq-cat"><input type="radio" name="gppq_cat" value="Тема"> Тема</label>
            <label class="gppq-cat"><input type="radio" name="gppq_cat" value="Вопрос Системе"> Вопрос Системе</label>
            <label class="gppq-cat"><input type="radio" name="gppq_cat" value="Вопрос ведущим"> Вопрос ведущим</label>
          </div>
        </div>

        <div class="gppq-field">
          <label for="gppqMsg">Сообщение</label>
          <textarea id="gppqMsg" rows="4" placeholder="Опишите тему или задайте вопрос…"></textarea>
        </div>

        <button class="gppq-submit" id="gppqSendBtn">Отправить</button>
        <div class="gppq-status" id="gppqStatus"></div>
      </div>

      <!-- Шаг 2: Ввод кода -->
      <div id="gppqCodeStep" class="gppq-step" style="display:none">
        <p class="gppq-code-info">Мы отправили 6-значный код на ваш email.<br>Введите его ниже:</p>
        <div class="gppq-code-row">
          <input type="text" id="gppqCode" maxlength="6" placeholder="000000" inputmode="numeric" autocomplete="one-time-code">
          <button class="gppq-submit" id="gppqVerifyBtn">Подтвердить</button>
        </div>
        <div class="gppq-status" id="gppqCodeStatus"></div>
        <button class="gppq-back" id="gppqBackBtn">← Назад к форме</button>
      </div>

      <!-- Шаг 3: Успех -->
      <div id="gppqDoneStep" class="gppq-step" style="display:none">
        <div class="gppq-done-msg">Спасибо! Ваше сообщение отправлено ведущим.</div>
      </div>
    </div>
    <?php
}

/* ═══════════════════════════════════════════
   7. CSS
   ═══════════════════════════════════════════ */

add_action('wp_head', 'gppq_css');
function gppq_css() {
    if (!is_singular('podcast_episode')) return;
    ?>
    <style>
    /* Карточка */
    .gppq-wrap{
      margin:32px 0;padding:24px 28px;
      background:var(--gp-surface);
      border:1px solid var(--gp-border);border-radius:6px;
      box-shadow:0 2px 12px rgba(0,0,0,.15);
    }
    .gppq-title{
      font-family:var(--gp-font-heading);
      font-size:var(--gp-fs-h3);font-style:var(--gp-h-style);font-weight:var(--gp-h-weight);
      color:var(--gp-text);margin:0 0 4px;line-height:var(--gp-lh-heading);
      text-align:center;
    }
    .gppq-desc{
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);
      color:var(--gp-textmuted);text-align:center;margin:0 0 20px;
    }

    /* Поля */
    .gppq-field{margin-bottom:14px}
    .gppq-field>label{
      display:block;margin-bottom:4px;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      letter-spacing:.15em;text-transform:uppercase;
      color:var(--gp-textfaint);
    }
    .gppq-field input[type="text"],
    .gppq-field input[type="email"],
    .gppq-field textarea,
    .gppq-field select{
      width:100%;background:var(--gp-surface);
      border:1px solid var(--gp-border2);border-radius:4px;
      padding:10px 14px;color:var(--gp-text);
      font-family:var(--gp-font-body);font-size:var(--gp-fs-body);
      outline:none;transition:border-color .15s;
      box-sizing:border-box;
    }
    .gppq-field input:focus,
    .gppq-field textarea:focus,
    .gppq-field select:focus{border-color:var(--gp-accentdim)}
    .gppq-field textarea{min-height:100px;resize:vertical}

    /* Контакт: тип + значение в одну строку */
    .gppq-contact-row{display:flex;gap:8px}
    .gppq-contact-row select{width:auto;min-width:120px;flex-shrink:0}
    .gppq-contact-row input{flex:1}

    /* Подсказка под полем */
    .gppq-hint{
      display:block;margin-top:4px;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-xs);
      color:var(--gp-textmuted);
    }

    /* Радиокнопки категорий */
    .gppq-cats{display:flex;flex-wrap:wrap;gap:10px}
    .gppq-cat{
      display:inline-flex;align-items:center;gap:6px;cursor:pointer;
      padding:8px 16px;border:1px solid var(--gp-border2);border-radius:24px;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);
      color:var(--gp-text2);transition:all .2s;user-select:none;
    }
    .gppq-cat:hover{border-color:var(--gp-accentdim);color:var(--gp-text)}
    .gppq-cat input{display:none}
    .gppq-cat.active{
      border-color:var(--gp-accent);background:var(--gp-accentbg);color:var(--gp-accent);
    }

    /* Кнопка отправки */
    .gppq-submit{
      display:block;width:100%;margin-top:8px;
      background:var(--gp-accent);color:#fff;border:none;
      padding:12px 24px;border-radius:4px;cursor:pointer;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);
      font-weight:500;transition:background .15s;
    }
    .gppq-submit:hover{background:#d03a2e}
    .gppq-submit:disabled{opacity:.5;cursor:not-allowed}

    /* Статус */
    .gppq-status{
      margin-top:8px;text-align:center;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);
      color:var(--gp-accent);min-height:20px;
    }

    /* Шаг 2: код */
    .gppq-code-info{
      text-align:center;
      font-family:var(--gp-font-body);font-size:var(--gp-fs-body);
      color:var(--gp-text2);margin:0 0 16px;
    }
    .gppq-code-row{
      display:flex;gap:8px;max-width:320px;margin:0 auto;
    }
    .gppq-code-row input{
      flex:1;text-align:center;
      font-size:24px;letter-spacing:.3em;
      font-family:var(--gp-font-ui);
      background:var(--gp-surface);border:1px solid var(--gp-border2);
      border-radius:4px;padding:10px;color:var(--gp-text);outline:none;
    }
    .gppq-code-row input:focus{border-color:var(--gp-accentdim)}
    .gppq-code-row .gppq-submit{width:auto;flex-shrink:0}

    /* Назад */
    .gppq-back{
      display:block;margin:12px auto 0;
      background:none;border:none;cursor:pointer;
      font-family:var(--gp-font-ui);font-size:var(--gp-fs-small);
      color:var(--gp-textmuted);transition:color .15s;
    }
    .gppq-back:hover{color:var(--gp-text2)}

    /* Успех */
    .gppq-done-msg{
      text-align:center;padding:24px 0;
      font-family:var(--gp-font-body);font-size:var(--gp-fs-body);
      color:var(--gp-text);
    }

    /* Мобилка */
    @media(max-width:768px){
      .gppq-wrap{padding:18px 16px}
      .gppq-contact-row{flex-direction:column}
      .gppq-contact-row select{width:100%}
      .gppq-cats{gap:8px}
      .gppq-cat{padding:7px 12px;font-size:13px}
    }
    </style>
    <?php
}

/* ═══════════════════════════════════════════
   8. JS
   ═══════════════════════════════════════════ */

add_action('wp_footer', 'gppq_js');
function gppq_js() {
    if (!is_singular('podcast_episode')) return;
    ?>
    <script>
    (function(){
      var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
      var token = '';

      /* --- Переключение типа контакта --- */
      var ctype  = document.getElementById('gppqCType');
      var cvalue = document.getElementById('gppqCValue');
      var efield = document.getElementById('gppqEmailField');

      if (ctype) ctype.addEventListener('change', function(){
        var t = this.value;
        if (t === 'Email') {
          cvalue.type = 'email';
          cvalue.placeholder = 'email@example.com';
          efield.style.display = 'none';
        } else if (t === 'Телефон') {
          cvalue.type = 'tel';
          cvalue.placeholder = '+7 (999) 123-45-67';
          efield.style.display = '';
        } else {
          cvalue.type = 'text';
          cvalue.placeholder = '@username';
          efield.style.display = '';
        }
      });

      /* --- Радиокнопки категорий --- */
      document.querySelectorAll('.gppq-cat').forEach(function(label){
        label.addEventListener('click', function(){
          document.querySelectorAll('.gppq-cat').forEach(function(l){ l.classList.remove('active'); });
          this.classList.add('active');
        });
      });

      /* --- Шаг 1: отправка кода --- */
      var sendBtn = document.getElementById('gppqSendBtn');
      if (sendBtn) sendBtn.addEventListener('click', function(){
        var name = document.getElementById('gppqName').value.trim();
        var ct   = ctype.value;
        var cv   = cvalue.value.trim();
        var catEl = document.querySelector('input[name="gppq_cat"]:checked');
        var cat  = catEl ? catEl.value : '';
        var msg  = document.getElementById('gppqMsg').value.trim();
        var vemail = '';

        var status = document.getElementById('gppqStatus');

        if (!name || !cv || !cat || !msg) {
          status.textContent = 'Заполните все поля';
          return;
        }

        // Для email-контакта — email = значение контакта
        // Для остальных — берём доп. поле
        if (ct === 'Email') {
          vemail = cv;
        } else {
          vemail = document.getElementById('gppqVerifyEmail').value.trim();
        }

        if (!vemail) {
          status.textContent = 'Укажите email для подтверждения';
          return;
        }

        sendBtn.disabled = true;
        status.textContent = 'Отправляем код…';

        var fd = new FormData();
        fd.append('action', 'gppq_send_code');
        fd.append('name', name);
        fd.append('contact_type', ct);
        fd.append('contact_value', cv);
        fd.append('category', cat);
        fd.append('message', msg);
        fd.append('verify_email', vemail);

        fetch(ajaxUrl, {method:'POST', body:fd})
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (resp.success) {
              token = resp.data.token;
              document.getElementById('gppqFormStep').style.display = 'none';
              document.getElementById('gppqCodeStep').style.display = '';
              document.getElementById('gppqCode').focus();
              status.textContent = '';
            } else {
              status.textContent = resp.data || 'Ошибка';
            }
          })
          .catch(function(){ status.textContent = 'Ошибка сети'; })
          .finally(function(){ sendBtn.disabled = false; });
      });

      /* --- Шаг 2: проверка кода --- */
      var verifyBtn = document.getElementById('gppqVerifyBtn');
      if (verifyBtn) verifyBtn.addEventListener('click', function(){
        var code = document.getElementById('gppqCode').value.trim();
        var codeStatus = document.getElementById('gppqCodeStatus');

        if (!code || code.length !== 6) {
          codeStatus.textContent = 'Введите 6-значный код';
          return;
        }

        verifyBtn.disabled = true;
        codeStatus.textContent = 'Проверяем…';

        var fd = new FormData();
        fd.append('action', 'gppq_verify');
        fd.append('token', token);
        fd.append('code', code);

        fetch(ajaxUrl, {method:'POST', body:fd})
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (resp.success) {
              document.getElementById('gppqCodeStep').style.display = 'none';
              document.getElementById('gppqDoneStep').style.display = '';
            } else {
              codeStatus.textContent = resp.data || 'Ошибка';
            }
          })
          .catch(function(){ codeStatus.textContent = 'Ошибка сети'; })
          .finally(function(){ verifyBtn.disabled = false; });
      });

      /* --- Назад к форме --- */
      var backBtn = document.getElementById('gppqBackBtn');
      if (backBtn) backBtn.addEventListener('click', function(){
        document.getElementById('gppqCodeStep').style.display = 'none';
        document.getElementById('gppqFormStep').style.display = '';
      });
    })();
    </script>
    <?php
}
