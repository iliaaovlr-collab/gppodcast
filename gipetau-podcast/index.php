<?php get_header(); ?>

<div class="content-area">
  <?php if (have_posts()): ?>
    <?php while (have_posts()): the_post(); ?>
      <?php get_template_part('parts/content', get_post_type()); ?>
    <?php endwhile; ?>
    <?php the_posts_pagination(['mid_size' => 2, 'prev_text' => '←', 'next_text' => '→']); ?>
  <?php else: ?>
    <?php get_template_part('parts/content', 'none'); ?>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
