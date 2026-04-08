<?php
/**
 * Front page — uses same display settings as content part
 */
get_header(); ?>

<div class="content-area">
  <?php if (have_posts()): ?>
    <?php while (have_posts()): the_post(); ?>
      <?php
      // Static front page → render as page
      if (is_page()) {
          echo '<article class="entry">';
          if (has_post_thumbnail() && gp_show('thumbnail')) echo '<div class="entry-thumbnail">' . get_the_post_thumbnail(null, 'gp-wide') . '</div>';
          echo '<div class="entry-content">';
          the_content();
          echo '</div></article>';
      } else {
          // Blog front page → use content part with display settings
          get_template_part('parts/content', get_post_type());
      }
      ?>
    <?php endwhile; ?>
    <?php the_posts_pagination(['mid_size' => 2, 'prev_text' => '←', 'next_text' => '→']); ?>
  <?php else: ?>
    <?php get_template_part('parts/content', 'none'); ?>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
