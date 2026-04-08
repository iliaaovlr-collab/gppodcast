<?php
/**
 * Bottom Player + AJAX Navigation
 */
defined('ABSPATH') || exit;

add_action('wp_footer', 'gpp_render_player');

function gpp_render_player() {
    $audio='';$title='';$cover='';$dur=0;$se='';
    if (is_singular('podcast_episode')) {
        $id=get_the_ID();
        $audio=get_post_meta($id,'_gpp_audio_url',true);
        $title=get_the_title($id);
        $s=get_post_meta($id,'_gpp_season',true);
        $e=get_post_meta($id,'_gpp_episode',true);
        $dur=intval(get_post_meta($id,'_gpp_duration',true));
        $se=($s?"S{$s}E{$e}":"#{$e}");
        $cover=gpp_episode_cover($id);
    }
    ?>
<div class="bp" id="bottomPlayer" <?php if(!$audio) echo 'style="display:none"';?>>
  <audio id="gpAudio" preload="none" <?php if($audio) echo 'src="'.esc_url($audio).'"';?>
         data-duration="<?php echo $dur;?>" data-episode-url="<?php echo esc_url(get_permalink());?>"></audio>

  <div class="bp-prog" id="bpProgress">
    <div class="bp-prog-fill" id="pFill"></div>
    <div class="bp-prog-thumb" id="pThumb"></div>
    <div class="bp-marker bp-marker-cur" id="mkCur">0:00</div>
    <div class="bp-marker bp-marker-hover" id="mkHover">0:00</div>
    <div class="bp-marker bp-marker-end" id="mkEnd"><?php echo $dur?gpp_format_duration($dur):'';?></div>
  </div>

  <div class="bp-row">
    <div class="bp-info">
      <img class="bp-cover" id="bpCover" src="<?php echo esc_url($cover);?>" alt="">
      <div class="bp-meta">
        <!-- 2. S/E серым + название -->
        <div class="bp-title" id="bpTitle"><span class="bp-se" id="bpSe"><?php echo esc_html($se);?></span> <a href="<?php if($audio) echo get_permalink(); else echo '#'; ?>" class="bp-title-link" id="bpTitleLink"><?php echo esc_html($title);?></a></div>
        <!-- 3. Chapter name THEN arrows -->
        <div class="bp-sub" id="bpSub">
          <span id="bpChLabel"><?php echo esc_html(get_bloginfo('name'));?></span>
          <button class="bp-ch-btn" id="bpChPrev" onclick="gppChapterPrev()" style="visibility:hidden">‹</button>
          <button class="bp-ch-btn" id="bpChNext" onclick="gppChapterNext()" style="visibility:hidden">›</button>
        </div>
      </div>
    </div>

    <div class="bp-ctrls">
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
    </div>

    <div class="bp-right">
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
</div>


<script>
(function(){
  var audio = document.getElementById('gpAudio');
  var pFill = document.getElementById('pFill'), pThumb = document.getElementById('pThumb');
  var mkCur = document.getElementById('mkCur'), mkHover = document.getElementById('mkHover'), mkEnd = document.getElementById('mkEnd');
  var progBar = document.getElementById('bpProgress');
  var speedBtn = document.getElementById('speedBtn'), volRange = document.getElementById('volRange');
  var iconVolOn = document.getElementById('iconVolOn'), iconVolOff = document.getElementById('iconVolOff');
  var iconPlay = document.getElementById('iconPlay'), iconPause = document.getElementById('iconPause');
  var speeds = [1, 1.25, 1.5, 2], speedIdx = 0, curCh = -1;
  if (!audio) return;
  audio.volume = 0.8;

  function fmt(s) {
    s = Math.floor(s);
    var h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sec = s%60;
    return h ? h+':'+(m<10?'0':'')+m+':'+(sec<10?'0':'')+sec : m+':'+(sec<10?'0':'')+sec;
  }
  function setPlay(p) { iconPlay.style.display = p ? 'none' : 'block'; iconPause.style.display = p ? 'block' : 'none'; }
  function setVol() { var m = audio.muted || audio.volume === 0; iconVolOn.style.display = m ? 'none' : 'block'; iconVolOff.style.display = m ? 'block' : 'none'; }

  // ═══ 1. DELEGATED TIMECODE CLICK ═══
  var clickedTc = null; // track which timecode was clicked for loader

  document.addEventListener('click', function(e) {
    var tc = e.target.closest('.gpp-tc-time[data-time]');
    if (!tc) return;
    e.preventDefault();
    e.stopPropagation();
    var sec = parseFloat(tc.dataset.time);

    // Add loader to this specific timecode
    removeTcLoader();
    clickedTc = tc;
    var dot = document.createElement('span');
    dot.className = 'gpp-tc-loader';
    tc.insertAdjacentElement('beforebegin', dot);

    // Check if this timecode belongs to a different episode
    var article = tc.closest('[data-episode-audio]');
    if (article) {
      var epAudio = article.dataset.episodeAudio;
      if (!audio.src || audio.src.indexOf(epAudio) === -1) {
        gpLoadEpisode(
          epAudio,
          article.dataset.episodeTitle || '',
          article.dataset.episodeSe || '',
          article.dataset.episodeCover || '',
          parseInt(article.dataset.episodeDuration) || 0
        );
        audio.addEventListener('loadedmetadata', function onLoad() {
          audio.removeEventListener('loadedmetadata', onLoad);
          gpSeekTo(sec);
        });
        return;
      }
    }
    gpSeekTo(sec);
  });

  function removeTcLoader() {
    document.querySelectorAll('.gpp-tc-loader').forEach(function(l) { l.remove(); });
    clickedTc = null;
  }

  // ═══ 3. SCOPED TIMECODE HIGHLIGHTING — only for playing episode ═══
  function getPlayingEpisodeTimecodes() {
    // Find article whose audio matches what's currently playing
    var articles = document.querySelectorAll('[data-episode-audio]');
    for (var i = 0; i < articles.length; i++) {
      if (audio.src && audio.src.indexOf(articles[i].dataset.episodeAudio) !== -1) {
        return articles[i].querySelectorAll('.gpp-tc-time[data-time]');
      }
    }
    return []; // No matching article on this page
  }

  // ═══ HYBRID PROGRESS: DB for logged-in, localStorage fallback ═══
  var _savePending = {};
  var _saveTimer = null;

  // Get saved position for any src
  window.gppGetPos = function(src) {
    // DB first (logged-in)
    if (window.gppUserProgress && window.gppUserProgress[src] !== undefined) return parseInt(window.gppUserProgress[src]);
    // localStorage fallback
    try { var p = localStorage.getItem('gpp_pos_' + src); if (p) return parseInt(p); } catch(e) {}
    return 0;
  };

  function savePos() {
    if (!audio.src) return;
    var pos = Math.floor(audio.currentTime);
    try { localStorage.setItem('gpp_pos_' + audio.src, pos); } catch(e) {}
    // Save last episode info for restoration after reload
    try {
      localStorage.setItem('gpp_last_ep', JSON.stringify({
        src: audio.src,
        title: document.getElementById('bpTitleLink') ? document.getElementById('bpTitleLink').textContent : '',
        se: document.getElementById('bpSe') ? document.getElementById('bpSe').textContent : '',
        cover: document.getElementById('bpCover') ? document.getElementById('bpCover').src : '',
        duration: audio.duration || parseFloat(audio.dataset.duration) || 0,
        url: audio.dataset.episodeUrl || ''
      }));
    } catch(e) {}
    if (window.gppLoggedIn) {
      if (window.gppUserProgress) window.gppUserProgress[audio.src] = pos;
      _savePending[audio.src] = pos;
      if (!_saveTimer) {
        _saveTimer = setTimeout(function() {
          Object.keys(_savePending).forEach(function(src) {
            var fd = new FormData();
            fd.append('action', 'gpp_save_progress');
            fd.append('src', src);
            fd.append('pos', _savePending[src]);
            fetch(window.gppAjaxUrl || '<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body:fd});
          });
          _savePending = {};
          _saveTimer = null;
        }, 10000); // debounce 10s
      }
    }
  }

  function restorePos() {
    if (!audio.src) return;
    var p = gppGetPos(audio.src);
    if (p > 5) {
      var dur = audio.duration || parseFloat(audio.dataset.duration) || 0;
      if (dur && (dur - p) < 30) { audio.currentTime = 0; return; }
      audio.currentTime = p;
    }
  }
  audio.addEventListener('loadedmetadata', function() { restorePos(); if (mkEnd) mkEnd.textContent = fmt(audio.duration); });
  // Also restore on first play (preload=none means loadedmetadata fires late)
  var _firstPlay = true;
  audio.addEventListener('play', function() {
    if (_firstPlay) {
      _firstPlay = false;
      // Wait a tick for loadedmetadata to set duration
      setTimeout(function() {
        if (audio.currentTime < 2) {
          var p = gppGetPos(audio.src);
          if (p > 5) {
            var dur = audio.duration || parseFloat(audio.dataset.duration) || 0;
            if (dur && (dur - p) < 30) { /* near end */ }
            else { audio.currentTime = p; }
          }
        }
      }, 100);
    }
    updateTitleLink();
  });
  setInterval(savePos, 5000);
  audio.addEventListener('pause', savePos);
  window.addEventListener('beforeunload', savePos);

  audio.addEventListener('timeupdate', function() {
    if (!audio.duration) return;
    var pct = audio.currentTime / audio.duration * 100;
    pFill.style.width = pct + '%';
    pThumb.style.left = pct + '%';
    if (mkCur) { mkCur.textContent = fmt(audio.currentTime); mkCur.style.left = pct + '%'; }
    var ti = document.getElementById('gppAudioTime');
    if (ti) ti.value = Math.floor(audio.currentTime);

    // 3. Only highlight timecodes in the article of the playing episode
    // First, clear ALL timecodes on the page
    document.querySelectorAll('.gpp-tc-time.gpp-tc-active').forEach(function(tc) {
      tc.classList.remove('gpp-tc-active');
    });
    // Then highlight only in the correct article
    var tcs = getPlayingEpisodeTimecodes();
    var newCh = -1;
    for (var i = 0; i < tcs.length; i++) {
      var t = parseFloat(tcs[i].dataset.time);
      var nt = tcs[i+1] ? parseFloat(tcs[i+1].dataset.time) : Infinity;
      if (audio.currentTime >= t && audio.currentTime < nt) {
        tcs[i].classList.add('gpp-tc-active');
        newCh = i;
      }
    }

    // Chapter nav in player — support both DOM timecodes and meta-stored (_gppCurrentTc)
    if (tcs.length > 0) {
      // DOM timecodes (episode page)
      if (newCh !== curCh && newCh >= 0) {
        curCh = newCh;
        var span = tcs[curCh].parentElement;
        var name = span.textContent.replace(tcs[curCh].textContent, '').trim() || ('Часть ' + (curCh + 1));
        var bl = document.getElementById('bpChLabel'); if (bl) bl.textContent = name;
        var pp = document.getElementById('bpChPrev'), nn = document.getElementById('bpChNext');
        if (pp) pp.style.visibility = curCh > 0 ? 'visible' : 'hidden';
        if (nn) nn.style.visibility = curCh < tcs.length - 1 ? 'visible' : 'hidden';
      }
    } else if (window._gppCurrentTc && window._gppCurrentTc.length > 0) {
      // Meta timecodes (archive page)
      var mtc = window._gppCurrentTc;
      var mCh = -1;
      for (var mi = 0; mi < mtc.length; mi++) {
        var mt = mtc[mi].t, mnt = mtc[mi+1] ? mtc[mi+1].t : Infinity;
        if (audio.currentTime >= mt && audio.currentTime < mnt) mCh = mi;
      }
      if (mCh !== curCh && mCh >= 0) {
        curCh = mCh;
        var bl = document.getElementById('bpChLabel'); if (bl) bl.textContent = mtc[curCh].l || '';
        var pp = document.getElementById('bpChPrev'), nn = document.getElementById('bpChNext');
        if (pp) pp.style.visibility = curCh > 0 ? 'visible' : 'hidden';
        if (nn) nn.style.visibility = curCh < mtc.length - 1 ? 'visible' : 'hidden';
      }
    }

    // Comments by time when playing
    if (!audio.paused) {
      document.querySelectorAll('.gpp-comments-list').forEach(function(list) {
        var cmts = Array.from(list.querySelectorAll('.gpp-comment[data-time]'));
        cmts.sort(function(a, b) { return parseInt(a.dataset.time) - parseInt(b.dataset.time); });
        cmts.forEach(function(c) { list.appendChild(c); c.style.display = (parseInt(c.dataset.time) <= Math.floor(audio.currentTime)) ? '' : 'none'; });
      });
    }
  });

  audio.addEventListener('pause', function() {
    setPlay(false);
    document.querySelectorAll('.gpp-comments-list').forEach(function(list) {
      var cmts = Array.from(list.querySelectorAll('.gpp-comment'));
      cmts.reverse();
      cmts.forEach(function(c) { list.appendChild(c); c.style.display = ''; });
    });
  });
  audio.addEventListener('ended', function() { setPlay(false); savePos(); });

  // Progress bar
  if (progBar) {
    progBar.addEventListener('click', function(e) { if (!audio.duration) return; audio.currentTime = ((e.clientX - this.getBoundingClientRect().left) / this.offsetWidth) * audio.duration; });
    progBar.addEventListener('mousemove', function(e) { if (!mkHover) return; var r = this.getBoundingClientRect(), p = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)), d = audio.duration || parseFloat(audio.dataset.duration) || 0; mkHover.textContent = fmt(p * d); mkHover.style.left = (p * 100) + '%'; });
    // 3. Mobile: show markers on touch, hide after 3s
    var _touchTimer = null;
    progBar.addEventListener('touchstart', function() {
      this.classList.add('bp-touched');
      clearTimeout(_touchTimer);
      _touchTimer = setTimeout(function() { progBar.classList.remove('bp-touched'); }, 3000);
    }, {passive: true});
  }

  // Volume wheel
  var vw = document.getElementById('bpVol');
  if (vw) vw.addEventListener('wheel', function(e) { e.preventDefault(); var n = Math.max(0, Math.min(100, Math.round(audio.volume * 100) + (e.deltaY > 0 ? -5 : 5))); audio.volume = n / 100; audio.muted = false; if (volRange) volRange.value = n; setVol(); }, {passive: false});

  // Keyboard
  document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;
    if (e.code === 'Space') { e.preventDefault(); gpToggle(); }
    if (e.code === 'ArrowLeft') { e.preventDefault(); gpSeek(-15); }
    if (e.code === 'ArrowRight') { e.preventDefault(); gpSeek(15); }
  });

  // Globals
  window.gpToggle = function() { if (audio.paused) { audio.play(); setPlay(true); } else { audio.pause(); } };
  window.gpSeek = function(s) { audio.currentTime = Math.max(0, Math.min(audio.duration || 0, audio.currentTime + s)); };
  window.gpSeekTo = function(s) { audio.currentTime = s; if (audio.paused) { audio.play(); setPlay(true); } };
  window.gpSpeed = function() { speedIdx = (speedIdx + 1) % speeds.length; audio.playbackRate = speeds[speedIdx]; speedBtn.textContent = speeds[speedIdx] + 'x'; };
  window.gpVol = function(v) { audio.volume = v / 100; audio.muted = false; setVol(); };
  window.gpMute = function() { audio.muted = !audio.muted; setVol(); };
  window.gppChapterPrev = function() {
    var tcs = getPlayingEpisodeTimecodes();
    if (tcs.length) { for (var i = tcs.length - 1; i >= 0; i--) { if (parseFloat(tcs[i].dataset.time) < audio.currentTime - 2) { gpSeekTo(parseFloat(tcs[i].dataset.time)); break; } } return; }
    if (window._gppCurrentTc) { var m = window._gppCurrentTc; for (var i = m.length - 1; i >= 0; i--) { if (m[i].t < audio.currentTime - 2) { gpSeekTo(m[i].t); break; } } }
  };
  window.gppChapterNext = function() {
    var tcs = getPlayingEpisodeTimecodes();
    if (tcs.length) { for (var i = 0; i < tcs.length; i++) { if (parseFloat(tcs[i].dataset.time) > audio.currentTime) { gpSeekTo(parseFloat(tcs[i].dataset.time)); break; } } return; }
    if (window._gppCurrentTc) { var m = window._gppCurrentTc; for (var i = 0; i < m.length; i++) { if (m[i].t > audio.currentTime) { gpSeekTo(m[i].t); break; } } }
  };

  window.gpLoadEpisode = function(src, title, se, cover, duration, url) {
    if (audio.src && audio.src === src) return;
    savePos();
    _firstPlay = true; // reset for new episode
    audio.src = src; audio.load();
    audio.dataset.episodeUrl = url || '';
    var link = document.getElementById('bpTitleLink');
    if (link) { link.textContent = title; link.href = url || '#'; }
    var seEl = document.getElementById('bpSe');
    if (seEl) seEl.textContent = se;
    document.getElementById('bpCover').src = cover;
    document.getElementById('mkEnd').textContent = fmt(duration);
    audio.dataset.duration = duration;
    document.getElementById('bottomPlayer').style.display = '';
    curCh = -1;
    document.getElementById('bpChLabel').textContent = '';
    document.getElementById('bpChPrev').style.visibility = 'hidden';
    document.getElementById('bpChNext').style.visibility = 'hidden';
    updateTitleLink();
  };

  // ═══ AJAX NAVIGATION ═══
  function isInternal(url) { try { var u = new URL(url, location.origin); return u.origin === location.origin && !u.pathname.match(/wp-admin|wp-login/); } catch(e) { return false; } }

  document.addEventListener('click', function(e) {
    // Skip timecodes — handled above
    if (e.target.closest('.gpp-tc-time[data-time]')) return;
    var a = e.target.closest('a[href]');
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank') return;
    var href = a.getAttribute('href');
    if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return;
    if (!isInternal(href)) return;
    e.preventDefault();
    navigateTo(href);
  });

  window.addEventListener('popstate', function() { navigateTo(location.href, true); });

  window.navigateTo = navigateTo;
  function navigateTo(url, noPush) {
    document.body.style.cursor = 'wait';
    fetch(url).then(function(r) { return r.text(); }).then(function(html) {
      var doc = (new DOMParser()).parseFromString(html, 'text/html');
      var nc = doc.querySelector('.gp-center'), oc = document.querySelector('.gp-center');
      if (nc && oc) oc.innerHTML = nc.innerHTML;
      else { var nm = doc.querySelector('.site-content'), om = document.querySelector('.site-content'); if (nm && om) om.innerHTML = nm.innerHTML; }
      var nr = doc.querySelector('.gp-panel--right .gp-panel-inner'), or_ = document.querySelector('.gp-panel--right .gp-panel-inner');
      if (nr && or_) or_.innerHTML = nr.innerHTML;
      document.title = doc.title;
      document.querySelectorAll('.gp-nav .current-menu-item').forEach(function(el) { el.classList.remove('current-menu-item'); });
      doc.querySelectorAll('.gp-nav .current-menu-item').forEach(function(el) { var lk = el.querySelector('a'); if (lk) { var m = document.querySelector('.gp-nav a[href="' + lk.getAttribute('href') + '"]'); if (m) m.parentElement.classList.add('current-menu-item'); } });

      // Load new episode into player only if player is idle
      var epArticle = doc.querySelector('[data-episode-audio]');
      if (epArticle && epArticle.dataset.episodeAudio) {
        var newSrc = epArticle.dataset.episodeAudio;
        if (audio.paused && !audio.currentTime) {
          gpLoadEpisode(newSrc,
            epArticle.dataset.episodeTitle || '',
            epArticle.dataset.episodeSe || '',
            epArticle.dataset.episodeCover || '',
            parseInt(epArticle.dataset.episodeDuration) || 0
          );
        }
      }

      // Execute inline scripts in new content
      document.querySelectorAll('.gp-center script').forEach(function(s) { var ns = document.createElement('script'); ns.textContent = s.textContent; s.parentNode.replaceChild(ns, s); });

      // 1. Force lazy images to load after AJAX replacement
      document.querySelectorAll('.gp-center img[loading="lazy"], .gp-panel--right img[loading="lazy"]').forEach(function(img) {
        img.removeAttribute('loading');
        img.removeAttribute('decoding');
        // Re-trigger by cloning src
        var src = img.getAttribute('src');
        if (src) { img.removeAttribute('src'); img.setAttribute('src', src); }
      });
      // Also fix srcset images
      document.querySelectorAll('.gp-center img[srcset], .gp-panel--right img[srcset]').forEach(function(img) {
        var ss = img.getAttribute('srcset');
        img.removeAttribute('srcset'); img.setAttribute('srcset', ss);
      });

      var c = document.querySelector('.gp-center'); if (c) c.scrollTop = 0;
      if (!noPush) history.pushState(null, '', url);
      document.body.style.cursor = '';
      updateTitleLink();
    }).catch(function() { document.body.style.cursor = ''; location.href = url; });
  }

  // 2. Disable player link when on current episode page
  function updateTitleLink() {
    var link = document.getElementById('bpTitleLink');
    if (!link) return;
    var epUrl = audio.dataset.episodeUrl || link.getAttribute('href') || '';
    if (!epUrl || epUrl === '#') { link.classList.add('bp-link-disabled'); return; }
    try {
      var epPath = new URL(epUrl, location.origin).pathname.replace(/\/+$/, '');
      var curPath = location.pathname.replace(/\/+$/, '');
      link.classList.toggle('bp-link-disabled', epPath === curPath);
    } catch(e) { link.classList.remove('bp-link-disabled'); }
  }
  updateTitleLink();

  // ═══ CIRCLE PLAY BUTTON ═══
  window.gpPlayThis = function(el) {
    var article = el.closest('[data-episode-audio]');
    if (!article) return;
    var src = article.dataset.episodeAudio;
    // If different episode, load it
    if (!audio.src || audio.src.indexOf(src) === -1) {
      gpLoadEpisode(src, article.dataset.episodeTitle || '', article.dataset.episodeSe || '', article.dataset.episodeCover || '', parseInt(article.dataset.episodeDuration) || 0);
      audio.play();
      setPlay(true);
    } else {
      gpToggle();
    }
  };

  // ═══ LOADING STATES ═══
  var C = 2 * Math.PI * 21;

  function isThisEpisode() {
    var article = document.querySelector('[data-episode-audio]');
    return article && audio.src && audio.src.indexOf(article.dataset.episodeAudio) !== -1;
  }

  function showLoading(on) {
    var bp = document.getElementById('playBtn');
    if (bp) bp.classList.toggle('bp-loading', on);
    if (isThisEpisode()) {
      var ci = document.getElementById('gppCircleIcon');
      var cl = document.getElementById('gppCircleLoader');
      if (ci) ci.style.display = on ? 'none' : '';
      if (cl) cl.style.display = on ? '' : 'none';
    }
  }

  audio.addEventListener('waiting', function() { showLoading(true); });
  audio.addEventListener('playing', function() { showLoading(false); setPlay(true); removeTcLoader(); });
  audio.addEventListener('canplay', function() { showLoading(false); removeTcLoader(); });

  // Buffer ring — only this episode
  audio.addEventListener('progress', function() {
    if (!audio.duration || !isThisEpisode()) return;
    var buf = audio.buffered;
    if (buf.length > 0) {
      var ring = document.getElementById('gppCircleBuffer');
      if (ring) ring.style.strokeDashoffset = C * (1 - buf.end(buf.length - 1) / audio.duration);
    }
  });

  // Circle progress — only this episode
  audio.addEventListener('timeupdate', function() {
    if (!audio.duration) return;
    var mine = isThisEpisode();
    var ring = document.getElementById('gppCircleProgress');
    var ci = document.getElementById('gppCircleIcon');
    if (mine) {
      if (ring) ring.style.strokeDashoffset = C * (1 - audio.currentTime / audio.duration);
      if(ci){ci.querySelector('.gpp-ci-play').style.display=audio.paused?'':'none';ci.querySelector('.gpp-ci-pause').style.display=audio.paused?'none':'';}
    } else {
      if(ci){ci.querySelector('.gpp-ci-play').style.display='';ci.querySelector('.gpp-ci-pause').style.display='none';}
    }
  });

  audio.addEventListener('pause', function() {
    if (isThisEpisode()) {
      var ci = document.getElementById('gppCircleIcon');
      if(ci){ci.querySelector('.gpp-ci-play').style.display='';ci.querySelector('.gpp-ci-pause').style.display='none';}
    }
  });

  // Restore saved progress on page load
  (function initCircle() {
    var article = document.querySelector('[data-episode-audio]');
    if (!article) return;
    var src = article.dataset.episodeAudio;
    var dur = parseInt(article.dataset.episodeDuration) || 0;
    if (!src || !dur) return;
    if (audio.src && audio.src.indexOf(src) !== -1) return;
    var saved = gppGetPos(src);
    if (saved > 5) {
      var ring = document.getElementById('gppCircleProgress');
      if (ring) ring.style.strokeDashoffset = C * (1 - saved / dur);
    }
  })();

  (function initPlayerBar() {
    if (!audio.src) return;
    var dur = parseFloat(audio.dataset.duration) || 0;
    if (!dur) return;
    var saved = gppGetPos(audio.src);
    if (saved > 5) {
      var pct = saved / dur * 100;
      pFill.style.width = pct + '%';
      pThumb.style.left = pct + '%';
      if (mkCur) { mkCur.textContent = fmt(saved); mkCur.style.left = pct + '%'; }
    }
  })();

  // 4. Restore last played episode if player is empty (e.g. page reload on non-episode page)
  (function restoreLastEp() {
    if (audio.src) return; // already has audio from template
    try {
      var last = JSON.parse(localStorage.getItem('gpp_last_ep'));
      if (last && last.src) {
        gpLoadEpisode(last.src, last.title || '', last.se || '', last.cover || '', last.duration || 0, last.url || '');
        // Show saved position on progress bar
        var saved = gppGetPos(last.src);
        var dur = last.duration || 0;
        if (saved > 5 && dur > 0) {
          var pct = saved / dur * 100;
          pFill.style.width = pct + '%';
          pThumb.style.left = pct + '%';
          if (mkCur) { mkCur.textContent = fmt(saved); mkCur.style.left = pct + '%'; }
        }
      }
    } catch(e) {}
  })();
})();
</script>
    <?php
}

add_action('wp_head','gpp_player_css');
function gpp_player_css(){?>
    <style>
    .bp{position:fixed;bottom:0;left:0;right:0;z-index:900;background:var(--gp-surface);border-top:1px solid var(--gp-border);height:64px}
    .bp-prog{position:absolute;top:-2px;left:0;right:0;height:4px;background:var(--gp-border2);cursor:pointer;z-index:2}
    .bp-prog-fill{height:100%;background:var(--gp-accent);width:0%;transition:width .15s linear}
    .bp-prog-thumb{width:12px;height:12px;border-radius:50%;background:var(--gp-accent);position:absolute;top:50%;transform:translate(-50%,-50%);box-shadow:0 0 8px var(--gp-accentbg);left:0%;opacity:0;transition:opacity .2s,left .15s linear}
    .bp-prog:hover .bp-prog-thumb{opacity:1}.bp-prog:hover{height:6px;top:-3px}
    .bp-marker{position:absolute;top:-24px;transform:translateX(-50%);background:var(--gp-surface2);border:1px solid var(--gp-border2);border-radius:3px;padding:2px 7px;font-family:var(--gp-font-ui);font-size:10px;pointer-events:none;white-space:nowrap;transition:opacity .15s}
    .bp-marker-cur{color:var(--gp-text);left:0%;opacity:0;z-index:4}.bp-marker-hover{color:var(--gp-accent);font-weight:600;left:50%;opacity:0;z-index:3}.bp-marker-end{color:var(--gp-textfaint);right:0;left:auto;transform:none;opacity:0}.bp-prog:hover .bp-marker{opacity:1}
    .bp-row{display:flex;align-items:center;justify-content:space-between;height:100%;padding:0 20px;gap:16px}
    .bp-info{display:flex;align-items:center;gap:12px;flex:1;min-width:0}.bp-cover{width:44px;height:44px;border-radius:3px;object-fit:cover;border:1px solid var(--gp-border);flex-shrink:0}.bp-meta{min-width:0}
    .bp-title{font-family:var(--gp-font-body);font-size:15px;color:var(--gp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .bp-title-link{color:inherit;text-decoration:none}
    .bp-title-link:hover{text-decoration:underline;color:var(--gp-accent)}
    .bp-title-link.bp-link-disabled{pointer-events:none;color:var(--gp-text);text-decoration:none!important}
    .bp-se{font-size:12px;color:var(--gp-textmuted);margin-right:2px}
    .bp-sub{font-family:var(--gp-font-ui);font-size:11px;color:var(--gp-textmuted);display:flex;align-items:center;gap:4px}
    .bp-ch-btn{background:var(--gp-surface2);border:1px solid var(--gp-border);color:var(--gp-text2);cursor:pointer;font-size:12px;padding:0 5px;border-radius:3px;line-height:1.4;transition:all .15s}.bp-ch-btn:hover{border-color:var(--gp-accent);color:var(--gp-accent)}
    .bp-ctrls{display:flex;align-items:center;gap:8px;position:absolute;left:50%;transform:translateX(-50%)}
    .bp-btn{background:none;border:none;color:var(--gp-textmuted);cursor:pointer;transition:color .15s;display:flex;align-items:center;justify-content:center}.bp-btn:hover{color:var(--gp-text2)}
    .bp-btn.bp-sm{width:28px;height:28px;border:1px solid var(--gp-border);border-radius:50%}.bp-btn.bp-sm:hover{border-color:var(--gp-accentdim);color:var(--gp-text2)}
    .bp-btn.bp-play{width:40px;height:40px;border-radius:50%;border:2px solid var(--gp-accent);color:var(--gp-accent)}.bp-btn.bp-play:hover{background:var(--gp-accent);color:#fff}
    .bp-right{display:flex;align-items:center;gap:12px;flex-shrink:0}
    .bp-speed{font-family:var(--gp-font-ui);font-size:10px;color:var(--gp-textmuted);background:none;border:1px solid var(--gp-border);border-radius:3px;padding:3px 7px;cursor:pointer;transition:all .15s}.bp-speed:hover{border-color:var(--gp-accentdim);color:var(--gp-accent)}
    .bp-vol{position:relative;display:flex;align-items:center}.bp-vol-btn{background:none;border:none;color:var(--gp-textmuted);cursor:pointer;display:flex;align-items:center}.bp-vol-btn:hover{color:var(--gp-text2)}
    .bp-vol-popup{position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);background:var(--gp-surface2);border:1px solid var(--gp-border2);border-radius:4px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;opacity:0;pointer-events:none;transition:opacity .2s}
    .bp-vol:hover .bp-vol-popup,.bp-vol-popup:hover{opacity:1;pointer-events:auto}
    .bp-vol-range{-webkit-appearance:none;appearance:none;width:3px;height:80px;background:var(--gp-border2);border-radius:2px;outline:none;writing-mode:vertical-lr;direction:rtl}
    .bp-vol-range::-webkit-slider-thumb{-webkit-appearance:none;width:12px;height:12px;border-radius:50%;background:var(--gp-accent);cursor:pointer}
    .gp-center .site-content{padding-bottom:80px!important}
    body:not(.gp-layout-three-column){padding-bottom:64px}
    @media(max-width:768px){
      .bp{height:56px}
      .bp-row{padding:0 12px;gap:10px}
      .bp-meta{max-width:none;flex:1;min-width:0}
      .bp-ctrls{position:static;transform:none;gap:0}
      .bp-ctrls .bp-sm{display:none}
      .bp-right{display:none}
      .bp-sub{display:none}
      .bp-marker-hover{display:none!important}
      .bp-marker-cur,.bp-marker-end{opacity:0;transition:opacity .3s}
      .bp-prog.bp-touched .bp-marker-cur,.bp-prog.bp-touched .bp-marker-end{opacity:1}
      .bp-prog:hover{height:4px;top:-2px}
    }

    /* ═══ CIRCLE PLAY BUTTON ═══ */
    .gpp-play-circle{
      position:relative;width:48px;height:48px;cursor:pointer;margin:16px 0;
      display:flex;align-items:center;justify-content:center;
    }
    .gpp-circle-svg{width:48px;height:48px;transform:rotate(-90deg)}
    .gpp-circle-bg{fill:none;stroke:var(--gp-border2);stroke-width:2}
    .gpp-circle-buffer{fill:none;stroke:var(--gp-border);stroke-width:2;stroke-dasharray:131.95;stroke-dashoffset:131.95;transition:stroke-dashoffset .3s}
    .gpp-circle-progress{fill:none;stroke:var(--gp-accent);stroke-width:2.5;stroke-dasharray:131.95;stroke-dashoffset:131.95;stroke-linecap:round;transition:stroke-dashoffset .3s}
    .gpp-circle-icon{
      position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
      font-size:16px;color:var(--gp-accent);
    }
    .gpp-play-circle:hover .gpp-circle-icon{color:var(--gp-text)}
    .gpp-play-circle:hover .gpp-circle-progress{stroke:var(--gp-text)}

    /* ═══ LOADING SPINNERS ═══ */
    @keyframes gppSpin{to{transform:rotate(360deg)}}

    .gpp-circle-loader{
      position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    }
    .gpp-circle-loader::after{
      content:'';width:20px;height:20px;border:2px solid var(--gp-border2);
      border-top-color:var(--gp-accent);border-radius:50%;animation:gppSpin .6s linear infinite;
    }

    /* Bottom player loading state */
    .bp-loading svg{display:none!important}
    .bp-loading::after{
      content:'';width:20px;height:20px;border:2px solid var(--gp-border2);
      border-top-color:var(--gp-accent);border-radius:50%;animation:gppSpin .6s linear infinite;
    }

    /* Timecode loading dot — appears before timecode on click */
    .gpp-tc-loader{
      display:inline-block;width:10px;height:10px;margin-right:4px;vertical-align:middle;
      border:1.5px solid var(--gp-border2);border-top-color:var(--gp-accent);
      border-radius:50%;animation:gppSpin .6s linear infinite;
    }
    </style>
<?php }
