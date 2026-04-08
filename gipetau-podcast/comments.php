<?php
if (post_password_required()) return;
?>
<section class="comments-area" id="comments">
  <?php if (have_comments()): ?>
    <h2 class="comments-title">
      <?php printf(_n('%d комментарий', '%d комментариев', get_comments_number(), 'gipetau-podcast'), get_comments_number()); ?>
    </h2>
    <ol class="comment-list">
      <?php wp_list_comments(['callback' => 'gp_comment_callback', 'style' => 'li', 'short_ping' => true]); ?>
    </ol>
    <?php the_comments_pagination(['prev_text' => '←', 'next_text' => '→']); ?>
  <?php endif; ?>

  <?php
  // Plugins can completely replace the form via filter
  $custom_form = apply_filters('gp_comment_form', null);
  if ($custom_form) {
      echo $custom_form;
  } else {
      comment_form([
          'title_reply'        => __('Оставить комментарий', 'gipetau-podcast'),
          'title_reply_before' => '<h3 class="comments-title">',
          'title_reply_after'  => '</h3>',
          'comment_notes_before' => '',
      ]);
  }
  ?>
</section>
