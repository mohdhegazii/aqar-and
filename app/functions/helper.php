<?php

function get_current_page_url(){
  global $wp;
  echo home_url(add_query_arg(array(), $wp->request));
}

/* -----------------------------------------------------------------------------
// show_cats
----------------------------------------------------------------------------- */

function show_cats(){
  $output = '';
  $post_cats = wp_get_post_categories( get_the_ID() );
  if (!empty($post_cats)) {
      foreach ($post_cats as $c) {
        $cat = get_category( $c );
        $output .= '<a href="' . get_tag_link($cat->term_id) . '">' . esc_html($cat->name) . '</a>';
      }
      $output = trim($output);
  }
  echo $output;
}



function breadcrumbs_cat(){
  $link = $name = '';
  $post_cats = wp_get_post_categories( get_the_ID() );
  if (!empty($post_cats)) {
    $cat = get_category( $post_cats[0] );
    $link = get_tag_link($cat->term_id);
    $name = $cat->name;
  }
  return ["link" => $link,"name" => $name];
}


/* -----------------------------------------------------------------------------
-- show_tags
----------------------------------------------------------------------------- */
function show_tags(){
    $output = '';
    $post_tags = get_the_tags();
    $separator = '';
    if (!empty($post_tags)) {
        foreach ($post_tags as $tag) {
            $output .= '<a href="' . esc_url(get_tag_link($tag->term_id)) . '">' . esc_html($tag->name) . '</a>' . $separator;
        }
        return trim($output, $separator);
    }
}


/* -----------------------------------------------------------------------------
// theme_share_buttons
----------------------------------------------------------------------------- */

function Myterml($termname){
  $res = false;
  $terms = get_the_terms( get_the_ID(), $termname );
  if ( $terms && ! is_wp_error( $terms ) ) :
  $res = '';
  foreach ( $terms as $term ) {
      $term_link = get_term_link( $term );
      if ( is_wp_error( $term_link ) ) {
          continue;
      }
      $res .= '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a>';
  }
  endif;
  return $res;
}

function Myterml_id($termname,$projectid){
  $res = false;
  $terms = get_the_terms( $projectid, $termname );
  if ( $terms && ! is_wp_error( $terms ) ) :
  $res = '';
  foreach ( $terms as $term ) {
      $term_link = get_term_link( $term );
      if ( is_wp_error( $term_link ) ) {
          continue;
      }
      $res .= '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a>';
  }
  endif;
  return $res;
}


function featured_city_tag($id,$termname){
  $res = false;
  $terms = get_the_terms( $id, $termname );
  if ( $terms && ! is_wp_error( $terms ) ) :
  $res = '';
      $term_link = get_term_link( $terms[0] );
      $res .= '<a href="' . esc_url( $term_link ) . '" class="featured-tag">' . $terms[0]->name . '</a>';
  endif;
  return $res;
}


/* -----------------------------------------------------------------------------
// get_developer_img
----------------------------------------------------------------------------- */

function get_developer_img($id){
  $term_obj_list = get_the_terms( $id, 'developer' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    $termid = $term_obj_list[0]->term_id;
    $img_id = carbon_get_term_meta( $termid, 'jawda_dev_logo' );
    $image = wp_get_attachment_url($img_id);
    return $image;
  }
  return '';
}


function get_developer_desc($id){
  $term_obj_list = get_the_terms( $id, 'developer' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    $termid = $term_obj_list[0]->term_id;
    $devdesc = carbon_get_term_meta( $termid, 'jawda_dev_desc' );
    return $devdesc;
  }
  return '';
}

/* -----------------------------------------------------------------------------
// get_developer_name
----------------------------------------------------------------------------- */

function get_developer_name($id){
  $term_obj_list = get_the_terms( $id, 'projects_developer' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    return $term_obj_list[0]->name;
  }
  return '';
}

function get_developer_link($id){
  $term_obj_list = get_the_terms( $id, 'projects_developer' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    $term_link = get_term_link( $term_obj_list[0] );
    if ( ! is_wp_error( $term_link ) ) {
      return esc_url( $term_link );
    }
  }
  return '';
}


/* -----------------------------------------------------------------------------
// get_developer_name
----------------------------------------------------------------------------- */

function get_developer_box($id){
  $term_obj_list = get_the_terms( $id, 'projects_developer' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    return $term_obj_list[0]->name;
  }
  return '';
}

/* -----------------------------------------------------------------------------
// get_developer_name
----------------------------------------------------------------------------- */

function get_property_city_name($id){
  $term_obj_list = get_the_terms( $id, 'property_city' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    return $term_obj_list[0]->name;
  }
  return '';
}

/* -----------------------------------------------------------------------------
// get_developer_name
----------------------------------------------------------------------------- */

function get_property_city_link($id){
  $term_obj_list = get_the_terms( $id, 'property_city' );
  if ( ! empty( $term_obj_list ) && ! is_wp_error( $term_obj_list ) ) {
    $term_link = get_term_link( $term_obj_list[0] );
    if ( ! is_wp_error( $term_link ) ) {
        return esc_url( $term_link );
    }
  }
  return '';
}

/* -----------------------------------------------------------------------------
// get_excerpt
----------------------------------------------------------------------------- */

function get_my_excerpt( $count ) {
  global $post;
  $excerpt = get_the_content();
  $excerpt = strip_tags($excerpt);
  $excerpt = substr($excerpt, 0, $count);
  $excerpt = substr($excerpt, 0, strripos($excerpt, " "));
  return $excerpt;
}

/* -----------------------------------------------------------------------------
// get_excerpt
----------------------------------------------------------------------------- */

function get_text( $ar,$en ) {
  $text = is_rtl() ? $ar : $en;
  echo $text;
}

/* -----------------------------------------------------------------------------
// List Of Categories
----------------------------------------------------------------------------- */

function theme_list_of_categoris()
{
    $args = array('order' => 'ASC');
    $categories = get_categories($args);

    $order_options = array('all' => 'All Categories');
    $categories = get_categories('orderby=name&hide_empty=0');
    foreach ($categories as $category):
        $catids = $category->term_id;
        $catname = $category->name;
        $order_options[$catids] = $catname;
    endforeach;
    return $order_options;
}

/* -----------------------------------------------------------------------------
Pages List
----------------------------------------------------------------------------- */

function my_pages_list(){
  $pages = get_pages();
  $pagearay = [];
  if( !empty($pages) ){
    foreach ($pages as $page) {
      $pagearay[$page->ID] = $page->post_title;
    }
  }
  return $pagearay;
}

/* -----------------------------------------------------------------------------
Projects List
----------------------------------------------------------------------------- */

function my_projects_list(){
  $pagearay['0'] = 'No Project';
  $args = array( 'post_type' => 'project', 'posts_per_page' => -1 );
  $query = new WP_Query($args);
  if ($query->have_posts() ) :
  while ( $query->have_posts() ) : $query->the_post();
      $pagearay[get_the_ID()] = get_the_title();
  endwhile;
  wp_reset_postdata();
  endif;
  return $pagearay;
}

/* -----------------------------------------------------------------------------
locations List
----------------------------------------------------------------------------- */

function my_locations_list(){
  $pagearay['0'] = is_rtl() ? 'كل المواقع' : 'All Locations';
  $locations = get_terms( array( 'taxonomy' => 'city','hide_empty' => true,'parent' => 0) );
  foreach ($locations as $location) {
    $pagearay[$location->term_id] = $location->name ;
  }
  return $pagearay;
}

/* -----------------------------------------------------------------------------
locations List
----------------------------------------------------------------------------- */

function my_types_list(){
  $pagearay['0'] = is_rtl() ? 'كل العقارات' : 'All Types';
  $locations = get_terms( array( 'taxonomy' => 'type','hide_empty' => true,'parent' => 0) );
  foreach ($locations as $location) {
    $pagearay[$location->term_id] = $location->name ;
  }
  return $pagearay;
}


/* -----------------------------------------------------------------------------
Pages List
----------------------------------------------------------------------------- */

function get_youtube_id($url){
  $youtube_id = '';
  if ( is_string($url) && preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match) ) {
    if(isset($match[1])) {
        $youtube_id = $match[1];
    }
  }
  return $youtube_id;
}

/* -----------------------------------------------------------------------------
-- Set token
----------------------------------------------------------------------------- */
function formtoken($form){
  $value = md5(uniqid(rand(), TRUE));
  $_SESSION['token_'.$form] = $value;
  return $_SESSION['token_'.$form];
}


/* -----------------------------------------------------------------------------
-- token_validator
----------------------------------------------------------------------------- */

function token_validator($tsession,$tpost){
  $resulte = true;
  $session = $_SESSION['token_'.$tsession];
  if(!isset($session)){$resulte = false;}
  if( $session !== $tpost ) {$resulte = false;}
  return $resulte;
}

/* -----------------------------------------------------------------------------
# Checking referrer
----------------------------------------------------------------------------- */
function check_referrer(){
  $allowed_host = $_SERVER['HTTP_HOST'];
  $host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
  if(substr($host, 0 - strlen($allowed_host)) != $allowed_host) {
    flush();
    sleep(1);
    gotourl($allowed_host);
  }
}

/* -----------------------------------------------------------------------------
# Get Real Ip
----------------------------------------------------------------------------- */

function getUserIP(){
  if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
  }
  $client  = @$_SERVER['HTTP_CLIENT_IP'];
  $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
  $remote  = $_SERVER['REMOTE_ADDR'];
  if(filter_var($client, FILTER_VALIDATE_IP)){$ip = $client;}
  elseif(filter_var($forward, FILTER_VALIDATE_IP)){$ip = $forward;}
  else{$ip = $remote;}
  return $ip;
}

/* -----------------------------------------------------------------------------
# Check country
----------------------------------------------------------------------------- */
function check_country($ip){
  $access_key = '0a6782a745b4df73e7c149549e6f0aef';
  $ch = curl_init('http://api.ipstack.com/'.$ip.'?access_key='.$access_key.'');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $json = curl_exec($ch);
  curl_close($ch);
  $api_result = json_decode($json, true);
  $return = 'Not Found';
  if( isset($api_result['country_name']) AND $api_result['country_name'] != '' ){
    $return = $api_result['country_name'];
  }
  return $return;
}


/* -----------------------------------------------------------------------------
-- decrypt
----------------------------------------------------------------------------- */

function decrypt($string, $key) {
  $result = '';
  $string = base64_decode($string);
  for($i=0; $i<strlen($string); $i++) {
    $char = substr($string, $i, 1);
    $keychar = substr($key, ($i % strlen($key))-1, 1);
    $char = chr(ord($char)-ord($keychar));
    $result.=$char;
  }
  return $result;
}

/* -----------------------------------------------------------------------------
-- encrypt
----------------------------------------------------------------------------- */

function encrypt($string, $key) {
  $result = '';
  for($i=0; $i<strlen($string); $i++) {
    $char = substr($string, $i, 1);
    $keychar = substr($key, ($i % strlen($key))-1, 1);
    $char = chr(ord($char)+ord($keychar));
    $result.=$char;
  }
  return base64_encode($result);
}


/* -----------------------------------------------------------------------------
-- goto
----------------------------------------------------------------------------- */

function gotourl( $url ){
  if(headers_sent()) {
    die("<script>window.location.replace('$url');</script>");
  }
  else{
    header("Location: $url");
    exit;
  }
}

/* -----------------------------------------------------------------------------
-- format slug
----------------------------------------------------------------------------- */

function formatslug($str, $sep='-'){
  $res = strtolower($str);
  $res = preg_replace('/[^[:alnum:]]/', ' ', $res);
  $res = preg_replace('/[[:space:]]+/', $sep, $res);
  return trim($res, $sep);
}

/* -----------------------------------------------------------------------------
-- test_input
----------------------------------------------------------------------------- */
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  return $data;
}


/* -----------------------------------------------------------------------------
# Print_R
----------------------------------------------------------------------------- */
function fan6($key){
  echo "<pre>";
  print_r($key);
  echo "</pre>";
  die();
}

/* -----------------------------------------------------------------------------
# get_my_page_title
----------------------------------------------------------------------------- */
function get_my_page_title(){
  if(is_front_page() || is_home()){
        echo get_bloginfo('name');
    } else{
        echo wp_title('');
    }
}
/* -----------------------------------------------------------------------------
# get_whatsapp_link
----------------------------------------------------------------------------- */

function get_whatsapp_link($whatsapp){
  $title = '';
  $link = '';
  if(is_front_page() || is_home()){
        $title =  get_bloginfo('name');
        $link = siteurl;
    } else{
        $title =  get_the_title('');
        $link = get_the_permalink();
    }
  return 'https://wa.me/'.$whatsapp.'?text='.urlencode('مرحبا لدي استفسار عن مشروع : '.$title.' url :'.$link);
}


function get_whatsapp_pro_link($whatsapp,$id){
  $title =  get_the_title($id);
  $title .=  ' - '.get_the_permalink($id);
  return 'https://wa.me/'.$whatsapp.'?text='.urlencode('مرحبا لدي استفسار عن مشروع : '.$title);
}

/* -----------------------------------------------------------------------------
# Print_R
----------------------------------------------------------------------------- */

function search_helper($array)
{
  global $wpdb;

  $is_text = ( isset($array['s']) && trim($array['s']) !== '' ) ? true : false;
  $is_city = ( isset($array['city']) && trim($array['city']) !== '' && trim($array['city']) != 0 ) ? true : false;
  $is_type = ( isset($array['type']) && trim($array['type']) !== '' && trim($array['type']) != 0 ) ? true : false;
  $searcht = $array['st'];

  $cpt = ($searcht == 2) ? 'property' : 'projects';
  $tax_city = ($searcht == 2) ? 'property_city' : 'projects_area';
  $tax_type = ($searcht == 2) ? 'property_type' : 'projects_type';

  $return = [];
  $return['post_type'] = [$cpt];
  if( is_array($array) )
  {
    if( $is_text ){ $return['s'] = $array['s']; }

    if( $is_city || $is_type  ){
      $array['tax_query'] = [];
      if( $is_city && $is_type ){ $return['tax_query']['relation'] = 'AND'; }
      if( $is_city ){
        $return['tax_query'][] = ['taxonomy' => $tax_city,'field' => 'term_id','terms' => $array['city']];
      }
      if( $is_type ){
        $return['tax_query'][] = ['taxonomy' => $tax_type,'field' => 'term_id','terms' => $array['type']];
      }
    }

    if( isset($array['postcount']) AND $array['postcount'] == 'all' )
    {
    }

  }
  return $return;
}

/* -----------------------------------------------------------------------------
# Print_R
----------------------------------------------------------------------------- */

function search_parameters_filter($parameters) {
  $allowed_keys = ['st','s','type','city','postcount'];
  $return = [];
  foreach ($parameters as $key => $value) {
    if( in_array($key, $allowed_keys) )
    {
      $return[$key] = $value;
    }
  }
  return $return;
}


function jawda_home_link()
{
  $lan = is_rtl() ? '/' : '/en';
  return siteurl.$lan;
}



/* -----------------------------------------------------------------------------
// theme_share_buttons
----------------------------------------------------------------------------- */

function theme_share_buttons(){
  ob_start();
  ?>
  <ul>
    <li>
      <a target="_blank" href="https://www.facebook.com/sharer.php?u=<?php the_permalink(); ?>"><i class="icon-facebook" title="facebook"></i></a>
    </li>
    <li>
      <a target="_blank" href="http://pinterest.com/pin/create/link/?url=<?php the_permalink(); ?>"><i class="icon-pinterest" title="pinterest"></i></a>
    </li>
    <li>
      <a target="_blank" href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>"><i class="icon-twitter" title="twitter"></i></a>
    </li>
    <li>
      <a target="_blank" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php the_permalink(); ?>"><i class="icon-linkedin" title="linkedin"></i></a>
    </li>
  </ul>
  <?php
  $content = ob_get_clean();
  echo minify_html($content);
}




function jawda_last_updated_date() {
  $updated_date = get_the_time('Y-m-d');
  if ( get_the_modified_time('U') ) {
    $updated_date = get_the_modified_time('Y-m-d');
  }
  return $updated_date;
}





add_filter('wpseo_title', 'filter_wpseo_title');
function filter_wpseo_title($v = '') {
  $r = do_shortcode($v);
  return $r;
}


add_shortcode('txt', 'jawda_title_text');
function jawda_title_text($atts = []) {
  $atts = array_change_key_case( (array) $atts, CASE_LOWER );
  $page_num = jawda_pagination_page();
  $text = [
    1 => [ 'ar' => 'صفحة النتائج','en' => 'Result page' ],
  ];
  $lang = is_rtl() ? 'ar' : 'en';
  if( isset($atts['t']) )
  {
    if ($atts['t'] == 1 AND !in_array($page_num, [0,1]) ) {
      $key = $atts['t'];
      if( isset($text[$key][$lang]) )
      {
        return $text[$key][$lang].' '.$page_num;
      }
    }

  }
}


function jawda_pagination_page()
{
  return get_query_var( 'paged', 1 );
}
