<?php
/**
 * Single post — respects Customizer display settings
 */
get_header();
$mode = gp_display_mode();
$has_sidebar = is_active_sidebar('sidebar-1');
?>

<div class="<?php echo $has_sidebar ? 'has-sidebar' : 'content-area'; ?>">
  <div class="primary">
    <?php while (have_posts()): the_post(); ?>
      <article <?php post_class('entry'); ?>>

        <?php if (gp_show('title')): ?>
          <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>
          </header>
        <?php endif; ?>

        <?php if (gp_show('date') || gp_show('author') || gp_show('categories') || gp_show('comments')): ?>
          <div class="entry-meta">
            <?php if (gp_show('date')): ?><span><?php echo esc_html(get_the_date()); ?></span><?php endif; ?>
            <?php if (gp_show('author')): ?><span><?php the_author(); ?></span><?php endif; ?>
            <?php if (gp_show('categories') && has_category()): ?><span><?php the_category(', '); ?></span><?php endif; ?>
            <?php if (gp_show('comments') && get_comments_number()): ?><span><?php comments_number('0', '1', '%'); ?> 💬</span><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (gp_show('thumbnail') && has_post_thumbnail()): ?>
          <div class="entry-thumbnail"><?php the_post_thumbnail('gp-wide'); ?></div>
        <?php endif; ?>

        <div class="entry-content">
          <?php if ($mode === 'excerpt'): the_excerpt(); else: the_content(); endif; ?>
        </div>

        <?php if (gp_show('tags') && has_tag()): ?>
          <div class="entry-tags"><?php the_tags('<span>', '</span><span>', '</span>'); ?></div>
        <?php endif; ?>

        <?php do_action('gp_after_content', get_the_ID()); ?>
        <?php if (comments_open() || get_comments_number()): comments_template(); endif; ?>

      </article>
    <?php endwhile; ?>
  </div>
  <?php if ($has_sidebar): get_sidebar(); endif; ?>
</div>

<?php get_footer(); ?>
