<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class('site'); ?>>
<?php wp_body_open(); ?>

<?php
$layout     = get_theme_mod('gp_layout_type', 'three-column');
$show_title = get_theme_mod('gp_show_title', true);
$show_tag   = get_theme_mod('gp_show_tagline', true);
$logo_h     = get_theme_mod('gp_logo_height', 40);
$logo_ret   = get_theme_mod('gp_logo_retina', '');
?>

<?php if ($layout === 'three-column'): ?>
<!-- ═══ THREE-COLUMN LAYOUT ═══ -->
<div class="gp-layout gp-layout--three">

  <!-- LEFT PANEL -->
  <aside class="gp-panel gp-panel--left">
    <div class="gp-panel-inner">
      <!-- Brand -->
      <div class="site-brand">
        <?php if (has_custom_logo() || $logo_ret): ?>
          <div class="site-brand-logo">
            <?php if ($logo_ret):
              $logo_1x = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');
            ?>
              <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url($logo_1x ?: $logo_ret); ?>"
                     srcset="<?php echo esc_url($logo_ret); ?> 2x"
                     alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                     style="height:var(--gp-logo-height);width:auto">
              </a>
            <?php else: the_custom_logo(); endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($show_title && get_bloginfo('name')): ?>
          <div class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></div>
        <?php endif; ?>
        <?php if ($show_tag && get_bloginfo('description')): ?>
          <div class="site-description"><?php bloginfo('description'); ?></div>
        <?php endif; ?>
      </div>

      <!-- Navigation -->
      <?php if (has_nav_menu('primary')):
        wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'gp-nav', 'depth' => 2]);
      endif; ?>

      <!-- Left widget area -->
      <?php if (is_active_sidebar('sidebar-left')): ?>
        <div class="gp-widgets-left"><?php dynamic_sidebar('sidebar-left'); ?></div>
      <?php endif; ?>

      <div class="gp-panel-footer">
        <div class="gp-theme-switch">
          <button class="gp-ts-btn gp-ts-dark" data-theme="dark" title="Гипетау">☾</button>
          <button class="gp-ts-btn gp-ts-light" data-theme="light" title="Уайт">☀</button>
          <button class="gp-ts-btn gp-ts-contrast" data-theme="contrast" title="Контраст+">◐</button>
        </div>
        <?php gp_copyright(); ?>
      </div>
    </div>
  </aside>

  <!-- CENTER -->
  <div class="gp-center">
    <!-- Mobile header -->
    <header class="gp-mobile-header">
      <div class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></div>
      <button class="nav-toggle" aria-label="<?php esc_attr_e('Меню', 'gipetau-podcast'); ?>" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <!-- Mobile nav overlay -->
      <nav class="main-nav" role="navigation">
        <button class="nav-close" aria-label="<?php esc_attr_e('Закрыть', 'gipetau-podcast'); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        <?php if (has_nav_menu('primary')): wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'depth' => 2]); endif; ?>
      </nav>
    </header>

    <?php if (is_active_sidebar('before-content')): ?>
      <div class="gp-before-content"><?php dynamic_sidebar('before-content'); ?></div>
    <?php endif; ?>

    <main class="site-content" role="main">

<?php else: ?>
<!-- ═══ STANDARD LAYOUTS (1-col, 2-col) ═══ -->
<header class="site-header" role="banner">
  <div class="wide-area">
    <div class="site-brand">
      <?php if (has_custom_logo() || $logo_ret): ?>
        <div class="site-brand-logo">
          <?php if ($logo_ret):
            $logo_1x = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');
          ?>
            <a href="<?php echo esc_url(home_url('/')); ?>">
              <img src="<?php echo esc_url($logo_1x ?: $logo_ret); ?>"
                   srcset="<?php echo esc_url($logo_ret); ?> 2x"
                   alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                   style="height:var(--gp-logo-height);width:auto">
            </a>
          <?php else: the_custom_logo(); endif; ?>
        </div>
      <?php endif; ?>
      <div class="site-brand-text">
        <?php if ($show_title && get_bloginfo('name')): ?>
          <div class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></div>
        <?php endif; ?>
        <?php if ($show_tag && get_bloginfo('description')): ?>
          <div class="site-description"><?php bloginfo('description'); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (has_nav_menu('primary')): ?>
    <button class="nav-toggle" aria-label="<?php esc_attr_e('Меню', 'gipetau-podcast'); ?>" aria-expanded="false">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <nav class="main-nav" role="navigation">
      <button class="nav-close" aria-label="<?php esc_attr_e('Закрыть', 'gipetau-podcast'); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'depth' => 2]); ?>
    </nav>
    <?php endif; ?>
  </div>
</header>

<main class="site-content" role="main">
<?php endif; ?>
