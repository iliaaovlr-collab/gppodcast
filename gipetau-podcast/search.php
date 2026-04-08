<?php get_header(); ?>
<div class="content-area">
  <header class="entry-header">
    <h1 class="entry-title"><?php printf(esc_html__('Результаты: «%s»', 'gipetau-podcast'), get_search_query()); ?></h1>
  </header>
  <?php if (have_posts()): ?>
    <?php while (have_posts()): the_post(); get_template_part('parts/content', 'search'); endwhile; ?>
    <?php the_posts_pagination(['mid_size' => 2]); ?>
  <?php else: ?>
    <div class="no-results">
      <p style="color:var(--wp--preset--color--text-muted)">Ничего не найдено. Попробуйте другой запрос.</p>
      <?php get_search_form(); ?>
    </div>
  <?php endif; ?>
</div>
<?php get_footer(); ?>
