<?php
/**
 * Main loader for modular features located in the /app/inc/ directory.
 * This file includes all the necessary components for new functionalities.
 *
 * @package Aqarand
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// --- Dependent Dropdowns Feature ---
// This feature adds AJAX-powered dependent dropdowns (Governorate -> City -> District)
// to the project post editor screen. The dropdown markup is provided by a custom
// meta box (see app/functions/meta_box.php).

// 1. Configuration: Defines constants for taxonomies and meta keys.
require_once __DIR__ . '/admin/cf-dependent-config.php';

// 2. AJAX Handlers: Sets up endpoints to fetch cities and projects dynamically.
require_once __DIR__ . '/admin/cf-dependent-ajax.php';

// 3. Quick & Bulk Edit: Adds location fields to the project list screen.
require_once __DIR__ . '/admin/quick-edit-locations.php';

// --- Categories System ---
// This feature adds a new 2-level classification system: Main Categories and Property Types.
require_once __DIR__ . '/categories/main.php';

// --- Project Features Lookups ---
// Provides bilingual feature lookups with media support.
require_once __DIR__ . '/project-features/main.php';
