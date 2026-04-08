/**
 * GipetauPodcast — Mobile Navigation
 * Delegated — survives AJAX content replacement.
 */
(function(){
  function shut(){
    var nav = document.querySelector('.main-nav');
    if (nav) nav.classList.remove('open');
    var toggle = document.querySelector('.nav-toggle');
    if (toggle) toggle.setAttribute('aria-expanded','false');
  }

  // Delegated click — works after AJAX replaces .gp-center
  document.addEventListener('click', function(e){
    if (e.target.closest('.nav-toggle')) {
      var nav = document.querySelector('.main-nav');
      if (nav) nav.classList.add('open');
      var toggle = e.target.closest('.nav-toggle');
      if (toggle) toggle.setAttribute('aria-expanded','true');
      return;
    }
    if (e.target.closest('.nav-close')) { shut(); return; }
    // Click on overlay (nav itself, not its children)
    if (e.target.classList && e.target.classList.contains('main-nav')) { shut(); }
  });

  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') shut(); });
})();
