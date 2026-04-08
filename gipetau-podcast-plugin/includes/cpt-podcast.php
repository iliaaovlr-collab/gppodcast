<?php
/**
 * Custom Post Type: podcast_episode
 */
defined('ABSPATH') || exit;

add_action('init', 'gpp_register_podcast_cpt');

function gpp_register_podcast_cpt() {
    register_post_type('podcast_episode', [
        'labels' => [
            'name'               => 'Подкасты',
            'singular_name'      => 'Выпуск',
            'add_new'            => 'Добавить выпуск',
            'add_new_item'       => 'Новый выпуск',
            'edit_item'          => 'Редактировать выпуск',
            'view_item'          => 'Смотреть выпуск',
            'all_items'          => 'Все выпуски',
            'search_items'       => 'Найти выпуск',
            'not_found'          => 'Выпусков не найдено',
            'not_found_in_trash' => 'В корзине пусто',
            'menu_name'          => 'Подкасты',
        ],
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'episodes', 'with_front' => false],
        'menu_icon'          => 'dashicons-microphone',
        'menu_position'      => 5,
        'supports'           => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
        'show_in_rest'       => true,
        'capability_type'    => 'post',
    ]);

    // Таксономия «Сезоны»
    register_taxonomy('podcast_season', 'podcast_episode', [
        'labels' => [
            'name'          => 'Сезоны',
            'singular_name' => 'Сезон',
            'add_new_item'  => 'Добавить сезон',
        ],
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'season'],
        'show_in_rest' => true,
    ]);
}

// Колонки в админке
add_filter('manage_podcast_episode_posts_columns', function($cols) {
    $new = [];
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['episode_num'] = 'S/E';
            $new['duration']    = 'Длит.';
            $new['rss_sync']    = 'RSS';
        }
    }
    return $new;
});

add_action('manage_podcast_episode_posts_custom_column', function($col, $id) {
    switch ($col) {
        case 'episode_num':
            $s = get_post_meta($id, '_gpp_season', true);
            $e = get_post_meta($id, '_gpp_episode', true);
            echo $s ? "S{$s}E{$e}" : "#{$e}";
            break;
        case 'duration':
            $d = get_post_meta($id, '_gpp_duration', true);
            if ($d) echo gpp_format_duration($d);
            break;
        case 'rss_sync':
            $guid = get_post_meta($id, '_gpp_guid', true);
            echo $guid ? '✓' : '—';
            break;
    }
}, 10, 2);

function gpp_format_duration($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return $h ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}
