<article <?php post_class('entry'); ?>>
  <header class="entry-header">
    <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php gp_entry_meta(); ?>
  </header>
  <div class="entry-content"><?php the_excerpt(); ?></div>
</article>
