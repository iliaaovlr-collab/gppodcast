<?php
get_header();
$is_season = is_tax('podcast_season');

$all_eps = get_posts(['post_type'=>'podcast_episode','posts_per_page'=>-1,'post_status'=>'publish','orderby'=>'date','order'=>'DESC','fields'=>'ids']);
$all_data = [];
foreach ($all_eps as $eid) {
    $s_terms = wp_get_post_terms($eid, 'podcast_season');
    $s_url = (!empty($s_terms) && !is_wp_error($s_terms)) ? get_term_link($s_terms[0]) : '';
    $all_data[] = [
        'src'   => get_post_meta($eid, '_gpp_audio_url', true),
        'title' => get_the_title($eid),
        'se'    => (get_post_meta($eid,'_gpp_season',true) ? 'S'.get_post_meta($eid,'_gpp_season',true).'E'.get_post_meta($eid,'_gpp_episode',true) : '#'.get_post_meta($eid,'_gpp_episode',true)),
        'cover' => gpp_episode_cover($eid),
        'dur'   => intval(get_post_meta($eid, '_gpp_duration', true)),
        'tc'    => json_decode(get_post_meta($eid, '_gpp_timecodes', true) ?: '[]', true),
        'season_url' => is_string($s_url) ? $s_url : '',
    ];
}

if ($is_season) {
    $eps = new WP_Query(['post_type'=>'podcast_episode','posts_per_page'=>-1,'post_status'=>'publish',
        'tax_query'=>[['taxonomy'=>'podcast_season','field'=>'term_id','terms'=>get_queried_object_id()]],
        'orderby'=>'date','order'=>'DESC']);
} else {
    $eps = new WP_Query(['post_type'=>'podcast_episode','posts_per_page'=>10,'post_status'=>'publish',
        'orderby'=>'date','order'=>'DESC','paged'=>1]);
}
$total_pages = $eps->max_num_pages;
$seasons = get_terms(['taxonomy'=>'podcast_season','hide_empty'=>true,'orderby'=>'name']);
?>

<div class="gpp-archive-header">
  <?php if ($is_season): ?>
    <h1 class="gpp-archive-title"><?php single_term_title(); ?></h1>
  <?php else: ?>
    <h1 class="gpp-archive-title">Все выпуски</h1>
  <?php endif; ?>
</div>

<?php do_action('gpp_archive_top'); ?>

<!-- 2. Prominent controls -->
<div class="gpp-list-controls">
  <button class="gpp-lc-main" id="gppPlayAll" title="Воспроизвести">
    <svg id="gppPlayAllIcon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
  </button>
  <button class="gpp-lc-main gpp-lc-toggle" id="gppShuffle" title="Случайный">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
  </button>
  <button class="gpp-lc-main gpp-lc-toggle" id="gppLoop" title="Зациклить">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
  </button>
</div>

<?php if ($seasons && !is_wp_error($seasons) && count($seasons) > 1): ?>
<div class="gpp-season-nav">
  <span class="gpp-season-label">Сезоны:</span>
  <a href="<?php echo get_post_type_archive_link('podcast_episode'); ?>" class="gpp-season-num <?php echo !$is_season ? 'gpp-season-active' : ''; ?>" data-season-click>все</a>
  <?php
  $total = count($seasons);
  $cur_idx = -1;
  foreach ($seasons as $i => $term) {
      if ($is_season && get_queried_object_id() === $term->term_id) $cur_idx = $i;
  }
  $last_was_dots = false;
  foreach ($seasons as $i => $term):
      $num = preg_replace('/[^0-9]/', '', $term->name) ?: ($i + 1);
      $show = ($i < 2 || $i >= $total - 2 || abs($i - $cur_idx) <= 1);
      if (!$show) {
          if (!$last_was_dots) { echo '<span class="gpp-season-dots">…</span>'; $last_was_dots = true; }
          continue;
      }
      $last_was_dots = false;
  ?>
    <a href="<?php echo get_term_link($term); ?>" class="gpp-season-num <?php echo ($is_season && get_queried_object_id() === $term->term_id) ? 'gpp-season-active' : ''; ?>" data-season-click><?php echo esc_html($num); ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($eps->have_posts()): ?>
<div class="gpp-episodes-table" id="gppEpList">
  <?php while ($eps->have_posts()): $eps->the_post();
    $id=get_the_ID(); $dur=intval(get_post_meta($id,'_gpp_duration',true));
    $audio_url=get_post_meta($id,'_gpp_audio_url',true);
    $s=get_post_meta($id,'_gpp_season',true); $e=get_post_meta($id,'_gpp_episode',true);
    $se=$s?"S{$s}E{$e}":"#{$e}";
    $date=get_the_date('j M'); if(get_the_date('Y')!==date('Y'))$date=get_the_date('j M Y');
    $cover=gpp_episode_cover($id); $tc_json=get_post_meta($id,'_gpp_timecodes',true)?:'[]';
  ?>
  <div class="gpp-ep-row" data-episode-audio="<?php echo esc_attr($audio_url);?>"
       data-episode-title="<?php echo esc_attr(get_the_title());?>"
       data-episode-se="<?php echo esc_attr($se);?>"
       data-episode-cover="<?php echo esc_attr($cover);?>"
       data-episode-duration="<?php echo $dur;?>"
       data-episode-url="<?php the_permalink();?>" data-episode-tc='<?php echo esc_attr($tc_json);?>'>
    <div class="gpp-ep-cover" onclick="gppListPlay(this.closest('.gpp-ep-row'))">
      <?php if($cover):?><img src="<?php echo esc_url($cover);?>" alt=""><?php else:?><div class="gpp-ep-cover-ph">🎙</div><?php endif;?>
      <svg class="gpp-ep-cover-ring" viewBox="0 0 48 48"><circle cx="24" cy="24" r="22" class="gpp-ep-ring-bg"/><circle cx="24" cy="24" r="22" class="gpp-ep-ring-fill"/></svg>
      <span class="gpp-ep-play-icon" data-src="<?php echo esc_attr($audio_url);?>"><svg class="gpp-epi-play" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="6,3 20,12 6,21"/></svg><svg class="gpp-epi-pause" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:none"><rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/></svg></span>
    </div>
    <div class="gpp-ep-info">
      <div class="gpp-ep-title"><span class="gpp-ep-se"><?php echo esc_html($se);?></span> <a href="<?php the_permalink();?>" class="gpp-ep-link"><?php the_title();?></a></div>
      <div class="gpp-ep-date"><?php echo esc_html($date);?></div>
    </div>
    <div class="gpp-ep-remaining">
      <span class="gpp-ep-remain-text" data-src="<?php echo esc_attr($audio_url);?>" data-dur="<?php echo $dur;?>"><?php echo esc_html(gpp_format_duration($dur));?></span>
    </div>
  </div>
  <?php endwhile; wp_reset_postdata(); ?>
</div>
<?php if (!$is_season && $total_pages > 1): ?>
<div class="gpp-load-more-wrap" id="gppLoadMore">
  <button class="gpp-load-more-btn" id="gppLoadBtn" data-page="2" data-max="<?php echo $total_pages;?>">Показать ещё</button>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
var gppAllEpisodes = <?php echo json_encode($all_data, JSON_UNESCAPED_UNICODE); ?>;

(function gppArchiveInit(){
  var audio = document.getElementById('gpAudio');
  if (!audio) { setTimeout(gppArchiveInit, 50); return; }

  var C48 = 2 * Math.PI * 22;
  var shuffleOn = false, loopOn = false;

  function fmt(s){s=Math.floor(s);var h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sec=s%60;return h?h+':'+(m<10?'0':'')+m+':'+(sec<10?'0':'')+sec:m+':'+(sec<10?'0':'')+sec;}

  function getPos(src) { return typeof gppGetPos === 'function' ? gppGetPos(src) : 0; }

  function initRows(){
    document.querySelectorAll('#gppEpList .gpp-ep-row').forEach(function(row){
      var src=row.dataset.episodeAudio, dur=parseInt(row.dataset.episodeDuration);
      if(!src||!dur)return;
      var ring=row.querySelector('.gpp-ep-ring-fill');
      if(ring){ring.style.strokeDasharray=C48;ring.style.strokeDashoffset=C48;}
      var pos = getPos(src);
      var rt=row.querySelector('.gpp-ep-remain-text');
      if(pos>5){
        if(ring)ring.style.strokeDashoffset=C48*(1-pos/dur);
        if(rt){var rem=dur-pos; rt.textContent=rem>30?'осталось: '+fmt(rem):'✓';}
      }else{
        if(rt)rt.textContent=fmt(dur);
      }
    });
  }
  initRows();

  function highlightPlaying(){
    document.querySelectorAll('.gpp-ep-row').forEach(function(r){r.classList.remove('gpp-ep-active');});
    var isPlaying = false;
    document.querySelectorAll('.gpp-ep-play-icon').forEach(function(icon){
      var playing=audio.src&&audio.src.indexOf(icon.dataset.src)!==-1;
      var isOn=playing&&!audio.paused;
      var pl=icon.querySelector('.gpp-epi-play'),pa=icon.querySelector('.gpp-epi-pause');
      if(pl)pl.style.display=isOn?'none':'';
      if(pa)pa.style.display=isOn?'':'none';
      if(playing){icon.closest('.gpp-ep-row').classList.add('gpp-ep-active');isPlaying=true;}
    });
    // 2. Play All button: show pause when playing
    var pai=document.getElementById('gppPlayAllIcon');
    if(pai) pai.innerHTML = (!audio.paused&&isPlaying) ? '<rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/>' : '<polygon points="5,3 19,12 5,21"/>';
  }
  function scrollToActive(){var a=document.querySelector('.gpp-ep-active');if(a)a.scrollIntoView({behavior:'smooth',block:'center'});}

  audio.addEventListener('timeupdate',function(){
    if(!audio.duration)return;
    document.querySelectorAll('#gppEpList .gpp-ep-row').forEach(function(row){
      if(audio.src.indexOf(row.dataset.episodeAudio)===-1)return;
      var ring=row.querySelector('.gpp-ep-ring-fill');
      if(ring){ring.style.strokeDasharray=C48;ring.style.strokeDashoffset=C48*(1-audio.currentTime/audio.duration);}
      var rt=row.querySelector('.gpp-ep-remain-text');
      if(rt){var rem=parseInt(row.dataset.episodeDuration)-audio.currentTime;rt.textContent=rem>30?'осталось: '+fmt(rem):'✓';}
    });
    highlightPlaying();
  });
  audio.addEventListener('pause',highlightPlaying);
  audio.addEventListener('playing',function(){highlightPlaying();scrollToActive();});
  audio.addEventListener('ended',function(){
    highlightPlaying();
    if(loopOn){audio.currentTime=0;audio.play();return;}
    if(shuffleOn&&window.gppAllEpisodes){
      var unfinished=gppAllEpisodes.filter(function(e){var p=getPos(e.src);return !p||p<(e.dur-30);});
      if(!unfinished.length)unfinished=gppAllEpisodes;
      var ep=unfinished[Math.floor(Math.random()*unfinished.length)];
      if(ep&&typeof gpLoadEpisode==='function'){gpLoadEpisode(ep.src,ep.title,ep.se,ep.cover,ep.dur);window._gppCurrentTc=ep.tc||[];audio.play();setTimeout(function(){highlightPlaying();scrollToActive();},500);}
      return;
    }
    var rows=Array.from(document.querySelectorAll('#gppEpList .gpp-ep-row'));
    var idx=rows.findIndex(function(r){return audio.src.indexOf(r.dataset.episodeAudio)!==-1;});
    if(idx!==-1&&idx+1<rows.length){gppListPlay(rows[idx+1]);setTimeout(function(){highlightPlaying();scrollToActive();},500);}
  });

  window.gppListPlay=function(row){
    if(!audio)return;
    var src=row.dataset.episodeAudio;if(!src)return;
    if(audio.src&&audio.src.indexOf(src)!==-1){
      if(typeof gpToggle==='function')gpToggle();
    }else{
      if(typeof gpLoadEpisode==='function')gpLoadEpisode(src,row.dataset.episodeTitle||'',row.dataset.episodeSe||'',row.dataset.episodeCover||'',parseInt(row.dataset.episodeDuration)||0,row.dataset.episodeUrl||'');
      try{window._gppCurrentTc=JSON.parse(row.dataset.episodeTc||'[]');}catch(e){window._gppCurrentTc=[];}
      audio.play();
    }
    highlightPlaying();
  };

  // 2. Play All / Pause
  var ba=document.getElementById('gppPlayAll');
  if(ba) ba.onclick=function(){
    if(!audio.paused){audio.pause();return;}
    var active=document.querySelector('.gpp-ep-active');
    if(active){if(typeof gpToggle==='function')gpToggle();}
    else{var f=document.querySelector('#gppEpList .gpp-ep-row');if(f)gppListPlay(f);}
  };

  // 2. Mutual exclusion: shuffle/loop
  // Restore button states
  shuffleOn = localStorage.getItem('gpp_shuffle') === '1';
  loopOn = localStorage.getItem('gpp_loop') === '1';
  if (shuffleOn && loopOn) loopOn = false; // mutual exclusion
  var bs=document.getElementById('gppShuffle');
  if(bs){bs.classList.toggle('gpp-lc-active',shuffleOn); bs.onclick=function(){
    shuffleOn=!shuffleOn;
    if(shuffleOn){loopOn=false;document.getElementById('gppLoop').classList.remove('gpp-lc-active');localStorage.setItem('gpp_loop','0');}
    this.classList.toggle('gpp-lc-active',shuffleOn);
    localStorage.setItem('gpp_shuffle',shuffleOn?'1':'0');
  };}
  var bl=document.getElementById('gppLoop');
  if(bl){bl.classList.toggle('gpp-lc-active',loopOn); bl.onclick=function(){
    loopOn=!loopOn;
    if(loopOn){shuffleOn=false;document.getElementById('gppShuffle').classList.remove('gpp-lc-active');localStorage.setItem('gpp_shuffle','0');}
    this.classList.toggle('gpp-lc-active',loopOn);
    localStorage.setItem('gpp_loop',loopOn?'1':'0');
  };}

  // Lazy load
  var loadBtn=document.getElementById('gppLoadBtn');
  if(loadBtn) loadBtn.onclick=function(){
    var page=parseInt(this.dataset.page),max=parseInt(this.dataset.max);
    this.disabled=true;this.textContent='Загрузка...';
    var fd=new FormData();fd.append('action','gpp_load_episodes');fd.append('page',page);
    fetch('<?php echo admin_url("admin-ajax.php");?>',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(resp){
      if(resp.success&&resp.data.html){
        document.getElementById('gppEpList').insertAdjacentHTML('beforeend',resp.data.html);
        initRows();highlightPlaying();
        loadBtn.dataset.page=page+1;loadBtn.disabled=false;loadBtn.textContent='Показать ещё';
        if(page>=max)document.getElementById('gppLoadMore').style.display='none';
      }
    }).catch(function(){loadBtn.disabled=false;loadBtn.textContent='Ошибка';});
  };

  // Season link clicks — set flag
  document.querySelectorAll('[data-season-click]').forEach(function(link){
    link.addEventListener('click',function(){sessionStorage.setItem('gpp_user_season','1');});
  });

  highlightPlaying();
  setTimeout(function(){
    scrollToActive();
    // 1. Auto-navigate to playing track's season (unless user picked one)
    if(sessionStorage.getItem('gpp_user_season')){sessionStorage.removeItem('gpp_user_season');return;}
    if(audio.src&&!document.querySelector('.gpp-ep-active')&&window.gppAllEpisodes){
      var ep=gppAllEpisodes.find(function(e){return audio.src.indexOf(e.src)!==-1;});
      if(ep&&ep.season_url) location.href=ep.season_url;
    }
  },400);
})();
</script>
<?php get_footer(); ?>
