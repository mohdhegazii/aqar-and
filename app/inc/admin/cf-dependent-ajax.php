<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_enqueue_scripts', function($hook){
    if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
        $is_ar = false;

        if (function_exists('aqarand_is_arabic_locale')) {
            $is_ar = aqarand_is_arabic_locale();
        } else {
            $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
            $is_ar = (bool) preg_match('/^ar/i', (string) $locale);
        }

        wp_enqueue_script('cf-dependent-selects',
            get_template_directory_uri() . '/admin/cf-dependent-selects.js',
            ['jquery'], '1.4', true
        );
        $select_gov_first_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'aqarand'));
        $select_city_first_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'aqarand'), 'both')
            : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'aqarand'));
        $error_loading_cities = $is_ar ? '— حدث خطأ، جرّب إعادة التحميل —' : __('— Error loading cities —', 'aqarand');
        $error_loading_districts = $is_ar ? '— حدث خطأ، جرّب إعادة التحميل —' : __('— Error loading districts —', 'aqarand');
        $no_cities_found = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('لم يتم العثور على مدن لهذه المحافظة.', __('No cities found for this governorate.', 'aqarand'), 'both')
            : ($is_ar ? 'لم يتم العثور على مدن لهذه المحافظة.' : __('No cities found for this governorate.', 'aqarand'));
        $no_districts_found = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('لم يتم العثور على مناطق لهذه المدينة.', __('No districts found for this city.', 'aqarand'), 'both')
            : ($is_ar ? 'لم يتم العثور على مناطق لهذه المدينة.' : __('No districts found for this city.', 'aqarand'));
        $no_options_placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— لا توجد بيانات متاحة —', __('— No options available —', 'aqarand'), 'both')
            : ($is_ar ? '— لا توجد بيانات متاحة —' : __('— No options available —', 'aqarand'));

        wp_localize_script('cf-dependent-selects', 'CF_DEP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cf_dep_nonce'),
            'language' => 'both',
            'i18n'     => [
                'loading'              => $is_ar ? '— جاري التحميل… —' : __('— Loading… —', 'aqarand'),
                'select_gov_first'     => $select_gov_first_placeholder,
                'select_city_first'    => $select_city_first_placeholder,
                'error_loading_cities' => $error_loading_cities,
                'error_loading_districts' => $error_loading_districts,
                'no_cities_found'      => $no_cities_found,
                'no_districts_found'   => $no_districts_found,
                'no_options'           => $no_options_placeholder,
            ]
        ]);
    }
});

// AJAX handler to get cities based on governorate ID
add_action('wp_ajax_cf_dep_get_cities', function(){
    global $wpdb;
    try {
        check_ajax_referer('cf_dep_nonce', 'nonce');
        $gov_id = isset($_GET['gov_id']) ? absint($_GET['gov_id']) : 0;
        if (!$gov_id) {
            throw new Exception('Governorate ID is missing.');
        }

        $default_lang = function_exists('aqarand_is_arabic_locale') && aqarand_is_arabic_locale() ? 'ar' : 'en';
        $requested_lang = isset($_GET['lang']) ? wp_unslash($_GET['lang']) : $default_lang;
        $language = function_exists('aqarand_locations_normalize_language')
            ? aqarand_locations_normalize_language($requested_lang, $default_lang)
            : $default_lang;

        $table_name = $wpdb->prefix . 'locations_cities';
        $query = $wpdb->prepare(
            "SELECT id, name_ar, name_en, latitude, longitude FROM $table_name WHERE governorate_id = %d ORDER BY name_ar ASC, name_en ASC",
            $gov_id
        );
        $results = $wpdb->get_results($query, ARRAY_A);

        if (CF_DEP_DEBUG) {
            error_log('[cf_dep_get_cities] Query: ' . $query);
            error_log('[cf_dep_get_cities] Found ' . count($results) . ' cities for governorate_id ' . $gov_id);
        }

        $placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'aqarand'), $language)
            : __('— Select City —', 'aqarand');

        $opts = ['' => [
            'label' => $placeholder,
            'lat'   => '',
            'lng'   => '',
        ]];
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

                $opts[$row['id']] = [
                    'label' => $label,
                    'lat'   => isset($row['latitude']) ? $row['latitude'] : '',
                    'lng'   => isset($row['longitude']) ? $row['longitude'] : '',
                ];
            }
        } else {
            // No cities found, send a specific message to be displayed
            wp_send_json_success(['options' => $opts, 'message' => CF_DEP_DEBUG ? 'No cities found in DB for this governorate.' : ''], 200);
            return;
        }

        wp_send_json_success(['options' => $opts], 200);
    } catch (Exception $e) {
        if (CF_DEP_DEBUG) {
            error_log('[cf_dep_get_cities] Exception: '.$e->getMessage());
        }
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }
});

// AJAX handler to get districts based on city ID
add_action('wp_ajax_cf_dep_get_districts', function(){
    global $wpdb;
    try {
        check_ajax_referer('cf_dep_nonce', 'nonce');
        $city_id = isset($_GET['city_id']) ? absint($_GET['city_id']) : 0;
        if (!$city_id) {
            throw new Exception('City ID is missing.');
        }

        $default_lang = function_exists('aqarand_is_arabic_locale') && aqarand_is_arabic_locale() ? 'ar' : 'en';
        $requested_lang = isset($_GET['lang']) ? wp_unslash($_GET['lang']) : $default_lang;
        $language = function_exists('aqarand_locations_normalize_language')
            ? aqarand_locations_normalize_language($requested_lang, $default_lang)
            : $default_lang;

        $table_name = $wpdb->prefix . 'locations_districts';
        $query = $wpdb->prepare(
            "SELECT id, name_ar, name_en, latitude, longitude FROM $table_name WHERE city_id = %d ORDER BY name_ar ASC, name_en ASC",
            $city_id
        );
        $results = $wpdb->get_results($query, ARRAY_A);

        if (CF_DEP_DEBUG) {
            error_log('[cf_dep_get_districts] Query: ' . $query);
            error_log('[cf_dep_get_districts] Found ' . count($results) . ' districts for city_id ' . $city_id);
        }

        $placeholder = function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المنطقة/الحي —', __('— Select District / Neighborhood —', 'aqarand'), $language)
            : __('— Select District / Neighborhood —', 'aqarand');

        $opts = ['' => [
            'label' => $placeholder,
            'lat'   => '',
            'lng'   => '',
        ]];
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

                $opts[$row['id']] = [
                    'label' => $label,
                    'lat'   => isset($row['latitude']) ? $row['latitude'] : '',
                    'lng'   => isset($row['longitude']) ? $row['longitude'] : '',
                ];
            }
        } else {
            wp_send_json_success(['options' => $opts, 'message' => CF_DEP_DEBUG ? 'No districts found in DB for this city.' : ''], 200);
            return;
        }

        wp_send_json_success(['options' => $opts], 200);
    } catch (Exception $e) {
        if (CF_DEP_DEBUG) {
            error_log('[cf_dep_get_districts] Exception: '.$e->getMessage());
        }
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }
});
