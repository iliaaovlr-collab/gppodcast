<?php
/**
 * Template Name: With Sidebar
 */
get_header(); ?>
<div class="has-sidebar">
  <div class="primary">
    <?php while (have_posts()): the_post(); ?>
      <article <?php post_class('entry'); ?>>
        <header class="entry-header"><h1 class="entry-title"><?php the_title(); ?></h1></header>
        <div class="entry-content"><?php the_content(); ?></div>
        <?php do_action('gp_after_content', get_the_ID()); ?>
      </article>
    <?php endwhile; ?>
  </div>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
