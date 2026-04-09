<?php
/**
 * Single podcast episode
 */
get_header();

$season    = get_post_meta(get_the_ID(), '_gpp_season', true);
$episode   = get_post_meta(get_the_ID(), '_gpp_episode', true);
$prev = get_adjacent_post(false, '', true);
$next = get_adjacent_post(false, '', false);
?>

<?php while (have_posts()): the_post(); ?>
<?php
$ep_audio = get_post_meta(get_the_ID(), '_gpp_audio_url', true);
$ep_dur = intval(get_post_meta(get_the_ID(), '_gpp_duration', true));
$ep_se = ($season ? "S{$season}E{$episode}" : "#{$episode}");
$ep_cover = gpp_episode_cover(get_the_ID());
?>
  <article <?php post_class('entry'); ?>
    data-episode-audio="<?php echo esc_attr($ep_audio); ?>"
    data-episode-title="<?php echo esc_attr(get_the_title()); ?>"
    data-episode-se="<?php echo esc_attr($ep_se); ?>"
    data-episode-cover="<?php echo esc_attr($ep_cover); ?>"
    data-episode-duration="<?php echo $ep_dur; ?>" data-episode-url="<?php the_permalink(); ?>"
  >

    <?php if (gp_show('title')): ?>
      <header class="entry-header">
        <h1 class="entry-title"><?php the_title(); ?></h1>
        <?php if ($season || $episode): ?>
          <div class="entry-meta">
            <?php if ($season): ?><span>Сезон <?php echo esc_html($season); ?></span><?php endif; ?>
            <?php if ($episode): ?><span>Выпуск <?php echo esc_html($episode); ?></span><?php endif; ?>
            <?php if (gp_show('date')): ?><span><?php echo esc_html(get_the_date()); ?></span><?php endif; ?>
            <?php if ($ep_dur): ?><span><?php echo esc_html(gpp_format_duration($ep_dur)); ?></span><?php endif; ?>
          </div>
        <?php endif; ?>
      </header>
    <?php endif; ?>

    <?php if ($ep_audio): ?>
    <div class="gpp-player" id="bottomPlayer">
      <div class="gpp-player-row">
        <button class="bp-btn bp-sm" onclick="gpSeek(-15)" title="−15 сек">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        </button>
        <button class="bp-btn bp-play" id="playBtn" onclick="gpToggle()">
          <svg id="iconPlay" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
          <svg id="iconPause" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display:none"><rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/></svg>
        </button>
        <button class="bp-btn bp-sm" onclick="gpSeek(15)" title="+15 сек">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.13-9.36L23 10"/></svg>
        </button>
        <span class="gpp-player-time" id="mkCur">0:00</span>
        <div class="bp-prog" id="bpProgress">
          <div class="bp-prog-fill" id="pFill"></div>
          <div class="bp-prog-thumb" id="pThumb"></div>
          <div class="bp-marker bp-marker-hover" id="mkHover">0:00</div>
        </div>
        <span class="gpp-player-time" id="mkEnd"><?php echo esc_html(gpp_format_duration($ep_dur)); ?></span>
        <button class="bp-speed" id="speedBtn" onclick="gpSpeed()">1x</button>
        <div class="bp-vol" id="bpVol">
          <button class="bp-vol-btn" onclick="gpMute()">
            <svg id="iconVolOn" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
            <svg id="iconVolOff" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
          </button>
          <div class="bp-vol-popup"><input class="bp-vol-range" type="range" min="0" max="100" value="80" id="volRange" oninput="gpVol(this.value)"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php // Mobile: cover before player (hidden on desktop) ?>
    <?php $mob_cover = gpp_episode_cover(get_the_ID(), 'medium'); if ($mob_cover): ?>
      <div class="gpp-mobile-cover"><img src="<?php echo esc_url($mob_cover); ?>" alt=""></div>
    <?php endif; ?>

    <?php // Mobile share — right after cover ?>
    <div class="gpp-share gpp-mobile-share">
      <?php $share_url = urlencode(get_permalink()); $share_title = urlencode(get_the_title()); ?>
      <span class="gpp-share-label">Поделиться:</span>
      <a href="https://t.me/share/url?url=<?php echo $share_url;?>&text=<?php echo $share_title;?>" target="_blank" rel="noopener" class="gpp-share-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 1 0 24 12.056A12.01 12.01 0 0 0 11.944 0Zm5.654 8.22-1.96 9.22c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.566-4.458c.537-.194 1.006.13.826.998Z"/></svg></a>
      <a href="https://vk.com/share.php?url=<?php echo $share_url;?>&title=<?php echo $share_title;?>" target="_blank" rel="noopener" class="gpp-share-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.77 19.15h1.33s.4-.04.6-.26c.19-.2.18-.56.18-.56s-.03-1.71.77-1.96c.78-.25 1.79 1.66 2.86 2.39.81.55 1.42.43 1.42.43l2.86-.04s1.5-.09.79-1.27c-.06-.1-.42-.88-2.15-2.48-1.82-1.68-1.57-1.41.61-4.32 1.33-1.77 1.86-2.85 1.7-3.31-.16-.44-1.13-.32-1.13-.32l-3.22.02s-.24-.03-.42.07c-.17.1-.28.35-.28.35s-.51 1.35-1.18 2.5c-1.43 2.43-2 2.56-2.23 2.41-.55-.36-.41-1.43-.41-2.2 0-2.38.36-3.38-.71-3.64-.36-.09-.62-.14-1.53-.15-.16 0-1.17 0-1.17 0s-.69.02-1.04.36c-.31.3 0 .36 0 .36s.94.18 1.28 1.64c.11.45.05 2.88.05 2.88s-.64 2.38-1.98-.79A21.7 21.7 0 016.3 7.8s-.17-.41-.47-.63c-.37-.27-.89-.36-.89-.36L1.86 6.84s-.55.02-.75.26c-.18.21 0 .65 0 .65s2.37 5.55 5.05 8.35c2.46 2.57 5.25 2.4 5.25 2.4h1.37z"/></svg></a>
      <a href="https://connect.ok.ru/offer?url=<?php echo $share_url;?>" target="_blank" rel="noopener" class="gpp-share-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm4.47 8.38a7.06 7.06 0 0 1-3.22 1.14l2.88 2.87a1.25 1.25 0 1 1-1.77 1.77L12 16.3l-2.36 2.36a1.25 1.25 0 1 1-1.77-1.77l2.88-2.87a7.06 7.06 0 0 1-3.22-1.14 1.25 1.25 0 1 1 1.4-2.08 4.56 4.56 0 0 0 5.14 0 1.25 1.25 0 1 1 1.4 2.08z"/></svg></a>
    </div>

    <div class="entry-content">
      <?php the_content(); ?>
    </div>

    <nav class="gpp-episode-nav">
      <?php if ($prev): ?>
        <a class="gpp-nav-prev" href="<?php echo get_permalink($prev); ?>">← <?php echo esc_html($prev->post_title); ?></a>
      <?php endif; ?>
      <?php if ($next): ?>
        <a class="gpp-nav-next" href="<?php echo get_permalink($next); ?>"><?php echo esc_html($next->post_title); ?> →</a>
      <?php endif; ?>
    </nav>

    <?php if (comments_open() && is_user_logged_in()): ?>
    <div class="gpp-comment-form-center">
      <div class="gpp-cf-row">
        <textarea id="gppCommentText" placeholder="Написать комментарий…" rows="2"></textarea>
        <input type="hidden" id="gppAudioTime" value="">
        <button class="gpp-cf-send" id="gppSendBtn" onclick="gppSendComment()">→</button>
      </div>
      <div class="gpp-cf-status" id="gppCfStatus"></div>
    </div>
    <?php endif; ?>

    <?php // Mobile: comments (hidden on desktop, shown on mobile) ?>
    <!-- Mobile comments -->
    <div class="gpp-mobile-comments">
      <h3 class="gpp-sidebar-title">Комментарии</h3>
      <?php
      $comments = get_comments([
          'post_id' => get_the_ID(), 'status' => 'approve',
          'number' => 50, 'orderby' => 'comment_date', 'order' => 'DESC',
      ]);
      if ($comments): ?>
        <div class="gpp-comments-list" id="gppMobileComments">
        <?php foreach ($comments as $c):
            $at = get_comment_meta($c->comment_ID, '_gpp_audio_time', true);
        ?>
          <div class="gpp-comment" <?php if ($at !== '' && $at !== false) echo 'data-time="' . intval($at) . '"'; ?>>
            <span class="gpp-comment-author"><?php echo esc_html($c->comment_author); ?></span>
            <span class="gpp-comment-time">[<?php echo human_time_diff(strtotime($c->comment_date), current_time('timestamp')); ?> назад]</span>
            <div class="gpp-comment-text"><?php echo esc_html($c->comment_content); ?></div>
          </div>
        <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="gpp-no-comments">Пока нет комментариев.</p>
      <?php endif; ?>
    </div>

    <?php do_action('gp_after_content', get_the_ID()); ?>

  </article>
<?php endwhile; ?>

<script>
(function(){
  // AJAX comment
  window.gppSendComment = function(){
    var text = document.getElementById('gppCommentText');
    var btn = document.getElementById('gppSendBtn');
    var status = document.getElementById('gppCfStatus');
    var timeInput = document.getElementById('gppAudioTime');
    if (!text || !text.value.trim()) return;

    btn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'gpp_comment');
    fd.append('nonce', gppAjax.nonce);
    fd.append('comment_post_ID', '<?php echo get_the_ID(); ?>');
    fd.append('comment', text.value.trim());
    fd.append('gpp_audio_time', timeInput ? timeInput.value : '');

    fetch(gppAjax.url, {method:'POST', body:fd})
      .then(function(r){ return r.json(); })
      .then(function(resp){
        if (resp.success) {
          text.value = '';
          gppAjax.nonce = resp.data.newNonce;
          var list = document.getElementById('gppCommentsList');
          if (list) list.insertAdjacentHTML('afterbegin', resp.data.html);
          var mlist = document.getElementById('gppMobileComments');
          if (mlist) mlist.insertAdjacentHTML('afterbegin', resp.data.html);
          if (status) { status.textContent = 'Отправлено'; setTimeout(function(){ status.textContent=''; }, 3000); }
        } else {
          if (status) { status.textContent = resp.data || 'Ошибка'; setTimeout(function(){ status.textContent=''; }, 3000); }
        }
      })
      .catch(function(){ if (status) status.textContent = 'Ошибка сети'; })
      .finally(function(){ btn.disabled = false; });
  };
})();
</script>

<?php get_footer(); ?>
