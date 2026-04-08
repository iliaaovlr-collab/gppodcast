(function(api){
  // Brand
  api('blogname', function(v){ v.bind(function(val){ var el=document.querySelector('.site-title a'); if(el) el.textContent=val; }); });
  api('blogdescription', function(v){ v.bind(function(val){ var el=document.querySelector('.site-description'); if(el) el.textContent=val; }); });
  api('gp_show_title', function(v){ v.bind(function(val){ var el=document.querySelector('.site-title'); if(el) el.style.display=val?'':'none'; }); });
  api('gp_show_tagline', function(v){ v.bind(function(val){ var el=document.querySelector('.site-description'); if(el) el.style.display=val?'':'none'; }); });

  // Layout
  function css(prop,val){ document.documentElement.style.setProperty(prop,val); }
  api('gp_logo_height', function(v){ v.bind(function(val){ css('--gp-logo-height',val+'px'); }); });
  api('gp_sidebar_left_width', function(v){ v.bind(function(val){ css('--gp-sidebar-left',val+'px'); }); });
  api('gp_sidebar_right_width', function(v){ v.bind(function(val){ css('--gp-sidebar-right',val+'px'); }); });

  // Typography sizes
  var sizes = {gp_fs_body:'--gp-fs-body',gp_fs_h1:'--gp-fs-h1',gp_fs_h2:'--gp-fs-h2',gp_fs_h3:'--gp-fs-h3',gp_fs_h4:'--gp-fs-h4',gp_fs_small:'--gp-fs-small',gp_fs_xs:'--gp-fs-xs'};
  Object.keys(sizes).forEach(function(k){ api(k, function(v){ v.bind(function(val){ css(sizes[k],val+'rem'); }); }); });

  // Line heights
  api('gp_lh_body', function(v){ v.bind(function(val){ css('--gp-lh-body',val); }); });
  api('gp_lh_heading', function(v){ v.bind(function(val){ css('--gp-lh-heading',val); }); });
})(wp.customize);
