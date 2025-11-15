<?php

function get_my_styles(){

  $cssfile = '';
  $cssContents = styles_list();

  foreach($cssContents as $file) {


    $cssfc = array(
      "~url~",
      "~imgurl~",
      "~fonts~",
      "~color1~",
      "~color2~",
      "~color3~",
    );

    $cssrv = array(
      get_template_directory_uri(),
      get_template_directory_uri().'/assets/images',
      get_template_directory_uri().'/assets/font',
      jawda_get_color(1),
      jawda_get_color(2),
      jawda_get_color(3),
    );


    $cssfile .=str_replace($cssfc, $cssrv,file_get_contents($file));
  }

  $style = '<style>';
  $style .= minifyCss($cssfile);
  $style .= '.menu-bar { background-color: ' . jawda_get_color(2) . ' !important; }';
  $style .= '.navi .menutoggel i { color: #fff !important; }';
  $style .= '.menu-bar .language a { color: ' . jawda_get_color(1) . ' !important; }';
  $style .= '
    .related-box { display: flex; flex-direction: column; height: 100%; }
    .related-data { flex-grow: 1; display: flex; flex-direction: column; }
    .related-price-container { margin-top: auto; }
    .project-services { display: flex; flex-direction: column; gap: 24px; }
    .project-services__list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; }
    .project-services__list--columns-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
    .project-services__list--columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    .project-services__list--columns-3 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    .project-services__list--columns-4 { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }
    .project-services__list--columns-5 { grid-template-columns: repeat(5, minmax(0, 1fr)) !important; }
    .project-services__item--toggle { order: initial !important; }
    .project-services__item--toggle--bottom { order: initial !important; grid-column: auto !important; }
    .project-services__item--extra[hidden] { display: none !important; }
    .project-services--collapsible:not(.project-services--ready) .project-services__item--toggle { display: none !important; }

    @media (max-width: 991px) {
      .project-services__list { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 12px; }
      .project-services__list--columns-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
      .project-services__list--columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }

    @media (max-width: 767px) {
      .project-services__item { min-height: 120px; padding: 12px; }
      .project-services__item--toggle { min-height: 120px; }
    }
  ';

  $style .= '</style>'."\n";
  echo $style;

}

function get_my_scripts(){
  $ldir = is_rtl() ? "rtl" : "ltr" ;
  $search_nonce = wp_create_nonce('search_nonce_action');
  $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : is_rtl();
  $cf_dep_data = [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('cf_dep_nonce'),
      'language' => $is_ar ? 'ar' : 'en',
      'i18n'     => [
          'loading'           => $is_ar ? '— جاري التحميل… —' : __('— Loading… —', 'aqarand'),
          'select_gov_first'  => $is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'aqarand'),
          'select_city'       => $is_ar ? '— اختر المدينة —' : __('— Select City —', 'aqarand'),
          'select_city_first' => $is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'aqarand'),
          'select_district'   => $is_ar ? '— اختر المنطقة —' : __('— Select District —', 'aqarand'),
      ]
  ];
  echo '<script>var CF_DEP = ' . json_encode($cf_dep_data) . ';</script>' . "\n";
  echo '<script>var global = {"ajax":'.json_encode( admin_url( "admin-ajax.php" ) ).'};</script>'."\n";
  echo '<script>var search_nonce = {"nonce":"'.$search_nonce.'"}</script>';
  echo '<script>window.aqarandDisableLegacySiteformHandler = true;</script>'."\n";
  echo '<script src="'.get_template_directory_uri().'/assets/js/frontend-locations.js?v=1.0"></script>'."\n";
  echo '<script src="'.get_template_directory_uri().'/assets/js/'.$ldir.'/script.js?v=01"></script>'."\n";
  echo '<script src="'.wjsurl.'main.js?v=1.0"></script>'."\n";
  ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var moreLessButton = document.querySelector('.more-less-button');
      if (moreLessButton) {
        moreLessButton.addEventListener('click', function () {
          var moreLinks = document.querySelector('.more-links');
          if (moreLinks.style.display === 'none') {
            moreLinks.style.display = 'block';
            <?php ob_start(); get_text("أقل", "Show Less"); $less_text = ob_get_clean(); ?>
            moreLessButton.textContent = '<?php echo esc_js($less_text); ?>';
          } else {
            moreLinks.style.display = 'none';
            <?php ob_start(); get_text("المزيد", "Show More"); $more_text = ob_get_clean(); ?>
            moreLessButton.textContent = '<?php echo esc_js($more_text); ?>';
          }
        });
      }
    });

    document.addEventListener('DOMContentLoaded', function () {
      var serviceSections = document.querySelectorAll('.project-services.project-services--collapsible');
      var columnClasses = [
        'project-services__list--columns-1',
        'project-services__list--columns-2',
        'project-services__list--columns-3',
        'project-services__list--columns-4',
        'project-services__list--columns-5'
      ];

      var toArray = function (nodeList) {
        return Array.prototype.slice.call(nodeList || []);
      };

      var initSection = function (section) {
        var list = section.querySelector('.project-services__list');
        var toggle = section.querySelector('.project-services__item--toggle');
        if (!list || !toggle) {
          return;
        }

        var extras = toArray(list.querySelectorAll('.project-services__item--extra'));
        var primaryItems = toArray(list.querySelectorAll('.project-services__item--primary:not(.project-services__item--extra)'));
        if (!extras.length) {
          section.classList.remove('project-services--collapsible');
          return;
        }

        section.classList.add('project-services--ready');
        var moreLabel = toggle.getAttribute('data-more-label') || toggle.textContent || '';
        var lessLabel = toggle.getAttribute('data-less-label') || moreLabel;

        var collapsedColumnsAttr = list.getAttribute('data-columns-collapsed');
        var expandedColumnsAttr = list.getAttribute('data-columns-expanded');
        var collapsedLimitAttr = list.getAttribute('data-collapsed-limit');
        var collapsedColumns = collapsedColumnsAttr ? parseInt(collapsedColumnsAttr, 10) : 0;
        var expandedColumns = expandedColumnsAttr ? parseInt(expandedColumnsAttr, 10) : collapsedColumns;
        var collapsedLimit = collapsedLimitAttr ? parseInt(collapsedLimitAttr, 10) : primaryItems.length;

        var setColumns = function (columns) {
          if (!columns || isNaN(columns)) {
            return;
          }
          for (var idx = 0; idx < columnClasses.length; idx++) {
            list.classList.remove(columnClasses[idx]);
          }
          list.classList.add('project-services__list--columns-' + columns);
        };

        var placeToggleBeforeExtras = function () {
          var referenceNode = null;
          for (var j = 0; j < extras.length; j++) {
            if (extras[j].parentElement === list) {
              referenceNode = extras[j];
              break;
            }
          }
          if (referenceNode) {
            list.insertBefore(toggle, referenceNode);
          } else {
            list.appendChild(toggle);
          }
        };

        var collapse = function (shouldScroll) {
          section.classList.remove('project-services--expanded');
          toggle.setAttribute('aria-expanded', 'false');
          if (moreLabel) {
            toggle.textContent = moreLabel;
          }
          for (var k = 0; k < extras.length; k++) {
            extras[k].setAttribute('hidden', '');
          }
          placeToggleBeforeExtras();
          toggle.classList.remove('project-services__item--toggle--bottom');
          var fallbackCollapsed = Math.max(1, Math.min(5, collapsedLimit + (extras.length ? 1 : 0)));
          setColumns(collapsedColumns || fallbackCollapsed);
          if (shouldScroll) {
            var scrollOffset = 120;
            var listTop = list.getBoundingClientRect().top + window.pageYOffset;
            window.scrollTo({
              top: Math.max(listTop - scrollOffset, 0),
              behavior: 'smooth'
            });
          }
        };

        var expand = function () {
          section.classList.add('project-services--expanded');
          toggle.setAttribute('aria-expanded', 'true');
          toggle.textContent = lessLabel || moreLabel;
          for (var m = 0; m < extras.length; m++) {
            extras[m].removeAttribute('hidden');
          }
          list.appendChild(toggle);
          toggle.classList.add('project-services__item--toggle--bottom');
          var fallbackExpanded = Math.max(1, Math.min(5, primaryItems.length + extras.length));
          setColumns(expandedColumns || collapsedColumns || fallbackExpanded);
        };

        collapse(false);

        toggle.addEventListener('click', function () {
          if (section.classList.contains('project-services--expanded')) {
            collapse(true);
          } else {
            expand();
          }
        });
      };

      for (var i = 0; i < serviceSections.length; i++) {
        initSection(serviceSections[i]);
      }
    });



    function equalizeCardHeights() {
      var rows = document.querySelectorAll('.row');
      for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var cards = row.querySelectorAll('.projectbxspace .related-box');
        if (cards.length > 1) {
          var maxHeight = 0;
          for (var j = 0; j < cards.length; j++) {
            cards[j].style.height = 'auto';
          }
          for (var k = 0; k < cards.length; k++) {
            if (cards[k].offsetHeight > maxHeight) {
              maxHeight = cards[k].offsetHeight;
            }
          }
          for (var m = 0; m < cards.length; m++) {
            cards[m].style.height = maxHeight + 'px';
          }
        }
      }
    }

    document.addEventListener('DOMContentLoaded', equalizeCardHeights);
    window.addEventListener('resize', equalizeCardHeights);

  </script>
  <?php
}

/* -----------------  ------------------ */



function styles_list(){

  $ldir = is_rtl() ? "rtl" : "ltr" ;

  $cssContents = [];

  $cssContents['main'] = get_template_directory().'/assets/css/'.$ldir.'/main.css' ;

  if ( is_front_page() || is_home() ) {
    $cssContents['home'] = get_template_directory().'/assets/css/'.$ldir.'/home.css' ;
  }

  elseif ( is_single() || is_page()  ) {
    $cssContents['post'] = get_template_directory().'/assets/css/'.$ldir.'/single.css' ;
  }

  elseif( is_category() || is_tag() || is_tax() || is_search() || is_404() ){
    $cssContents['category'] = get_template_directory().'/assets/css/'.$ldir.'/single.css' ;
  }

  return $cssContents;
}


function jawda_get_color($id)
{
  $d = [ 1 => '#DD3333', 2 => '#235B4E', 3 => '#424242' ];
  for ($i=1; $i <= 3; $i++) {
    $code = carbon_get_theme_option( 'jawda_color_'.$i );
    if ( $code !== NULL AND $code !== "" ) {
      $d[$i] = $code;
    }
  }
  return $d[$id];
}

// Hook to enqueue styles and scripts
function jawda_enqueue_assets() {
    get_my_styles();
    get_my_scripts();
}
add_action( 'wp_enqueue_scripts', 'jawda_enqueue_assets' );