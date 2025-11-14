<?php

// Security Check
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/**
 * Main function to update or create catalogs for each location and the master catalog, with Polylang integration.
 */
function jawda_update_location_catalogs() {
    global $wpdb;

    if (!function_exists('pll_save_post_translations') || !function_exists('pll_set_post_language')) {
        return;
    }

    // --- Master Catalog ---
    $total_projects_ar = count_projects_for_location(null, null, 'ar');
    $total_projects_en = count_projects_for_location(null, null, 'en');
    $master_catalog_ar_id = find_master_catalog('ar');
    $master_catalog_en_id = find_master_catalog('en');

    $master_title_ar = $total_projects_ar . ' ' . 'مشاريع جديدة وتحت الانشاء في مصر';
    $master_title_en = $total_projects_en . ' ' . 'off-plan and new projects in egypt';
    $master_slug_ar = 'مشاريع-جديدة-في-مصر';
    $master_slug_en = 'new-projects-in-egypt';

    $master_catalog_ar_id = upsert_catalog($master_catalog_ar_id, $master_title_ar, $master_slug_ar, true, 'ar', 0);
    $master_catalog_en_id = upsert_catalog($master_catalog_en_id, $master_title_en, $master_slug_en, true, 'en', 0);

    if ($master_catalog_ar_id && $master_catalog_en_id) {
        pll_save_post_translations(['ar' => $master_catalog_ar_id, 'en' => $master_catalog_en_id]);
        update_post_meta($master_catalog_ar_id, 'jawda_is_master_catalog', 'yes');
        update_post_meta($master_catalog_en_id, 'jawda_is_master_catalog', 'yes');
    }

    $gov_map_ar = [];
    $gov_map_en = [];

    // --- Governorates ---
    $governorates = $wpdb->get_results("SELECT id, name_ar, name_en FROM {$wpdb->prefix}locations_governorates");
    foreach ($governorates as $gov) {
        $project_count_ar = count_projects_for_location('loc_governorate_id', $gov->id, 'ar');
        $project_count_en = count_projects_for_location('loc_governorate_id', $gov->id, 'en');

        $cat_ar_id = find_catalog_by_location('governorate', $gov->id, 'ar');
        $cat_en_id = find_catalog_by_location('governorate', $gov->id, 'en');

        $title_ar = $project_count_ar . ' ' . 'مشاريع جديدة في' . ' ' . $gov->name_ar;
        $title_en = $project_count_en . ' ' . 'New Projects in' . ' ' . $gov->name_en;

        $slug_ar = sanitize_title_with_dashes($gov->name_ar, '', 'ar');
        $slug_en = sanitize_title($gov->name_en);

        $cat_ar_id = upsert_catalog($cat_ar_id, $title_ar, $slug_ar, true, 'ar', 0, 'governorate', $gov->id);
        $cat_en_id = upsert_catalog($cat_en_id, $title_en, $slug_en, true, 'en', 0, 'governorate', $gov->id);

        if ($cat_ar_id && $cat_en_id) {
            pll_save_post_translations(['ar' => $cat_ar_id, 'en' => $cat_en_id]);
            $gov_map_ar[$gov->id] = $cat_ar_id;
            $gov_map_en[$gov->id] = $cat_en_id;
        }
    }

    // --- Cities ---
    $city_map_ar = [];
    $city_map_en = [];
    $cities = $wpdb->get_results("SELECT id, name_ar, name_en, governorate_id FROM {$wpdb->prefix}locations_cities");
    foreach ($cities as $city) {
        $project_count_ar = count_projects_for_location('loc_city_id', $city->id, 'ar');
        $project_count_en = count_projects_for_location('loc_city_id', $city->id, 'en');

        $parent_ar_id = $gov_map_ar[$city->governorate_id] ?? 0;
        $parent_en_id = $gov_map_en[$city->governorate_id] ?? 0;

        $cat_ar_id = find_catalog_by_location('city', $city->id, 'ar');
        $cat_en_id = find_catalog_by_location('city', $city->id, 'en');

        $title_ar = $project_count_ar . ' ' . 'مشاريع جديدة في' . ' ' . $city->name_ar;
        $title_en = $project_count_en . ' ' . 'New Projects in' . ' ' . $city->name_en;

        $slug_ar = sanitize_title_with_dashes($city->name_ar, '', 'ar');
        $slug_en = sanitize_title($city->name_en);

        $cat_ar_id = upsert_catalog($cat_ar_id, $title_ar, $slug_ar, true, 'ar', $parent_ar_id, 'city', $city->id);
        $cat_en_id = upsert_catalog($cat_en_id, $title_en, $slug_en, true, 'en', $parent_en_id, 'city', $city->id);

        if ($cat_ar_id && $cat_en_id) {
            pll_save_post_translations(['ar' => $cat_ar_id, 'en' => $cat_en_id]);
            $city_map_ar[$city->id] = $cat_ar_id;
            $city_map_en[$city->id] = $cat_en_id;
        }
    }

    // --- Districts ---
    $districts = $wpdb->get_results("SELECT id, name_ar, name_en, city_id FROM {$wpdb->prefix}locations_districts");
    foreach ($districts as $district) {
        $project_count_ar = count_projects_for_location('loc_district_id', $district->id, 'ar');
        $project_count_en = count_projects_for_location('loc_district_id', $district->id, 'en');

        $parent_ar_id = $city_map_ar[$district->city_id] ?? 0;
        $parent_en_id = $city_map_en[$district->city_id] ?? 0;

        $cat_ar_id = find_catalog_by_location('district', $district->id, 'ar');
        $cat_en_id = find_catalog_by_location('district', $district->id, 'en');

        $title_ar = $project_count_ar . ' ' . 'مشاريع جديدة في' . ' ' . $district->name_ar;
        $title_en = $project_count_en . ' ' . 'New Projects in' . ' ' . $district->name_en;

        $slug_ar = sanitize_title_with_dashes($district->name_ar, '', 'ar');
        $slug_en = sanitize_title($district->name_en);

        $cat_ar_id = upsert_catalog($cat_ar_id, $title_ar, $slug_ar, true, 'ar', $parent_ar_id, 'district', $district->id);
        $cat_en_id = upsert_catalog($cat_en_id, $title_en, $slug_en, true, 'en', $parent_en_id, 'district', $district->id);

        if ($cat_ar_id && $cat_en_id) {
            pll_save_post_translations(['ar' => $cat_ar_id, 'en' => $cat_en_id]);
        }
    }
}


/**
 * Helper to insert or update a catalog post.
 */
function upsert_catalog($post_id, $title, $slug, $is_published, $lang, $parent_id = 0, $loc_type = null, $loc_id = null) {
    // Check for duplicate slug within the same hierarchy
    $path = $slug;
    if ($parent_id) {
        $parent_slug = get_post_field('post_name', $parent_id);
        $path = $parent_slug . '/' . $slug;
    }

    $existing_post = get_page_by_path($path, OBJECT, 'catalogs');
    if ($existing_post && (!$post_id || $existing_post->ID != $post_id)) {
        // If a slug exists but it's for the post we are trying to update, that's fine.
        // If it's for a different post, we have a conflict.
        if($post_id && $existing_post->ID == $post_id) {
           // Not a conflict, it's the same post.
        } else {
            $notifications = get_transient('jawda_catalog_slug_errors');
            if (!is_array($notifications)) {
                $notifications = [];
            }
            $notifications[] = sprintf('Could not create/update catalog "%s" because slug "%s" is already in use by Post ID %d.', $title, $path, $existing_post->ID);
            set_transient('jawda_catalog_slug_errors', $notifications, HOUR_IN_SECONDS);
            return $post_id ? $post_id : null; // Return existing ID if updating, null if creating
        }
    }

    $post_data = [
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => $is_published ? 'publish' : 'draft',
        'post_type' => 'catalogs',
        'post_parent' => $parent_id,
    ];

    if ($post_id) {
        $post_data['ID'] = $post_id;
        wp_update_post($post_data);
    } else {
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
             $notifications = get_transient('jawda_catalog_slug_errors');
            if (!is_array($notifications)) $notifications = [];
            $notifications[] = sprintf('Error creating catalog "%s": %s', $title, $post_id->get_error_message());
            set_transient('jawda_catalog_slug_errors', $notifications, HOUR_IN_SECONDS);
            return null;
        }
    }

    if (!is_wp_error($post_id)) {
        pll_set_post_language($post_id, $lang);
        update_post_meta($post_id, 'jawda_catalog_type', 1);
        if ($loc_type && $loc_id) {
            update_post_meta($post_id, 'jawda_location_type', $loc_type);
            update_post_meta($post_id, 'jawda_location_id', $loc_id);
            // Assuming loc_governorate_id, loc_city_id etc. are still needed for filtering.
            // This part might need adjustment based on how filtering will work with hierarchical CPTs.
            update_post_meta($post_id, 'loc_' . $loc_type . '_id', $loc_id);
        }
    }
    return $post_id;
}


/**
 * Helper function to count projects.
 */
function count_projects_for_location($meta_key = null, $location_id = null, $lang = null) {
    $args = [
        'post_type'      => 'projects',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'suppress_filters' => false,
    ];

    if ($meta_key && $location_id) {
        $args['meta_query'] = [
            [
                'key'   => $meta_key,
                'value' => $location_id,
            ],
        ];
    }

    if ($lang) {
        $args['lang'] = $lang;
    }

    $query = new WP_Query($args);

    return (int) $query->found_posts;
}

/**
 * Helper function to find a catalog by its location.
 */
function find_catalog_by_location($location_type, $location_id, $lang) {
    $args = [
        'post_type' => 'catalogs',
        'post_status' => ['publish', 'draft'],
        'lang' => $lang,
        'meta_query' => [
            'relation' => 'AND',
            ['key' => 'jawda_location_type', 'value' => $location_type],
            ['key' => 'jawda_location_id', 'value' => $location_id],
        ],
        'fields' => 'ids',
        'posts_per_page' => 1,
    ];
    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0] : null;
}

/**
 * Helper function to find the master catalog.
 */
function find_master_catalog($lang) {
    $args = [
        'post_type' => 'catalogs',
        'post_status' => ['publish', 'draft'],
        'lang' => $lang,
        'meta_key' => 'jawda_is_master_catalog',
        'meta_value' => 'yes',
        'fields' => 'ids',
        'posts_per_page' => 1,
    ];
    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0] : null;
}

// Schedule the daily event
if ( ! wp_next_scheduled( 'jawda_daily_catalog_update' ) ) {
    wp_schedule_event( time(), 'daily', 'jawda_daily_catalog_update' );
}

add_action( 'jawda_daily_catalog_update', 'jawda_update_location_catalogs' );

// One-time event to ensure catalogs are created immediately after deployment
if (!get_option('jawda_catalogs_initialized_polylang')) {
    wp_schedule_single_event(time() + 5, 'jawda_daily_catalog_update');
    update_option('jawda_catalogs_initialized_polylang', true);
}

/**
 * Display catalog creation errors in the admin dashboard.
 */
add_action('admin_notices', 'jawda_display_catalog_errors');
function jawda_display_catalog_errors() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $errors = get_transient('jawda_catalog_slug_errors');

    if ($errors && is_array($errors)) {
        foreach ($errors as $error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        }
        delete_transient('jawda_catalog_slug_errors');
    }
}
