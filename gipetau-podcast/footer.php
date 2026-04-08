<?php $layout = get_theme_mod('gp_layout_type', 'three-column'); ?>

<?php if ($layout === 'three-column'): ?>

    </main><!-- .site-content -->

    <?php if (is_active_sidebar('after-content')): ?>
      <div class="gp-after-content"><?php dynamic_sidebar('after-content'); ?></div>
    <?php endif; ?>

  </div><!-- .gp-center -->

  <!-- RIGHT PANEL -->
  <aside class="gp-panel gp-panel--right">
    <div class="gp-panel-inner">
      <?php do_action('gp_right_panel'); ?>
      <?php if (is_active_sidebar('sidebar-right')): ?>
        <?php dynamic_sidebar('sidebar-right'); ?>
      <?php endif; ?>
    </div>
  </aside>

</div><!-- .gp-layout--three -->

<!-- Mobile footer (hidden on desktop) -->
<footer class="gp-mobile-footer">
  <div class="gp-theme-switch">
    <button class="gp-ts-btn gp-ts-dark" data-theme="dark" title="Гипетау">☾</button>
    <button class="gp-ts-btn gp-ts-light" data-theme="light" title="Уайт">☀</button>
    <button class="gp-ts-btn gp-ts-contrast" data-theme="contrast" title="Контраст+">◐</button>
  </div>
  <?php gp_copyright(); ?>
</footer>

<?php else: ?>

</main><!-- .site-content -->

<footer class="site-footer" role="contentinfo">
  <div class="wide-area">
    <?php gp_copyright(); ?>
    <?php if (has_nav_menu('footer')): ?>
      <nav class="footer-nav"><?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'depth' => 1, 'menu_class' => 'footer-menu']); ?></nav>
    <?php endif; ?>
  </div>
</footer>

<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
