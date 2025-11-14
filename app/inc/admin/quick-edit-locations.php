<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Adds custom location fields to the project quick edit screen.
 */
function jawda_add_location_to_quick_edit($column_name, $post_type) {
    if ($post_type !== 'projects') {
        return;
    }

    if ($column_name === 'taxonomy-projects_developer') { // A good column to hook after
        wp_nonce_field('jawda_location_quick_edit_nonce', 'location_quick_edit_nonce');
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <span class="title inline-edit-group-title"><?php _e('Location', 'jawda'); ?></span>
                <label class="alignleft">
                    <span class="title"><?php _e('Governorate', 'jawda'); ?></span>
                    <select name="loc_governorate_id" class="jawda-governorate-select"></select>
                </label>
                <label class="alignleft">
                    <span class="title"><?php _e('City', 'jawda'); ?></span>
                    <select name="loc_city_id" class="jawda-city-select"></select>
                </label>
                <label class="alignleft">
                    <span class="title"><?php _e('District', 'jawda'); ?></span>
                    <select name="loc_district_id" class="jawda-district-select"></select>
                </label>
            </div>
        </fieldset>
        <?php
    }
}
add_action('quick_edit_custom_box', 'jawda_add_location_to_quick_edit', 10, 2);

/**
 * Adds custom location fields to the project bulk edit screen.
 */
function jawda_add_location_to_bulk_edit($column_name, $post_type) {
    if ($post_type !== 'projects') {
        return;
    }

    if ($column_name === 'taxonomy-projects_developer') { // A good column to hook after
        wp_nonce_field('jawda_location_bulk_edit_nonce', 'location_bulk_edit_nonce');
        ?>
        <div class="inline-edit-group">
            <label class="alignleft">
                <span class="title"><?php _e('Governorate', 'jawda'); ?></span>
                <select name="loc_governorate_id" class="jawda-governorate-select">
                    <option value=""><?php _e('— No Change —', 'jawda'); ?></option>
                </select>
            </label>
            <label class="alignleft">
                <span class="title"><?php _e('City', 'jawda'); ?></span>
                <select name="loc_city_id" class="jawda-city-select">
                    <option value=""><?php _e('— No Change —', 'jawda'); ?></option>
                </select>
            </label>
            <label class="alignleft">
                <span class="title"><?php _e('District', 'jawda'); ?></span>
                <select name="loc_district_id" class="jawda-district-select">
                    <option value=""><?php _e('— No Change —', 'jawda'); ?></option>
                </select>
            </label>
        </div>
        <?php
    }
}
add_action('bulk_edit_custom_box', 'jawda_add_location_to_bulk_edit', 10, 2);

/**
 * Enqueues the javascript for the quick edit locations and localizes data.
 */
function jawda_enqueue_quick_edit_locations_js($hook) {
    if ($hook === 'edit.php' && get_current_screen()->post_type === 'projects') {
        wp_enqueue_script('jawda-quick-edit-locations', get_template_directory_uri() . '/app/inc/admin/js/quick-edit-locations.js', ['jquery', 'inline-edit-post'], '1.1', true);

        $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

        $select_gov_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'aqarand'));
        $select_city_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'aqarand'));
        $select_city_first_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'aqarand'));
        $select_gov_first_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'aqarand'));
        $select_district_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المنطقة —', __('— Select District —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المنطقة —' : __('— Select District —', 'aqarand'));

        wp_localize_script('jawda-quick-edit-locations', 'CF_DEP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cf_dep_nonce'),
            'language' => 'both',
            'i18n'     => [
                'loading'              => $is_ar ? '— جاري التحميل… —' : __('— Loading… —', 'aqarand'),
                'select_gov'           => $select_gov_placeholder,
                'select_gov_first'     => $select_gov_first_placeholder,
                'select_city'          => $select_city_placeholder,
                'select_city_first'    => $select_city_first_placeholder,
                'select_district'      => $select_district_placeholder,
            ]
        ]);
    }
}
add_action('admin_enqueue_scripts', 'jawda_enqueue_quick_edit_locations_js');

/**
 * Adds the project's location data to a hidden div in a column for easy access in javascript.
 */
function jawda_add_location_data_to_project_column($column, $post_id) {
    if ($column === 'taxonomy-projects_developer') {
        printf(
            '<div class="jawda-location-data" style="display:none;" data-gov-id="%s" data-city-id="%s" data-district-id="%s"></div>',
            esc_attr(get_post_meta($post_id, 'loc_governorate_id', true)),
            esc_attr(get_post_meta($post_id, 'loc_city_id', true)),
            esc_attr(get_post_meta($post_id, 'loc_district_id', true))
        );
    }
}
add_action('manage_projects_posts_custom_column', 'jawda_add_location_data_to_project_column', 10, 2);

/**
 * AJAX handler to get all governorates, respecting the current language.
 */
add_action('wp_ajax_cf_dep_get_governorates', function() {
    global $wpdb;
    check_ajax_referer('cf_dep_nonce', 'nonce');

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;
    $default_lang = $is_ar ? 'ar' : 'en';
    $requested_lang = isset($_GET['lang']) ? wp_unslash($_GET['lang']) : $default_lang;
    $language = function_exists('aqarand_locations_normalize_language')
        ? aqarand_locations_normalize_language($requested_lang, $default_lang)
        : $default_lang;

    $table_name = $wpdb->prefix . 'locations_governorates';
    $results = $wpdb->get_results(
        "SELECT id, name_ar, name_en FROM {$table_name} ORDER BY name_ar ASC, name_en ASC",
        ARRAY_A
    );

    $placeholder = function_exists('aqarand_locations_get_placeholder')
        ? aqarand_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'aqarand'), $language)
        : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'aqarand'));
    $options = ['' => $placeholder];

    if ($results) {
        foreach ($results as $row) {
            $label = function_exists('aqarand_locations_get_label')
                ? aqarand_locations_get_label(
                    $row['name_ar'] ?? '',
                    $row['name_en'] ?? '',
                    $language,
                    sprintf('#%d', (int) $row['id'])
                )
                : ($row['name_ar'] ?? $row['name_en'] ?? sprintf('#%d', (int) $row['id']));

            $options[$row['id']] = $label;
        }
    }
    wp_send_json_success(['options' => $options]);
});

/**
 * Saves the location data from the quick edit screen.
 */
function jawda_save_quick_edit_location_data($post_id) {
    if (!isset($_POST['location_quick_edit_nonce']) || !wp_verify_nonce($_POST['location_quick_edit_nonce'], 'jawda_location_quick_edit_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = ['loc_governorate_id', 'loc_city_id', 'loc_district_id'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, absint($_POST[$field]));
        }
    }
    jawda_update_project_coordinates_from_location($post_id);
}
add_action('save_post_projects', 'jawda_save_quick_edit_location_data');

/**
 * Helper function to update project coordinates based on its location.
 */
function jawda_update_project_coordinates_from_location($post_id) {
    global $wpdb;
    $district_id = get_post_meta($post_id, 'loc_district_id', true);
    $city_id = get_post_meta($post_id, 'loc_city_id', true);
    $gov_id = get_post_meta($post_id, 'loc_governorate_id', true);

    $coords = null;

    if ($district_id) {
        $coords = $wpdb->get_row($wpdb->prepare("SELECT latitude, longitude FROM {$wpdb->prefix}locations_districts WHERE id = %d", $district_id));
    } elseif ($city_id) {
        $coords = $wpdb->get_row($wpdb->prepare("SELECT latitude, longitude FROM {$wpdb->prefix}locations_cities WHERE id = %d", $city_id));
    } elseif ($gov_id) {
        $coords = $wpdb->get_row($wpdb->prepare("SELECT latitude, longitude FROM {$wpdb->prefix}locations_governorates WHERE id = %d", $gov_id));
    }

    if ($coords && !empty($coords->latitude) && !empty($coords->longitude)) {
        carbon_set_post_meta($post_id, 'jawda_map', [
            'lat'  => $coords->latitude,
            'lng'  => $coords->longitude,
            'zoom' => 15, // A reasonable default zoom level
        ]);
    }
}

/**
 * AJAX handler for bulk updating project locations.
 */
function jawda_bulk_edit_save_locations() {
    check_ajax_referer('jawda_location_bulk_edit_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied.');
    }

    $post_ids = !empty($_POST['post_ids']) ? array_map('absint', $_POST['post_ids']) : [];
    $gov_id = !empty($_POST['loc_governorate_id']) ? absint($_POST['loc_governorate_id']) : 0;
    $city_id = !empty($_POST['loc_city_id']) ? absint($_POST['loc_city_id']) : 0;
    $district_id = !empty($_POST['loc_district_id']) ? absint($_POST['loc_district_id']) : 0;

    foreach ($post_ids as $post_id) {
        if ($gov_id) update_post_meta($post_id, 'loc_governorate_id', $gov_id);
        if ($city_id) update_post_meta($post_id, 'loc_city_id', $city_id);
        if ($district_id) update_post_meta($post_id, 'loc_district_id', $district_id);
        jawda_update_project_coordinates_from_location($post_id);
    }
    wp_send_json_success('Projects updated.');
}
add_action('wp_ajax_jawda_bulk_edit_save_locations', 'jawda_bulk_edit_save_locations');
