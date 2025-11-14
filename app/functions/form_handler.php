<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Get Real Ip
----------------------------------------------------------------------------- */
if ( ! function_exists( 'getUserIP' ) ) {
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
}


function set_mail_content_type(){ return "text/html"; }
add_filter( 'wp_mail_content_type','set_mail_content_type' );

// Track whether we should force the secondary SMTP configuration.
global $aqarand_force_secondary_smtp;
if ( ! isset( $aqarand_force_secondary_smtp ) ) {
  $aqarand_force_secondary_smtp = false;
}


function prefix_send_email_to_admin() {

  // Language
  $lang = ( isset($_POST['langu']) && $_POST['langu'] == 'ar' ) ? 'ar' : 'en';
  $is_ajax = ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'WP_TESTS_DOMAIN' );

  // Honeypot check for spam
  if ( isset( $_POST['contact_me_by_fax_only'] ) && ! empty( $_POST['contact_me_by_fax_only'] ) ) {
    $blocklist_path = ABSPATH . 'blocklist.txt';
    $ip = getUserIP();

    // Add the IP to the blocklist if it's not already there
    $current_blocklist = file_exists( $blocklist_path ) ? file_get_contents( $blocklist_path ) : '';
    if ( strpos( $current_blocklist, $ip ) === false ) {
      file_put_contents( $blocklist_path, $ip . PHP_EOL, FILE_APPEND );
    }

    // Silently fail to prevent spammer from knowing they were caught
    if ( $is_ajax ) {
      wp_send_json_success();
    } else {
      wp_redirect( home_url( '/' ) );
    }
    exit;
  }


  $send_error = function( $message ) use ( $is_ajax ) {
    if ( $is_ajax ) {
      wp_send_json_error( [ 'message' => $message ] );
    } else {
      wp_die( $message );
    }
  };

  // Verify nonce
  if ( ! isset( $_POST['my_contact_form_nonce'] ) || ! wp_verify_nonce( $_POST['my_contact_form_nonce'], 'my_contact_form_action' ) ) {
    $error_message = get_text_lang('عذراً، حدث خطأ ما.','Sorry, something went wrong.',$lang, false);
    $send_error( $error_message );
    return;
  }

  // Thank you page
  $thankyou_page_id = carbon_get_theme_option( 'jawda_page_thankyou_'.$lang );
  $thankyou = $thankyou_page_id ? get_page_link($thankyou_page_id) : home_url('/');


  // Site Email
  $email_to = '';
  if ( function_exists( 'carbon_get_theme_option' ) ) {
    $email_to = carbon_get_theme_option( 'jawda_email' );

    if ( empty( $email_to ) ) {
      $email_to = carbon_get_theme_option( '_jawda_email' );
    }
  }

  if ( empty( $email_to ) ) {
    $email_to = get_bloginfo( 'admin_email' );
  }

  $email_to = sanitize_email( $email_to );

  if ( empty( $email_to ) || ! filter_var( $email_to, FILTER_VALIDATE_EMAIL ) ) {
    $error_message = get_text_lang(
      'عذراً، لم يتم ضبط بريد الاستقبال بشكل صحيح. برجاء مراجعة الإعدادات.',
      'Sorry, the recipient email address is not configured correctly. Please review the settings.',
      $lang,
      false
    );
    $send_error( $error_message );
    return;
  }

  // Check Required Data
  if(
    !isset($_POST['name']) || empty($_POST['name'])
    || !isset($_POST['phone']) || empty($_POST['phone'])
    || !isset($_POST['packageid']) || $_POST['packageid'] === ''
  ){
    $error_message = get_text_lang('برجاء التأكد من اضافة جميع الحقول المطلوبة','Please make sure to add all required fields',$lang, false);
    $send_error( $error_message );
    return;
  }

    // package id
    $packagename = sanitize_text_field($_POST['packageid']);

    $name = str_replace( [ "\r", "\n" ], [ '', '' ], wp_strip_all_tags( trim( $_POST['name'] ) ) );
    $phone = wp_strip_all_tags(trim($_POST['phone']));

    $massege = ( isset($_POST['special_request']) AND $_POST['special_request'] != '' ) ? wp_strip_all_tags(trim($_POST['special_request'])) : 'لم يتم اضافة رسالة';

    $bHasLink = strpos($massege, 'http') !== false || strpos($massege, 'www.') !== false;

    if ( $bHasLink ) {
        $error_message = get_text_lang('غير مسموح بإضافة روابط في الرسالة.','It is not allowed to add links in the message.',$lang, false);
        $send_error( $error_message );
        return;
    }

    $headers = [ 'From: AqarAnd <wordpress@aqarand.com>' ];
    if( isset($_POST['email']) && $_POST['email'] != '' ){
      $email = sanitize_email($_POST['email']);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $error_message = get_text_lang('برجاء التأكد من ادخال بريد الكتروني صحيح.','Please make sure to enter a valid email.',$lang, false);
          $send_error( $error_message );
          return;
      }
      $headers[]   = 'Reply-To: '.$name.' <'.$email.'>';
    } else {
      $email = "لم يتم اضافته";
    }

    // Check Phone - validate for 11 digits (Egypt standard)
    if ( ! preg_match( '/^\d{11}$/', $phone ) ) {
        $error_message = get_text_lang('برجاء التأكد من ادخال رقم هاتف صحيح مكون من 11 رقم.','Please make sure to enter a valid 11-digit phone number.',$lang, false);
        $send_error( $error_message );
        return;
    }

    // Title Of Email
    $subject = "رسالة جديدة من العميل : ".$name;

    $message = "
    <html>
    <head>
    <title>" . esc_html($subject) . "</title>
    </head>
    <body>
      <h2>" . esc_html($subject) . "</h2>
      <table>
        <tr>
          <td><strong>اسم العميل : </strong></td>
          <td>" . esc_html($name) . "</td>
        </tr>
        <tr>
          <td><strong>ايميل العميل : </strong></td>
          <td>" . esc_html($email) . "</td>
        </tr>
        <tr>
          <td><strong>تليفون العميل : </strong></td>
          <td>" . esc_html($phone) . "</td>
        </tr>
        <tr>
          <td><strong>رسالة العميل : </strong></td>
          <td>" . esc_html($massege) . "</td>
        </tr>
        <tr>
          <td><strong>اسم المشروع / الإهتمام : </strong></td>
          <td>" . esc_html($packagename) . "</td>
        </tr>
      </table>
    </body>
    </html>
    ";

    global $aqarand_force_secondary_smtp;

    $aqarand_force_secondary_smtp = false;
    $send_mail = wp_mail($email_to, $subject, $message, $headers);

    if ( false === $send_mail ) {
        if ( aqarand_can_use_secondary_smtp() ) {
            $aqarand_force_secondary_smtp = true;
            $send_mail = wp_mail($email_to, $subject, $message, $headers);
            $aqarand_force_secondary_smtp = false;

            if ( ! $send_mail ) {
                error_log('[Mail Error] Secondary SMTP fallback failed.');
            }
        } else {
            error_log('[Mail Error] Secondary SMTP settings incomplete. Fallback not attempted.');
        }
    }

    if( $send_mail ){
        global $wpdb;
        $table = $wpdb->prefix.'leadstable';
        $data = array('name' => $name,'email' => $email,'phone' => $phone,'massege' => $massege,'packagename' => $packagename);
        $format = array('%s','%s','%s','%s','%s');
        $wpdb->insert($table,$data,$format);
        $my_id = $wpdb->insert_id;

        if ( $is_ajax ) {
            wp_send_json_success(['redirect' => $thankyou]);
            return;
        }

        wp_redirect($thankyou);
        exit;
    } else {
        $error_message = get_text_lang(
            'عذرا، فشل إرسال البريد. يرجى التحقق من إعدادات خادم البريد (SMTP).',
            'Sorry, the email could not be sent. Please check the server\'s mail (SMTP) configuration.',
            $lang,
            false
        );
        $send_error( $error_message );
    }
}
add_action( 'admin_post_nopriv_my_contact_form', 'prefix_send_email_to_admin' );
add_action( 'admin_post_my_contact_form', 'prefix_send_email_to_admin' );
add_action( 'wp_ajax_nopriv_my_contact_form', 'prefix_send_email_to_admin' );
add_action( 'wp_ajax_my_contact_form', 'prefix_send_email_to_admin' );

function aqarand_can_use_secondary_smtp() {
  if ( ! function_exists( 'carbon_get_theme_option' ) ) {
    return false;
  }

  $host     = carbon_get_theme_option( 'crb_smtp_host' );
  $port     = carbon_get_theme_option( 'crb_smtp_port' );
  $username = carbon_get_theme_option( 'crb_smtp_username' );
  $password = carbon_get_theme_option( 'crb_smtp_password' );

  return ! empty( $host ) && ! empty( $port ) && ! empty( $username ) && ! empty( $password );
}



function get_text_lang($st1, $st2, $lang, $echo = true){
  $return = ($lang == 'ar') ? $st1 : $st2;
  if ($echo) {
    echo $return;
  } else {
    return $return;
  }
}




function ja_ajax_search_properties() {
    check_ajax_referer( 'search_nonce_action', 'security' );

	$results = new WP_Query( array(
		'post_type'     => array( 'property' ),
		'post_status'   => 'publish',
    'posts_per_page' => 10,
		's'             => sanitize_text_field( $_POST['search'] ),
	) );

	$items = array();

	if ( !empty( $results->posts ) ) {
		foreach ( $results->posts as $result ) {
			$items[] = $result->post_title;
		}
	}

	wp_send_json_success( $items );
}
add_action( 'wp_ajax_search_properties',        'ja_ajax_search_properties' );
add_action( 'wp_ajax_nopriv_search_properties', 'ja_ajax_search_properties' );


function ja_ajax_search_projects() {
    check_ajax_referer( 'search_nonce_action', 'security' );

	$results = new WP_Query( array(
		'post_type'     => array( 'projects' ),
		'post_status'   => 'publish',
    'posts_per_page' => 10,
		's'             => sanitize_text_field( $_POST['search'] ),
	) );

	$items = array();

	if ( !empty( $results->posts ) ) {
		foreach ( $results->posts as $result ) {
			$items[] = $result->post_title;
		}
	}

	wp_send_json_success( $items );
}
add_action( 'wp_ajax_search_projects',        'ja_ajax_search_projects' );
add_action( 'wp_ajax_nopriv_search_projects', 'ja_ajax_search_projects' );
