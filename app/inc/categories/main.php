<?php
/**
 * Main loader for the Categories feature.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load database setup.
require_once __DIR__ . '/db.php';

// Load admin menu pages, but not during AJAX requests.
if (is_admin() && !wp_doing_ajax()) {
    require_once __DIR__ . '/admin-menu.php';
}

// Load template helpers.
require_once __DIR__ . '/template-helpers.php';
