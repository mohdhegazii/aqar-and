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
  $style .= '</style>'."\n";
  echo $style;

}

function get_my_scripts(){
  $ldir = is_rtl() ? "rtl" : "ltr" ;
  $search_nonce = wp_create_nonce('search_nonce_action');
  echo '<script>var global = {"ajax":'.json_encode( admin_url( "admin-ajax.php" ) ).'};</script>'."\n";
  echo '<script>var search_nonce = {"nonce":"'.$search_nonce.'"}</script>';
  echo '<script>window.aqarandDisableLegacySiteformHandler = true;</script>'."\n";
  echo '<script src="'.get_template_directory_uri().'/assets/js/'.$ldir.'/script.js?v=01"></script>'."\n";
  echo '<script src="'.wjsurl.'main.js?v=1.0"></script>'."\n";
  ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const moreLessButton = document.querySelector('.more-less-button');
      if (moreLessButton) {
        moreLessButton.addEventListener('click', function () {
          const moreLinks = document.querySelector('.more-links');
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
  $d = [ 1 => '#DD3333', 2 => '#000000', 3 => '#424242' ];
  for ($i=1; $i <= 3; $i++) {
    $code = carbon_get_theme_option( 'jawda_color_'.$i );
    if ( $code !== NULL AND $code !== "" ) {
      $d[$i] = $code;
    }
  }
  return $d[$id];
}