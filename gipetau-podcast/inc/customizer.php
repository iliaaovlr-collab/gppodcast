<?php
/**
 * GipetauPodcast — Customizer v2
 * Typography presets · Color schemes · Layout · Widgets · Logo
 */
defined('ABSPATH') || exit;

// ══════════════════════════════════════════
// PRESETS DATA
// ══════════════════════════════════════════

function gp_typography_presets() {
    return [
        'gipetau' => [
            'label'    => 'Гипетау (Cormorant + Montserrat)',
            'heading'  => "'Cormorant Garamond', Georgia, serif",
            'body'     => "'Cormorant Garamond', Georgia, serif",
            'ui'       => "'Montserrat', system-ui, sans-serif",
            'mono'     => "'Courier Prime', 'Courier New', monospace",
            'google'   => 'Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&family=Montserrat:wght@400;500;600&family=Courier+Prime',
            'h_style'  => 'italic',
            'h_weight' => '600',
        ],
        'classic' => [
            'label'    => 'Классика (Playfair + Source Sans)',
            'heading'  => "'Playfair Display', Georgia, serif",
            'body'     => "'Source Sans 3', system-ui, sans-serif",
            'ui'       => "'Source Sans 3', system-ui, sans-serif",
            'mono'     => "'Source Code Pro', monospace",
            'google'   => 'Playfair+Display:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;600&family=Source+Code+Pro:wght@400',
            'h_style'  => 'normal',
            'h_weight' => '700',
        ],
        'modern' => [
            'label'    => 'Модерн (Inter + Libre Baskerville)',
            'heading'  => "'Libre Baskerville', Georgia, serif",
            'body'     => "'Inter', system-ui, sans-serif",
            'ui'       => "'Inter', system-ui, sans-serif",
            'mono'     => "'JetBrains Mono', monospace",
            'google'   => 'Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400',
            'h_style'  => 'normal',
            'h_weight' => '700',
        ],
        'editorial' => [
            'label'    => 'Редакция (Lora + Fira Sans)',
            'heading'  => "'Lora', Georgia, serif",
            'body'     => "'Lora', Georgia, serif",
            'ui'       => "'Fira Sans', system-ui, sans-serif",
            'mono'     => "'Fira Mono', monospace",
            'google'   => 'Lora:ital,wght@0,400;0,600;1,400&family=Fira+Sans:wght@400;500;600&family=Fira+Mono:wght@400',
            'h_style'  => 'italic',
            'h_weight' => '600',
        ],
        'brutal' => [
            'label'    => 'Брутал (Space Grotesk + IBM Plex)',
            'heading'  => "'Space Grotesk', system-ui, sans-serif",
            'body'     => "'IBM Plex Sans', system-ui, sans-serif",
            'ui'       => "'IBM Plex Sans', system-ui, sans-serif",
            'mono'     => "'IBM Plex Mono', monospace",
            'google'   => 'Space+Grotesk:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400',
            'h_style'  => 'normal',
            'h_weight' => '700',
        ],
        'system' => [
            'label'    => 'Системный (без загрузки шрифтов)',
            'heading'  => "Georgia, 'Times New Roman', serif",
            'body'     => "system-ui, -apple-system, sans-serif",
            'ui'       => "system-ui, -apple-system, sans-serif",
            'mono'     => "ui-monospace, 'Courier New', monospace",
            'google'   => '',
            'h_style'  => 'normal',
            'h_weight' => '700',
        ],
    ];
}

function gp_color_schemes() {
    return [
        'gipetau' => [
            'label'      => 'Гипетау (тёмная, красный акцент)',
            'base'       => '#090909',
            'surface'    => '#111111',
            'surface2'   => '#1c1c1c',
            'border'     => 'rgba(255,255,255,0.08)',
            'border2'    => 'rgba(255,255,255,0.16)',
            'text'       => '#f2ede4',
            'text2'      => '#c4bbb0',
            'textmuted'  => '#8c857c',
            'textfaint'  => '#4e4b47',
            'accent'     => '#c0352a',
            'accentdim'  => 'rgba(192,53,42,0.55)',
            'accentbg'   => 'rgba(192,53,42,0.14)',
        ],
        'midnight' => [
            'label'      => 'Полночь (тёмно-синяя)',
            'base'       => '#0a0c14',
            'surface'    => '#111422',
            'surface2'   => '#1a1e30',
            'border'     => 'rgba(130,150,255,0.08)',
            'border2'    => 'rgba(130,150,255,0.16)',
            'text'       => '#e8eaf0',
            'text2'      => '#b0b4c4',
            'textmuted'  => '#7a7f94',
            'textfaint'  => '#464a5c',
            'accent'     => '#5b7fff',
            'accentdim'  => 'rgba(91,127,255,0.55)',
            'accentbg'   => 'rgba(91,127,255,0.14)',
        ],
        'forest' => [
            'label'      => 'Лес (тёмно-зелёная)',
            'base'       => '#080c09',
            'surface'    => '#0f1610',
            'surface2'   => '#182019',
            'border'     => 'rgba(140,200,140,0.08)',
            'border2'    => 'rgba(140,200,140,0.16)',
            'text'       => '#e4ede6',
            'text2'      => '#b0c4b4',
            'textmuted'  => '#7c8c80',
            'textfaint'  => '#46504a',
            'accent'     => '#4a9e5a',
            'accentdim'  => 'rgba(74,158,90,0.55)',
            'accentbg'   => 'rgba(74,158,90,0.14)',
        ],
        'amber' => [
            'label'      => 'Янтарь (тёплая тёмная)',
            'base'       => '#0c0a07',
            'surface'    => '#16130e',
            'surface2'   => '#201c14',
            'border'     => 'rgba(200,170,100,0.08)',
            'border2'    => 'rgba(200,170,100,0.12)',
            'text'       => '#ede4d4',
            'text2'      => '#c4b89a',
            'textmuted'  => '#8c8268',
            'textfaint'  => '#504a3c',
            'accent'     => '#c88a2a',
            'accentdim'  => 'rgba(200,138,42,0.55)',
            'accentbg'   => 'rgba(200,138,42,0.14)',
        ],
        'bone' => [
            'label'      => 'Кость (светлая)',
            'base'       => '#f4f0ea',
            'surface'    => '#eae5dc',
            'surface2'   => '#ddd7cc',
            'border'     => 'rgba(0,0,0,0.08)',
            'border2'    => 'rgba(0,0,0,0.14)',
            'text'       => '#1a1816',
            'text2'      => '#3c3834',
            'textmuted'  => '#6a6460',
            'textfaint'  => '#9a948e',
            'accent'     => '#a03020',
            'accentdim'  => 'rgba(160,48,32,0.55)',
            'accentbg'   => 'rgba(160,48,32,0.10)',
        ],
    ];
}

// ══════════════════════════════════════════
// REGISTER CUSTOMIZER CONTROLS
// ══════════════════════════════════════════
add_action('customize_register', function(WP_Customize_Manager $wp_customize){

    // ── Брендинг ──
    $wp_customize->add_setting('gp_show_title', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean', 'transport' => 'postMessage']);
    $wp_customize->add_control('gp_show_title', ['label' => 'Показывать название сайта', 'section' => 'title_tagline', 'type' => 'checkbox']);

    $wp_customize->add_setting('gp_show_tagline', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean', 'transport' => 'postMessage']);
    $wp_customize->add_control('gp_show_tagline', ['label' => 'Показывать краткое описание', 'section' => 'title_tagline', 'type' => 'checkbox']);

    $wp_customize->add_setting('gp_logo_height', ['default' => 40, 'sanitize_callback' => 'absint', 'transport' => 'postMessage']);
    $wp_customize->add_control('gp_logo_height', [
        'label' => 'Высота логотипа (px)', 'section' => 'title_tagline', 'type' => 'number',
        'description' => 'Для ретина: загрузите 2× и укажите желаемую высоту.',
        'input_attrs' => ['min' => 16, 'max' => 200, 'step' => 1],
    ]);

    $wp_customize->add_setting('gp_logo_retina', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'gp_logo_retina', [
        'label' => 'Логотип Retina (2×)', 'section' => 'title_tagline',
    ]));

    // ── Цветовая схема ──
    $wp_customize->add_section('gp_colors', ['title' => 'Цветовая схема', 'priority' => 25]);

    $schemes = gp_color_schemes();
    $choices = [];
    foreach ($schemes as $k => $s) $choices[$k] = $s['label'];

    $wp_customize->add_setting('gp_color_scheme', ['default' => 'gipetau', 'sanitize_callback' => function($v) use ($schemes){
        return isset($schemes[$v]) ? $v : 'gipetau';
    }]);
    $wp_customize->add_control('gp_color_scheme', [
        'label' => 'Схема', 'section' => 'gp_colors', 'type' => 'select', 'choices' => $choices,
    ]);

    // Ручной override акцента
    $wp_customize->add_setting('gp_accent_override', ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gp_accent_override', [
        'label' => 'Свой акцентный цвет (необязательно)', 'section' => 'gp_colors',
    ]));

    // ── Типографика ──
    $wp_customize->add_section('gp_typography', ['title' => 'Типографика', 'priority' => 26]);

    $presets = gp_typography_presets();
    $t_choices = [];
    foreach ($presets as $k => $p) $t_choices[$k] = $p['label'];
    $t_choices['custom'] = '— Свой набор —';

    $wp_customize->add_setting('gp_typo_preset', ['default' => 'gipetau', 'sanitize_callback' => function($v) use ($t_choices){
        return isset($t_choices[$v]) ? $v : 'gipetau';
    }]);
    $wp_customize->add_control('gp_typo_preset', [
        'label' => 'Набор шрифтов', 'section' => 'gp_typography', 'type' => 'select', 'choices' => $t_choices,
    ]);

    // Custom font family fields
    $custom_fonts = [
        'gp_custom_heading' => ['Шрифт заголовков', "'Cormorant Garamond', Georgia, serif"],
        'gp_custom_body'    => ['Шрифт текста', "'Cormorant Garamond', Georgia, serif"],
        'gp_custom_ui'      => ['Шрифт интерфейса', "'Montserrat', system-ui, sans-serif"],
        'gp_custom_mono'    => ['Моноширинный', "'Courier Prime', monospace"],
        'gp_custom_google'  => ['Google Fonts (часть URL после family=)', ''],
    ];
    foreach ($custom_fonts as $id => $cfg) {
        $wp_customize->add_setting($id, ['default' => $cfg[1], 'sanitize_callback' => 'sanitize_text_field']);
        $wp_customize->add_control($id, ['label' => $cfg[0], 'section' => 'gp_typography', 'type' => 'text']);
    }

    // ── Per-tag overrides (MATRIX) ──
    // Empty = inherit from preset. Only non-empty values generate CSS.
    $typo_tags = [
        'body'  => 'Основной текст',
        'h1'    => 'H1',
        'h2'    => 'H2',
        'h3'    => 'H3',
        'h4'    => 'H4',
        'small' => 'Мелкий / UI',
        'xs'    => 'XS / метки',
    ];

    // Properties: id_suffix => [label, type, attrs/choices]
    $typo_props = [
        'fs' => ['Размер',       'text', ['placeholder' => 'rem']],
        'fw' => ['Насыщ.',       'select', ['' => '—', '300' => '300', '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800']],
        'fi' => ['Начертание',   'select', ['' => '—', 'normal' => 'Обычн.', 'italic' => 'Курсив']],
        'lh' => ['Интерлиньяж',  'text', ['placeholder' => '']],
        'ls' => ['Трекинг',      'text', ['placeholder' => 'em']],
        'tt' => ['Регистр',      'select', ['' => '—', 'none' => 'Нет', 'uppercase' => 'ABC', 'lowercase' => 'abc', 'capitalize' => 'Abc']],
    ];

    foreach ($typo_tags as $tag => $tag_label) {
        foreach ($typo_props as $prop => $pcfg) {
            $sid = 'gp_t_' . $tag . '_' . $prop;
            $label = $tag_label . ' — ' . $pcfg[0];

            $wp_customize->add_setting($sid, [
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'postMessage',
            ]);

            if ($pcfg[1] === 'select') {
                $wp_customize->add_control($sid, [
                    'label'   => $label,
                    'section' => 'gp_typography',
                    'type'    => 'select',
                    'choices' => $pcfg[2],
                ]);
            } else {
                $wp_customize->add_control($sid, [
                    'label'       => $label,
                    'section'     => 'gp_typography',
                    'type'        => 'text',
                    'input_attrs' => $pcfg[2],
                ]);
            }
        }
    }

    // ── Раскладка ──
    $wp_customize->add_section('gp_layout', ['title' => 'Раскладка', 'priority' => 30]);

    $wp_customize->add_setting('gp_layout_type', ['default' => 'three-column', 'sanitize_callback' => function($v){
        return in_array($v, ['one-column','two-column-left','two-column-right','three-column']) ? $v : 'three-column';
    }]);
    $wp_customize->add_control('gp_layout_type', [
        'label' => 'Раскладка по умолчанию', 'section' => 'gp_layout', 'type' => 'select',
        'choices' => [
            'one-column'       => 'Одна колонка',
            'two-column-left'  => 'Сайдбар + контент',
            'two-column-right' => 'Контент + сайдбар',
            'three-column'     => 'Три колонки (Гипетау)',
        ],
    ]);

    $wp_customize->add_setting('gp_sidebar_left_width', ['default' => 220, 'sanitize_callback' => 'absint', 'transport' => 'postMessage']);
    $wp_customize->add_control('gp_sidebar_left_width', ['label' => 'Ширина левой колонки (px)', 'section' => 'gp_layout', 'type' => 'number', 'input_attrs' => ['min' => 160, 'max' => 360, 'step' => 10]]);

    $wp_customize->add_setting('gp_sidebar_right_width', ['default' => 280, 'sanitize_callback' => 'absint', 'transport' => 'postMessage']);
    $wp_customize->add_control('gp_sidebar_right_width', ['label' => 'Ширина правой колонки (px)', 'section' => 'gp_layout', 'type' => 'number', 'input_attrs' => ['min' => 200, 'max' => 400, 'step' => 10]]);

    // ── Области виджетов ──
    $wp_customize->add_section('gp_widgets_control', ['title' => 'Области виджетов', 'priority' => 35]);
    $areas = [
        'sidebar-left' => 'Левая панель', 'sidebar-right' => 'Правая панель',
        'footer-1' => 'Подвал 1', 'footer-2' => 'Подвал 2', 'footer-3' => 'Подвал 3',
        'before-content' => 'Перед контентом', 'after-content' => 'После контента',
    ];
    foreach ($areas as $id => $label) {
        $wp_customize->add_setting('gp_widget_area_' . $id, ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
        $wp_customize->add_control('gp_widget_area_' . $id, ['label' => $label, 'section' => 'gp_widgets_control', 'type' => 'checkbox']);
    }

    // ══════════════════════════════════════════
    // PANEL: Отображение записей
    // ══════════════════════════════════════════
    $wp_customize->add_panel('gp_display', [
        'title'    => 'Отображение записей',
        'priority' => 40,
    ]);

    // 3 контекста: front (основной), archive (наследует front), single (наследует archive)
    $contexts = [
        'front'   => ['label' => 'Главная страница', 'desc' => 'Основные настройки. Архив и Запись наследуют отсюда.'],
        'archive' => ['label' => 'Архив / Категории', 'desc' => 'Наследует с Главной. Отдельная запись наследует отсюда.'],
        'single'  => ['label' => 'Отдельная запись', 'desc' => 'Наследует из Архива (который наследует из Главной).'],
    ];

    $display_modes = [
        'full'    => 'Полный текст',
        'excerpt' => 'Отрывок',
        'title'   => 'Только заголовок',
    ];

    $meta_items = [
        'title'      => 'Заголовок записи',
        'date'       => 'Дата публикации',
        'author'     => 'Автор',
        'categories' => 'Рубрики',
        'tags'       => 'Метки',
        'thumbnail'  => 'Миниатюра',
        'comments'   => 'Число комментариев',
    ];

    foreach ($contexts as $ctx => $cfg) {
        $section_id = 'gp_display_' . $ctx;
        $wp_customize->add_section($section_id, [
            'title'       => $cfg['label'],
            'description' => $cfg['desc'],
            'panel'       => 'gp_display',
        ]);

        // Режим отображения
        if ($ctx === 'front') {
            // Главная — без варианта «Наследовать»
            $wp_customize->add_setting('gp_display_mode_' . $ctx, [
                'default' => 'excerpt',
                'sanitize_callback' => function($v) use ($display_modes) {
                    return isset($display_modes[$v]) ? $v : 'excerpt';
                },
            ]);
            $wp_customize->add_control('gp_display_mode_' . $ctx, [
                'label'   => 'Как показывать записи',
                'section' => $section_id,
                'type'    => 'select',
                'choices' => $display_modes,
            ]);
        } else {
            // Архив / Запись
            $modes_inherit = ['inherit' => '— Наследовать —'] + $display_modes;
            $ctx_default = ($ctx === 'single') ? 'full' : 'inherit';
            $wp_customize->add_setting('gp_display_mode_' . $ctx, [
                'default' => $ctx_default,
                'sanitize_callback' => function($v) use ($modes_inherit) {
                    return isset($modes_inherit[$v]) ? $v : 'inherit';
                },
            ]);
            $wp_customize->add_control('gp_display_mode_' . $ctx, [
                'label'   => 'Как показывать записи',
                'section' => $section_id,
                'type'    => 'select',
                'choices' => $modes_inherit,
            ]);
        }

        // Галочки show/hide для каждого элемента
        foreach ($meta_items as $item_id => $item_label) {
            $setting_id = 'gp_show_' . $item_id . '_' . $ctx;

            if ($ctx === 'front') {
                $wp_customize->add_setting($setting_id, [
                    'default'           => true,
                    'sanitize_callback' => 'wp_validate_boolean',
                ]);
                $wp_customize->add_control($setting_id, [
                    'label'   => $item_label,
                    'section' => $section_id,
                    'type'    => 'checkbox',
                ]);
            } else {
                // Три состояния: inherit / show / hide
                $wp_customize->add_setting($setting_id, [
                    'default'           => 'inherit',
                    'sanitize_callback' => function($v) {
                        return in_array($v, ['inherit','1','0']) ? $v : 'inherit';
                    },
                ]);
                $wp_customize->add_control($setting_id, [
                    'label'   => $item_label,
                    'section' => $section_id,
                    'type'    => 'select',
                    'choices' => [
                        'inherit' => '— Наследовать —',
                        '1'       => 'Показывать',
                        '0'       => 'Скрывать',
                    ],
                ]);
            }
        }
    }

    // Live preview
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport = 'postMessage';

    // Copyright
    $wp_customize->add_section('gp_footer', ['title' => 'Подвал', 'priority' => 35]);
    $wp_customize->add_setting('gp_copyright', [
        'default' => '© {year} {site}',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('gp_copyright', [
        'label' => 'Копирайт ({year} = год, {site} = название сайта)',
        'section' => 'gp_footer',
        'type' => 'text',
    ]);
});

// ══════════════════════════════════════════
// DISPLAY HELPERS — resolve inheritance
// ══════════════════════════════════════════

/**
 * Determine current context: 'front', 'archive', or 'single'
 */
function gp_current_context() {
    if (is_front_page() || is_home()) return 'front';
    if (is_singular()) return 'single';
    return 'archive'; // category, tag, date, author, search
}

/**
 * Get display mode for current context.
 * Chain: single inherits archive, archive inherits front.
 */
function gp_display_mode($context = null) {
    if (!$context) $context = gp_current_context();

    // Single defaults to 'full' (not inherit)
    $default = 'inherit';
    if ($context === 'front') $default = 'excerpt';
    if ($context === 'single') $default = 'full';

    $val = get_theme_mod('gp_display_mode_' . $context, $default);

    if ($val === 'inherit') {
        if ($context === 'single') return gp_display_mode('archive');
        return get_theme_mod('gp_display_mode_front', 'excerpt');
    }
    return $val;
}

/**
 * Check if element should be shown.
 * Chain: single → archive → front.
 */
function gp_show($item, $context = null) {
    if (!$context) $context = gp_current_context();

    $val = get_theme_mod('gp_show_' . $item . '_' . $context, $context === 'front' ? true : 'inherit');

    if ($val === 'inherit') {
        if ($context === 'single') {
            // single → archive → front
            return gp_show($item, 'archive');
        }
        // archive → front
        return (bool) get_theme_mod('gp_show_' . $item . '_front', true);
    }
    return (bool) $val;
}

// ══════════════════════════════════════════
// OUTPUT: CSS variables from settings
// ══════════════════════════════════════════
add_action('wp_head', function(){
    $scheme_key = get_theme_mod('gp_color_scheme', 'gipetau');
    $schemes    = gp_color_schemes();
    $scheme     = $schemes[$scheme_key] ?? $schemes['gipetau'];

    $typo_key   = get_theme_mod('gp_typo_preset', 'gipetau');
    if ($typo_key === 'custom') {
        $typo = [
            'heading'  => get_theme_mod('gp_custom_heading', "'Cormorant Garamond', Georgia, serif"),
            'body'     => get_theme_mod('gp_custom_body', "'Cormorant Garamond', Georgia, serif"),
            'ui'       => get_theme_mod('gp_custom_ui', "'Montserrat', system-ui, sans-serif"),
            'mono'     => get_theme_mod('gp_custom_mono', "'Courier Prime', monospace"),
            'h_style'  => 'italic',
            'h_weight' => '600',
        ];
    } else {
        $typos = gp_typography_presets();
        $typo  = $typos[$typo_key] ?? $typos['gipetau'];
    }

    $accent_over = get_theme_mod('gp_accent_override', '');

    $logo_h  = get_theme_mod('gp_logo_height', 40);
    $left_w  = get_theme_mod('gp_sidebar_left_width', 220);
    $right_w = get_theme_mod('gp_sidebar_right_width', 280);

    // Per-tag overrides — only non-empty values generate CSS
    $tags_map = [
        'body'  => 'body',
        'h1'    => 'h1',
        'h2'    => 'h2',
        'h3'    => 'h3',
        'h4'    => 'h4',
        'small' => 'small,.text-small',
        'xs'    => '.text-xs,.entry-meta,.widget-title,.footer-copy',
    ];
    $prop_map = [
        'fs' => ['font-size',       'rem'],
        'fw' => ['font-weight',     ''],
        'fi' => ['font-style',      ''],
        'lh' => ['line-height',     ''],
        'ls' => ['letter-spacing',  'em'],
        'tt' => ['text-transform',  ''],
    ];
    $overrides = '';
    foreach ($tags_map as $tag => $selector) {
        $rules = '';
        foreach ($prop_map as $prop => $cfg) {
            $val = get_theme_mod('gp_t_' . $tag . '_' . $prop, '');
            if ($val === '' || $val === null) continue;
            $unit = (is_numeric($val) && $cfg[1]) ? $cfg[1] : '';
            $rules .= $cfg[0] . ':' . $val . $unit . ';';
        }
        if ($rules) {
            $overrides .= $selector . '{' . $rules . '}' . "\n";
        }
    }
    ?>
    <style id="gp-customizer-css">
    :root{
      --gp-base:<?php echo $scheme['base']; ?>;
      --gp-surface:<?php echo $scheme['surface']; ?>;
      --gp-surface2:<?php echo $scheme['surface2']; ?>;
      --gp-border:<?php echo $scheme['border']; ?>;
      --gp-border2:<?php echo $scheme['border2']; ?>;
      --gp-text:<?php echo $scheme['text']; ?>;
      --gp-text2:<?php echo $scheme['text2']; ?>;
      --gp-textmuted:<?php echo $scheme['textmuted']; ?>;
      --gp-textfaint:<?php echo $scheme['textfaint']; ?>;
      --gp-accent:<?php echo $accent_over ?: $scheme['accent']; ?>;
      --gp-accentdim:<?php echo $scheme['accentdim']; ?>;
      --gp-accentbg:<?php echo $scheme['accentbg']; ?>;
      --gp-font-heading:<?php echo $typo['heading']; ?>;
      --gp-font-body:<?php echo $typo['body']; ?>;
      --gp-font-ui:<?php echo $typo['ui']; ?>;
      --gp-font-mono:<?php echo $typo['mono']; ?>;
      --gp-h-style:<?php echo $typo['h_style']; ?>;
      --gp-h-weight:<?php echo $typo['h_weight']; ?>;
      --gp-logo-height:<?php echo intval($logo_h); ?>px;
      --gp-sidebar-left:<?php echo intval($left_w); ?>px;
      --gp-sidebar-right:<?php echo intval($right_w); ?>px;
      /* Typography sizes — original Gipetau values */
      --gp-fs-body:16px;
      --gp-fs-h1:26px;
      --gp-fs-h2:22px;
      --gp-fs-h3:18px;
      --gp-fs-h4:16px;
      --gp-fs-small:13px;
      --gp-fs-xs:9px;
      --gp-lh-body:1.6;
      --gp-lh-heading:1.2;
    }
    body{background:var(--gp-base);color:var(--gp-text);font-family:var(--gp-font-body);font-size:var(--gp-fs-body);line-height:var(--gp-lh-body)}
    h1,h2,h3,h4,h5,h6{font-family:var(--gp-font-heading);font-style:var(--gp-h-style);font-weight:var(--gp-h-weight);line-height:var(--gp-lh-heading)}
    h1{font-size:var(--gp-fs-h1)}h2{font-size:var(--gp-fs-h2)}h3{font-size:var(--gp-fs-h3)}h4{font-size:var(--gp-fs-h4)}
    a{color:var(--gp-accent)}
    <?php echo $overrides; ?>
    </style>
    <?php
}, 20);

// ══════════════════════════════════════════
// OPTIMIZED FONT LOADING — only load what preset needs
// ══════════════════════════════════════════
function gp_get_google_fonts_url() {
    $typo_key = get_theme_mod('gp_typo_preset', 'gipetau');

    if ($typo_key === 'custom') {
        $google = get_theme_mod('gp_custom_google', '');
        if (empty($google)) return '';
        return 'https://fonts.googleapis.com/css2?family=' . $google . '&display=swap';
    }

    $typos = gp_typography_presets();
    $typo  = $typos[$typo_key] ?? $typos['gipetau'];
    if (empty($typo['google'])) return '';
    return 'https://fonts.googleapis.com/css2?family=' . $typo['google'] . '&display=swap';
}

// ══════════════════════════════════════════
// Customizer preview JS
// ══════════════════════════════════════════
add_action('customize_preview_init', function(){
    wp_enqueue_script('gp-customizer-preview', GP_URI . '/assets/js/customizer-preview.js', ['customize-preview'], GP_VERSION, true);
});

// ══════════════════════════════════════════
// Customizer PANEL JS — range values + custom preset toggle
// ══════════════════════════════════════════
add_action('customize_controls_enqueue_scripts', function(){
    $custom_ids = ['gp_custom_heading','gp_custom_body','gp_custom_ui','gp_custom_mono','gp_custom_google'];
    wp_add_inline_script('customize-controls', '
    jQuery(function($){
        var customIds = ' . json_encode($custom_ids) . ';
        function toggleCustom(val){
            customIds.forEach(function(id){
                var ctrl = wp.customize.control(id);
                if(ctrl) ctrl.container.toggle(val === "custom");
            });
        }
        wp.customize("gp_typo_preset", function(setting){
            setting.bind(toggleCustom);
            wp.customize.section("gp_typography", function(sec){
                sec.expanded.bind(function(expanded){ if(expanded) toggleCustom(setting.get()); });
            });
        });
    });
    ');
});
