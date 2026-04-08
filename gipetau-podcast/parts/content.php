<?php
/**
 * Post card — respects Customizer display settings
 * Used in: front-page.php, index.php, archive.php
 */
$mode = gp_display_mode();
?>
<article <?php post_class('entry'); ?>>

  <?php if (gp_show('thumbnail') && has_post_thumbnail()): ?>
    <div class="entry-thumbnail">
      <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('post-thumbnail'); ?></a>
    </div>
  <?php endif; ?>

  <?php if (gp_show('title')): ?>
    <header class="entry-header">
      <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    </header>
  <?php endif; ?>

  <?php if (gp_show('date') || gp_show('author') || gp_show('categories') || gp_show('comments')): ?>
    <div class="entry-meta">
      <?php if (gp_show('date')): ?>
        <span class="meta-date"><?php echo esc_html(get_the_date()); ?></span>
      <?php endif; ?>
      <?php if (gp_show('author')): ?>
        <span class="meta-author"><?php the_author(); ?></span>
      <?php endif; ?>
      <?php if (gp_show('categories') && has_category()): ?>
        <span class="meta-cat"><?php the_category(', '); ?></span>
      <?php endif; ?>
      <?php if (gp_show('comments') && get_comments_number()): ?>
        <span class="meta-comments"><?php comments_number('0', '1', '%'); ?> 💬</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($mode !== 'title'): ?>
    <div class="entry-content">
      <?php if ($mode === 'full'): ?>
        <?php the_content(); ?>
      <?php else: ?>
        <?php the_excerpt(); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (gp_show('tags') && has_tag()): ?>
    <div class="entry-tags"><?php the_tags('<span>', '</span><span>', '</span>'); ?></div>
  <?php endif; ?>

</article>
