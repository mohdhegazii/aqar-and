<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Contact Form
----------------------------------------------------------------------------- */

function my_contact_form(){
  $langu = is_rtl() ? 'ar' : 'en' ;
  $page_title = ( is_front_page() || is_home() ) ? get_bloginfo( 'name' ) : wp_title( '', false );
  global $wp;
  $current_url = home_url( add_query_arg( array(), $wp->request ) );
  $package_value = esc_attr( trim( $page_title ) . ' ' . $current_url );
  ?>
  <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="siteform">
    <?php wp_nonce_field( 'my_contact_form_action', 'my_contact_form_nonce' ); ?>
    <input type="hidden" name="langu" value="<?php echo $langu; ?>">
    <input type="hidden" name="action" value="my_contact_form">
    <input type="hidden" name="packageid" value="<?php echo $package_value; ?>">
    <input type="text" name="contact_me_by_fax_only" style="opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1;" autocomplete="off" tabindex="-1">
    <input name="name" placeholder="<?php get_text('الاسم','First Name'); ?> *" class="form-bg" aria-label="first-name" required>
    <input type="text" id="phone" name="phone" class="form-bg" placeholder="<?php get_text('رقم الهاتف','Phone Number'); ?> *" aria-label="contact-phone" required>
    <input name="email" type="text" class="form-bg" placeholder="<?php get_text('البريد الإلكتروني','Email'); ?>" aria-label="your-email">
    <textarea required name="special_request" cols="10" rows="1" placeholder="<?php get_text('رسالتك','Your Message'); ?>" aria-label="your-comment" class="comment"></textarea>
    <input type="submit" value="<?php get_text('ارسال','Send'); ?>" class="submit">
  </form>

  <?php
}

function my_home_contact_form(){
  $langu = is_rtl() ? 'ar' : 'en' ;
  $page_title = ( is_front_page() || is_home() ) ? get_bloginfo( 'name' ) : wp_title( '', false );
  global $wp;
  $current_url = home_url( add_query_arg( array(), $wp->request ) );
  $package_value = esc_attr( trim( $page_title ) . ' ' . $current_url );
  ?>
  <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="siteform">
    <?php wp_nonce_field( 'my_contact_form_action', 'my_contact_form_nonce' ); ?>
    <input type="hidden" name="langu" value="<?php echo $langu; ?>">
    <input type="hidden" name="action" value="my_contact_form">
    <input type="hidden" name="packageid" value="<?php echo $package_value; ?>">
    <input type="text" name="contact_me_by_fax_only" style="opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1;" autocomplete="off" tabindex="-1">
    <span class="inputs-wrap">
      <input name="name" placeholder="<?php get_text('الاسم','First Name'); ?> *" class="form-bg" aria-label="first-name" required>
      <input type="text" id="phone_home" name="phone" class="form-bg" placeholder="<?php get_text('رقم الهاتف','Phone Number'); ?> *" aria-label="contact-phone" required>
      <input name="email" type="text" class="form-bg" placeholder="<?php get_text('البريد الإلكتروني','Email'); ?>" aria-label="your-email">
    </span>
    <textarea required name="special_request" cols="10" rows="1" placeholder="<?php get_text('رسالتك','Your Message'); ?>" aria-label="your-comment" class="comment"></textarea>
    <input type="submit" value="<?php get_text('ارسال','Send'); ?>" class="submit">
  </form>

  <?php
}


function my_contact_footer_form(){
  $langu = is_rtl() ? 'ar' : 'en' ;
  $page_title = ( is_front_page() || is_home() ) ? get_bloginfo( 'name' ) : wp_title( '', false );
  global $wp;
  $current_url = home_url( add_query_arg( array(), $wp->request ) );
  $package_value = esc_attr( trim( $page_title ) . ' ' . $current_url );
  ?>

<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="siteform" id="myform">
  <?php wp_nonce_field( 'my_contact_form_action', 'my_contact_form_nonce' ); ?>
  <input type="hidden" name="langu" value="<?php echo $langu; ?>">
  <input type="hidden" name="action" value="my_contact_form">
  <input type="hidden" name="packageid" value="<?php echo $package_value; ?>">
  <input type="text" name="contact_me_by_fax_only" style="opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1;" autocomplete="off" tabindex="-1">
  <input id="fname" name="name" placeholder="<?php get_text('الاسم','First Name'); ?> *" class="form-bg half-r"
    aria-label="your-name" required>
  <input type="text" id="fphone" name="phone" class="form-bg half-l" placeholder="<?php get_text('رقم الهاتف','Phone Number'); ?> *"
    aria-label="contact-phone" required>
  <input id="femail" name="email" type="text" class="form-bg"
    placeholder="<?php get_text('البريد الإلكتروني','Email'); ?> *" aria-label="your-email" required>
  <textarea id="fmessage" name="special_request" cols="10" rows="1"
    placeholder="<?php get_text('رسالتك','Your Message'); ?>" aria-label="your-comment" class="comment"></textarea>
  <input type="submit" value="<?php get_text('ارسال','Send'); ?>" class="submit">
</form>

  <?php
}
