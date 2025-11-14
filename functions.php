<?php

use Tracy\OutputDebugger;

/**
 * Block IPs found in blocklist.txt.
 *
 * This function is hooked to 'init' to ensure that all necessary functions,
 * like getUserIP(), are loaded before it runs.
 */
function jawda_block_spammer_ips() {
    $blocklist_path = ABSPATH . 'blocklist.txt';
    if ( file_exists( $blocklist_path ) ) {
        $blocked_ips = file( $blocklist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( in_array( getUserIP(), $blocked_ips, true ) ) {
            header( 'HTTP/1.0 403 Forbidden' );
            die( 'Your IP address has been blocked.' );
        }
    }
}
add_action( 'init', 'jawda_block_spammer_ips' );


/* -----------------------------------------------------
# Define Directories
----------------------------------------------------- */
define('ROOT_DIR', dirname(__FILE__));
define('CLASS_DIR', ROOT_DIR . '/app/classes/');
define('FUNC_DIR', ROOT_DIR . '/app/functions/');
define('TEMP_DIR', ROOT_DIR . '/app/templates/');

/* -----------------------------------------------------
# Define URLs and Paths
----------------------------------------------------- */
define('siteurl', get_site_url());
define('sitename', get_bloginfo('name'));
define('wpath', get_template_directory());
define('wurl', get_template_directory_uri());
define('wcssurl', wurl . '/assets/css/');
define('wfavurl', wurl . '/assets/favicons/');
define('wfonturl', wurl . '/assets/fonts/');
define('wimgurl', wurl . '/assets/images/');
define('wjsurl', wurl . '/assets/js/');

/* -----------------------------------------------------
# Define Secret Key
----------------------------------------------------- */
define('scrtky', 'SaBrY2585Trmd_df@#!ki5&5d8d*_8');

/* -----------------------------------------------------
# Load Composer Autoload
----------------------------------------------------- */
include_once(wpath . '/app/vendor/autoload.php');

/* -----------------------------------------------------
# Load Functions
----------------------------------------------------- */
$functionslist = [
    'basics', 'helper', 'menus', 'minifier', 'settings', 'post_types',
    'payment_plans', 'meta_box', 'styles', 'form_handler', 'tgm', 'schema',
    'pagination', 'shortcodes', 'editor_buttons', 'jawda_leads',
    'jawda_leads_download', 'translate', 'smtp_settings', 'smtp_mailer', 'locations-migrator', 'aqarand-locations-admin/aqarand-locations-admin', 'auto_catalog'
];
load_my_files($functionslist, FUNC_DIR);

/* -----------------------------------------------------
# Load Modular Features (inc)
----------------------------------------------------- */
if (file_exists(ROOT_DIR . '/app/inc/main.php')) {
    require_once ROOT_DIR . '/app/inc/main.php';
}

/* -----------------------------------------------------
# Load Templates
----------------------------------------------------- */
load_all_files(TEMP_DIR);

/* -----------------------------------------------------
# Loader Functions
----------------------------------------------------- */

// Load multiple PHP files
function load_my_files($files, $path) {
    foreach ($files as $filename) {
        $filepath = $path . $filename . '.php';
        if (file_exists($filepath)) {
            include_once($filepath);
        }
    }
}

// Load all PHP files in a directory recursively
function load_all_files($directory) {
    if (is_dir($directory)) {
        $scan = scandir($directory);
        unset($scan[0], $scan[1]);
        foreach ($scan as $file) {
            if (is_dir($directory . '/' . $file)) {
                load_all_files($directory . '/' . $file);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                include_once($directory . '/' . $file);
            }
        }
    }
}
