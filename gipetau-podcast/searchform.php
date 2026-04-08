<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
  <input type="search" name="s" placeholder="<?php esc_attr_e('Поиск…', 'gipetau-podcast'); ?>" value="<?php echo get_search_query(); ?>">
  <button type="submit"><?php esc_html_e('Найти', 'gipetau-podcast'); ?></button>
</form>
