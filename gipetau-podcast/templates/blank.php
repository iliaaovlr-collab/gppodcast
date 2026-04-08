<?php
/**
 * Template Name: Blank (No Header/Footer)
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php while (have_posts()): the_post(); ?>
  <div class="entry-content"><?php the_content(); ?></div>
<?php endwhile; ?>
<?php wp_footer(); ?>
</body>
</html>
