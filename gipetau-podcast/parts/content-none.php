<div class="no-results">
  <h1 class="entry-title"><?php esc_html_e('Ничего не найдено', 'gipetau-podcast'); ?></h1>
  <?php if (is_search()): ?>
    <p style="color:var(--wp--preset--color--text-muted)"><?php esc_html_e('Попробуйте другой запрос.', 'gipetau-podcast'); ?></p>
    <?php get_search_form(); ?>
  <?php else: ?>
    <p style="color:var(--wp--preset--color--text-muted)"><?php esc_html_e('Здесь пока пусто.', 'gipetau-podcast'); ?></p>
  <?php endif; ?>
</div>
