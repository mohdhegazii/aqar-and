<?php

// Security Check
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

// Carbon_Fields
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/* -------------------------------------------------------------------------
# Property Meta Boxes
------------------------------------------------------------------------- */

if ( !function_exists('jawda_meta_property') ) {

  add_action( 'carbon_fields_register_fields', 'jawda_meta_property' );
  function jawda_meta_property() {

    // Options
    $meta_package =
    Container::make( 'post_meta', 'Property Data' )
      ->where( 'post_type', '=', 'property' )
      ->add_tab( __( 'Property Details' ), array(

        // Gallery
        Field::make( 'separator', 'jawda_separator_002', __( 'Main Project' ) ),
        Field::make( 'multiselect', 'jawda_project', __( 'Main Project' ) )->add_options( 'get_my_projects_list' ),

        Field::make( 'separator', 'jawda_separator_cat', __( 'Category Details' ) ),
        Field::make('select', 'jawda_main_category_id', __('Main Category', 'aqarand'))
            ->set_options('jawda_get_main_categories_options')
            ->set_width(50),
        Field::make('select', 'jawda_property_type_id', __('Property Type', 'aqarand'))
            ->set_options('jawda_get_property_types_for_meta_box')
            ->set_conditional_logic(array(
                array(
                    'field' => 'jawda_main_category_id',
                    'value' => '',
                    'compare' => '!=',
                )
            ))
            ->set_width(50),
        Field::make('multiselect', 'jawda_project_feature_ids', __('Featured (Amenities / Facilities)', 'aqarand'))
            ->set_options('jawda_get_project_feature_options_for_properties')
            ->set_help_text(__('Choose the amenities, facilities, or highlights available for this unit.', 'aqarand')),

        // Property details
        Field::make( 'separator', 'jawda_separator_003', __( 'Property details' ) ),
        Field::make( 'text', 'jawda_bedrooms', __( 'bedrooms' ) ),
        Field::make( 'text', 'jawda_bathrooms', __( 'bathrooms' ) ),
        Field::make( 'text', 'jawda_garage', __( 'garage' ) ),
        Field::make( 'text', 'jawda_price', __( 'price' ) ),
        Field::make( 'text', 'jawda_size', __( 'size' ) ),
        Field::make( 'text', 'jawda_year', __( 'Receipt date' ) ),
        Field::make( 'text', 'jawda_location', __( 'location' ) ),
        Field::make( 'text', 'jawda_payment_systems', __( 'Payment Systems' ) ),
        Field::make( 'text', 'jawda_finishing', __( 'finishing' ) ),

        Field::make( 'separator', 'jawda_separator_004', __( 'Property Plan' ) ),
        Field::make( 'image', 'jawda_priperty_plan', __( 'Plan' ) ),

    ) )

    ->add_tab( __( 'Gallery' ), array(

      // Gallery
      Field::make( 'separator', 'jawda_separator_001', __( 'Property photos' ) ),
      Field::make( 'media_gallery', 'jawda_attachments', __( 'Property Gallery' ) ),


    ) )


    ->add_tab( __( 'Video' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0c1', __( 'Property Video' ) ),
      Field::make( 'text', 'jawda_video_url', __( 'youtube video url' ) ),


    ) )

    ->add_tab( __( 'Map' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0b1', __( 'Property On Map' ) ),
      Field::make( 'map', 'jawda_map', __( 'Map' ) )->set_position( '30.076224563542933','31.51153564453125','10' ),


    ) )


    ->add_tab( __( 'FAQ' ), array(

      Field::make( 'separator', 'jawda_separator_0d1', __( 'Frequently Asked Questions' ) ),

      Field::make( 'complex', 'jawda_faq', __( 'Questions' ) )
          ->add_fields( array(
              Field::make( 'text', 'jawda_faq_q', __( 'Question' ) ),
              Field::make( 'textarea', 'jawda_faq_a', __( 'Answer' ) ),
          )
        )


    ) );


  }

}


/**
 * Legacy Carbon Fields location fields have been replaced with a custom meta box
 * to ensure dependable storage of governorate, city, and district selections.
 */
if (!function_exists('aqarand_is_arabic_locale')) {
    function aqarand_is_arabic_locale() {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();

        return (bool) preg_match('/^ar/i', (string) $locale);
    }
}

add_action('add_meta_boxes', 'aqarand_register_project_location_meta_box');
function aqarand_register_project_location_meta_box() {
    $title = aqarand_is_arabic_locale() ? 'موقع المشروع' : __('Project Location', 'aqarand');

    add_meta_box(
        'aqarand-project-location',
        $title,
        'aqarand_render_project_location_meta_box',
        ['projects', 'catalogs'],
        'normal',
        'high'
    );
}

/**
 * Render the Project Location meta box with governorate, city, and district selects.
 *
 * @param WP_Post $post Current post instance.
 */
function aqarand_render_project_location_meta_box($post) {
    if (!current_user_can('edit_post', $post->ID)) {
        return;
    }

    wp_nonce_field('aqarand_save_project_location', 'aqarand_project_location_nonce');

    $gov_id      = absint(get_post_meta($post->ID, 'loc_governorate_id', true));
    $city_id     = absint(get_post_meta($post->ID, 'loc_city_id', true));
    $district_id = absint(get_post_meta($post->ID, 'loc_district_id', true));

    $is_ar = aqarand_is_arabic_locale();
    $options_language = function_exists('aqarand_locations_normalize_language')
        ? aqarand_locations_normalize_language('both', $is_ar ? 'ar' : 'en')
        : 'both';

    $labels = [
        'gov'      => $is_ar ? 'المحافظة' : __('Governorate', 'aqarand'),
        'city'     => $is_ar ? 'المدينة' : __('City', 'aqarand'),
        'district' => $is_ar ? 'المنطقة/الحي' : __('District / Neighborhood', 'aqarand'),
    ];

    $placeholders = [
        'select_gov'  => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'aqarand'), $options_language)
            : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'aqarand')),
        'select_city' => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'aqarand'), $options_language)
            : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'aqarand')),
        'select_city_first' => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'aqarand'), $options_language)
            : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'aqarand')),
        'select_gov_first'  => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'aqarand'), $options_language)
            : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'aqarand')),
        'select_district'   => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('— اختر المنطقة/الحي —', __('— Select District / Neighborhood —', 'aqarand'), $options_language)
            : ($is_ar ? '— اختر المنطقة/الحي —' : __('— Select District / Neighborhood —', 'aqarand')),
    ];

    global $wpdb;

    $governorates = $wpdb->get_results(
        "SELECT id, name_ar, name_en, latitude, longitude FROM {$wpdb->prefix}locations_governorates ORDER BY name_ar ASC, name_en ASC"
    );

    $cities = [];
    if ($gov_id) {
        $cities = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name_ar, name_en, latitude, longitude FROM {$wpdb->prefix}locations_cities WHERE governorate_id = %d ORDER BY name_ar ASC, name_en ASC",
                $gov_id
            )
        );
    }

    $districts = [];
    if ($city_id) {
        $districts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name_ar, name_en, latitude, longitude FROM {$wpdb->prefix}locations_districts WHERE city_id = %d ORDER BY name_ar ASC, name_en ASC",
                $city_id
            )
        );
    }

    $map_meta = function_exists('carbon_get_post_meta')
        ? carbon_get_post_meta($post->ID, 'jawda_map')
        : [];

    $map_lat = '';
    $map_lng = '';
    $map_zoom = 13;
    $map_address = '';

    if (is_array($map_meta)) {
        if (isset($map_meta['lat'])) {
            $map_lat = (string) $map_meta['lat'];
        }
        if (isset($map_meta['lng'])) {
            $map_lng = (string) $map_meta['lng'];
        }
        if (isset($map_meta['zoom']) && is_numeric($map_meta['zoom'])) {
            $map_zoom = (int) $map_meta['zoom'];
        }
        if (!empty($map_meta['address'])) {
            $map_address = (string) $map_meta['address'];
        }
    }

    $fallback_lat = '';
    $fallback_lng = '';

    $candidates = [
        ['items' => $districts, 'id' => $district_id],
        ['items' => $cities, 'id' => $city_id],
        ['items' => $governorates, 'id' => $gov_id],
    ];

    foreach ($candidates as $candidate) {
        if (!$candidate['id'] || empty($candidate['items'])) {
            continue;
        }

        foreach ($candidate['items'] as $item) {
            if ((int) $item->id !== (int) $candidate['id']) {
                continue;
            }

            if (($item->latitude !== null && $item->latitude !== '') && ($item->longitude !== null && $item->longitude !== '')) {
                $fallback_lat = (string) $item->latitude;
                $fallback_lng = (string) $item->longitude;
            }

            break 2;
        }
    }

    if ('' === $map_lat && '' !== $fallback_lat) {
        $map_lat = $fallback_lat;
    }

    if ('' === $map_lng && '' !== $fallback_lng) {
        $map_lng = $fallback_lng;
    }

    ?>
    <div class="aqarand-project-location-box">
        <div class="aqarand-project-location-grid">
            <div class="aqarand-project-location-grid__fields">
                <div class="aqarand-project-location-field cf-dep-governorate">
                    <label for="loc_governorate_id"><strong><?php echo esc_html($labels['gov']); ?></strong></label>
                    <select id="loc_governorate_id" name="loc_governorate_id" data-selected="<?php echo esc_attr($gov_id); ?>">
                        <option value=""><?php echo esc_html($placeholders['select_gov']); ?></option>
                        <?php
                        if ($governorates) {
                            foreach ($governorates as $gov) {
                                $label = function_exists('aqarand_locations_get_label')
                                    ? aqarand_locations_get_label(
                                        $gov->name_ar ?? '',
                                        $gov->name_en ?? '',
                                        $options_language,
                                        sprintf('#%d', (int) $gov->id)
                                    )
                                    : ($gov->name_ar ?? $gov->name_en ?? sprintf('#%d', (int) $gov->id));
                                $lat_attr = ($gov->latitude !== null && $gov->latitude !== '') ? ' data-lat="' . esc_attr($gov->latitude) . '"' : '';
                                $lng_attr = ($gov->longitude !== null && $gov->longitude !== '') ? ' data-lng="' . esc_attr($gov->longitude) . '"' : '';

                                printf(
                                    '<option value="%1$s"%4$s%5$s %2$s>%3$s</option>',
                                    esc_attr($gov->id),
                                    selected((string) $gov_id, (string) $gov->id, false),
                                    esc_html($label),
                                    $lat_attr,
                                    $lng_attr
                                );
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="aqarand-project-location-field cf-dep-city">
                    <label for="loc_city_id"><strong><?php echo esc_html($labels['city']); ?></strong></label>
                    <select id="loc_city_id" name="loc_city_id" data-selected="<?php echo esc_attr($city_id); ?>">
                        <option value=""><?php echo esc_html($gov_id ? $placeholders['select_city'] : $placeholders['select_gov_first']); ?></option>
                        <?php
                        if ($cities) {
                            foreach ($cities as $city) {
                                $label = function_exists('aqarand_locations_get_label')
                                    ? aqarand_locations_get_label(
                                        $city->name_ar ?? '',
                                        $city->name_en ?? '',
                                        $options_language,
                                        sprintf('#%d', (int) $city->id)
                                    )
                                    : ($city->name_ar ?? $city->name_en ?? sprintf('#%d', (int) $city->id));
                                $lat_attr = ($city->latitude !== null && $city->latitude !== '') ? ' data-lat="' . esc_attr($city->latitude) . '"' : '';
                                $lng_attr = ($city->longitude !== null && $city->longitude !== '') ? ' data-lng="' . esc_attr($city->longitude) . '"' : '';

                                printf(
                                    '<option value="%1$s"%4$s%5$s %2$s>%3$s</option>',
                                    esc_attr($city->id),
                                    selected((string) $city_id, (string) $city->id, false),
                                    esc_html($label),
                                    $lat_attr,
                                    $lng_attr
                                );
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="aqarand-project-location-field cf-dep-district">
                    <label for="loc_district_id"><strong><?php echo esc_html($labels['district']); ?></strong></label>
                    <select id="loc_district_id" name="loc_district_id" data-selected="<?php echo esc_attr($district_id); ?>">
                        <option value=""><?php echo esc_html($city_id ? $placeholders['select_district'] : $placeholders['select_city_first']); ?></option>
                        <?php
                        if ($districts) {
                            foreach ($districts as $district) {
                                $label = function_exists('aqarand_locations_get_label')
                                    ? aqarand_locations_get_label(
                                        $district->name_ar ?? '',
                                        $district->name_en ?? '',
                                        $options_language,
                                        sprintf('#%d', (int) $district->id)
                                    )
                                    : ($district->name_ar ?? $district->name_en ?? sprintf('#%d', (int) $district->id));
                                $lat_attr = ($district->latitude !== null && $district->latitude !== '') ? ' data-lat="' . esc_attr($district->latitude) . '"' : '';
                                $lng_attr = ($district->longitude !== null && $district->longitude !== '') ? ' data-lng="' . esc_attr($district->longitude) . '"' : '';

                                printf(
                                    '<option value="%1$s"%4$s%5$s %2$s>%3$s</option>',
                                    esc_attr($district->id),
                                    selected((string) $district_id, (string) $district->id, false),
                                    esc_html($label),
                                    $lat_attr,
                                    $lng_attr
                                );
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="cf-dep-errors-box">
                    <div id="cf-dep-errors" class="aqarand-project-location-errors"></div>
                </div>
            </div>

            <div class="aqarand-project-location-grid__map">
                <?php
                if (function_exists('aqarand_locations_render_coordinate_fields')) {
                    aqarand_locations_render_coordinate_fields([
                        'lat_id'    => 'aqarand_project_latitude',
                        'lat_name'  => 'aqarand_project_latitude',
                        'lat_value' => $map_lat,
                        'lng_id'    => 'aqarand_project_longitude',
                        'lng_name'  => 'aqarand_project_longitude',
                        'lng_value' => $map_lng,
                        'map_id'    => 'aqarand-project-location-map',
                        'label'     => $is_ar ? 'موقع الخريطة' : __('Map Preview', 'aqarand'),
                    ]);
                } else {
                    ?>
                    <p><?php esc_html_e('Map component is unavailable.', 'aqarand'); ?></p>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Persist project location selections when the post is saved.
 *
 * @param int $post_id Post identifier.
 */
function aqarand_save_project_location_meta_box($post_id) {
    if (!isset($_POST['aqarand_project_location_nonce']) ||
        !wp_verify_nonce(wp_unslash($_POST['aqarand_project_location_nonce']), 'aqarand_save_project_location')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'loc_governorate_id' => 'absint',
        'loc_city_id'        => 'absint',
        'loc_district_id'    => 'absint',
    ];

    foreach ($fields as $key => $sanitizer) {
        $value = isset($_POST[$key]) ? call_user_func($sanitizer, wp_unslash($_POST[$key])) : 0;

        if ($value) {
            update_post_meta($post_id, $key, $value);
        } else {
            delete_post_meta($post_id, $key);
        }
    }

    $lat_raw = isset($_POST['aqarand_project_latitude']) ? wp_unslash($_POST['aqarand_project_latitude']) : '';
    $lng_raw = isset($_POST['aqarand_project_longitude']) ? wp_unslash($_POST['aqarand_project_longitude']) : '';

    if (function_exists('aqarand_locations_normalize_coordinate')) {
        $lat = aqarand_locations_normalize_coordinate($lat_raw);
        $lng = aqarand_locations_normalize_coordinate($lng_raw);
    } else {
        $normalize = static function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $value = str_replace(',', '.', $value);

            return is_numeric($value) ? $value : null;
        };

        $lat = $normalize($lat_raw);
        $lng = $normalize($lng_raw);
    }

    $existing_map = function_exists('carbon_get_post_meta')
        ? carbon_get_post_meta($post_id, 'jawda_map')
        : [];

    $map_zoom = 13;
    $map_address = '';

    if (is_array($existing_map)) {
        if (isset($existing_map['zoom']) && is_numeric($existing_map['zoom'])) {
            $map_zoom = (int) $existing_map['zoom'];
        }

        if (!empty($existing_map['address'])) {
            $map_address = (string) $existing_map['address'];
        }
    }

    $map_payload = [
        'lat'     => $lat !== null ? (string) $lat : '',
        'lng'     => $lng !== null ? (string) $lng : '',
        'zoom'    => $map_zoom,
        'address' => $map_address,
    ];

    if (function_exists('carbon_set_post_meta')) {
        carbon_set_post_meta($post_id, 'jawda_map', $map_payload);
    } else {
        update_post_meta($post_id, 'jawda_map', $map_payload);
    }
}
add_action('save_post_projects', 'aqarand_save_project_location_meta_box', 20);
add_action('save_post_catalogs', 'aqarand_save_project_location_meta_box', 20);








/* -----------------------------------------------------------------------------
# Term Meta
----------------------------------------------------------------------------- */


if ( !function_exists('jawda_meta_project') ) {

  add_action( 'carbon_fields_register_fields', 'jawda_meta_project' );
  function jawda_meta_project() {

    // Options
    $meta_package =
    Container::make( 'post_meta', 'Project Details' )
      ->where( 'post_type', '=', 'projects' )
      ->add_tab( __( 'Project Details' ), array(

        Field::make( 'separator', 'jawda_separator_cat', __( 'Category Details' ) ),
        Field::make('select', 'jawda_main_category_id', __('Main Category', 'aqarand'))
            ->set_options('jawda_get_main_categories_options')
            ->set_width(50),
        Field::make('multiselect', 'jawda_property_type_ids', __('Property Types', 'aqarand'))
            ->set_options('jawda_get_property_types_for_meta_box')
            ->set_help_text(__('Select the property types associated with the main category.', 'aqarand'))
            ->set_conditional_logic(array(
                array(
                    'field' => 'jawda_main_category_id',
                    'value' => '',
                    'compare' => '!=',
                )
            ))
            ->set_width(50),
        Field::make('separator', 'jawda_project_services_separator', __('Services', 'aqarand')),
        Field::make('multiselect', 'jawda_project_service_feature_ids', __('Project Features', 'aqarand'))
            ->set_options('jawda_get_project_feature_options_for_project_features')
            ->set_help_text(__('Highlight the project features that make this development stand out.', 'aqarand')),
        Field::make('multiselect', 'jawda_project_service_amenity_ids', __('Amenities', 'aqarand'))
            ->set_options('jawda_get_project_feature_options_for_project_amenities')
            ->set_help_text(__('Select the on-site amenities that residents will enjoy.', 'aqarand')),
        Field::make('multiselect', 'jawda_project_service_facility_ids', __('Facilities', 'aqarand'))
            ->set_options('jawda_get_project_feature_options_for_project_facilities')
            ->set_help_text(__('List the essential facilities that support the project.', 'aqarand')),

        // Property details
        Field::make( 'separator', 'jawda_separator_003', __( 'Project details' ) ),
        Field::make( 'text', 'jawda_price', __( 'price' ) ),
        Field::make( 'text', 'jawda_installment', __( 'installment' ) ),
        Field::make( 'text', 'jawda_down_payment', __( 'down payment' ) ),
        Field::make( 'text', 'jawda_size', __( 'size' ) ),
        Field::make( 'text', 'jawda_year', __( 'Receipt date' ) ),
        Field::make( 'text', 'jawda_location', __( 'location' ) ),
        Field::make( 'text', 'jawda_unit_types', __( 'Unit types' ) ),

        Field::make( 'text', 'jawda_payment_systems', __( 'Payment Systems' ) ),
        Field::make( 'text', 'jawda_finishing', __( 'finishing' ) ),

        Field::make( 'separator', 'jawda_separator_004', __( 'Property Plan' ) ),
        Field::make( 'image', 'jawda_priperty_plan', __( 'Plan' ) ),
      ) )

      ->add_tab( __( 'Payment Plans', 'aqarand' ), function_exists('aqarand_get_payment_plan_fields') ? aqarand_get_payment_plan_fields() : array() )

      ->add_tab( __( 'Gallery' ), array(

      // Gallery
      Field::make( 'separator', 'jawda_separator_001', __( 'Property photos' ) ),
      Field::make( 'media_gallery', 'jawda_attachments', __( 'Property Gallery' ) ),


      ) )


      ->add_tab( __( 'Video' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0c1', __( 'Property Video' ) ),
      Field::make( 'text', 'jawda_video_url', __( 'youtube video url' ) ),


      ) )

      ->add_tab( __( 'Map' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0b1', __( 'Property On Map' ) ),
      Field::make( 'html', 'aqarand_project_location_hook', '' )
        ->set_html( '<div id="aqarand-project-location-placeholder" class="aqarand-project-location-placeholder"></div>' ),


      ) )


      ->add_tab( __( 'FAQ' ), array(

      Field::make( 'separator', 'jawda_separator_0d1', __( 'Frequently Asked Questions' ) ),

      Field::make( 'complex', 'jawda_faq', __( 'Questions' ) )
          ->add_fields( array(
              Field::make( 'text', 'jawda_faq_q', __( 'Question' ) ),
              Field::make( 'textarea', 'jawda_faq_a', __( 'Answer' ) ),
          )
        )


      ) );

  }

}









add_action( 'carbon_fields_register_fields', 'jawda_terms_meta' );
function jawda_terms_meta() {

  // Options
  $basic_options_container =
  Container::make( 'term_meta', __( 'Photo' ) )
    ->where( 'term_taxonomy', 'IN', ['projects_type','projects_category','projects_tag','projects_developer','projects_area','property_label','property_type','property_feature','property_city','property_area','property_state','property_country','property_status'] )
    ->add_fields( array(
        Field::make( 'image', 'jawda_thumb', __( 'Cover photo' ) ),
    )
  );



}



add_action( 'carbon_fields_register_fields', 'jawda_city_terms_meta' );
function jawda_city_terms_meta() {

  // Options
  $basic_options_container =
  Container::make( 'term_meta', __( 'State' ) )
    ->where( 'term_taxonomy', 'IN', ['property_city'] )
    ->add_fields( array(
      Field::make( 'select', 'jawda_city_state', __( 'Choose State' ) )->set_options( 'get_my_states_list' ),
    )
  );



}




/* ----------------------------------------------------------------------------
# initiative
---------------------------------------------------------------------------- */

add_action( 'carbon_fields_register_fields', 'jawda_meta_page_catalog' );
function jawda_meta_page_catalog() {

  // Options
  $meta_package =
  Container::make( 'post_meta', 'Initiative Details' )
    ->where( 'post_type', '=', 'catalogs' )
    ->add_fields( array(

      // Cataloug Type
      Field::make( 'separator', 'jawda_separator_1', __( 'Cataloug Type' ) ),
      Field::make( 'select', 'jawda_catalog_type', __( 'Cataloug Type' ) )->set_options( array('1' => 'مشروعات','2' => 'وحدات') ),

      // IF Project
      Field::make( 'separator', 'jawda_separator_2', __( 'If Project' ) ),
      // Field::make( 'select', 'jawda_project_city', __( 'Project city' ) )->set_options( 'get_my_projects_cities_list' ),
      Field::make( 'select', 'jawda_project_type', __( 'Project Type' ) )->set_options( 'get_my_projects_types_list' ),
      Field::make( 'text', 'jawda_project_price_from', __( 'Price From' ) ),
      Field::make( 'text', 'jawda_project_price_to', __( 'Price to' ) ),


      // IF Property
      Field::make( 'separator', 'jawda_separator_3', __( 'If Property' ) ),
      //Field::make( 'select', 'jawda_property_state', __( 'Property state' ) )->set_options( 'get_my_properties_state_list' ),
      // Field::make( 'select', 'jawda_property_city', __( 'Property city' ) )->set_options( 'get_my_properties_cities_list' ),
      Field::make( 'select', 'jawda_property_type', __( 'Property Type' ) )->set_options( 'get_my_properties_types_list' ),
      //Field::make( 'text', 'jawda_property_price_from', __( 'Price From' ) ),
      //Field::make( 'text', 'jawda_property_price_to', __( 'Price to' ) ),
      Field::make( 'multiselect', 'jawda_property_main_project', __( 'Main Project' ) )->add_options( 'get_my_projects_list' ),

    ));

}



/* ------  ----------- */

function get_my_projects_types_list(){
  $return = [];
  $terms = get_terms( 'projects_type', array('hide_empty' => false,) );
  $return[] = '';
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}

function get_my_properties_state_list(){
  $return = [];
  $terms = get_terms( 'property_state', array('hide_empty' => false,) );
  $return[] = '';
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}

function get_my_properties_types_list(){
  $return = [];
  $terms = get_terms( 'property_type', array('hide_empty' => false,) );
  $return[] = '';
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}
// carbon_get_post_meta( get_the_ID(), 'jawda_location' );

add_action('admin_head', 'aqarand_hide_location_map_for_catalogs');
function aqarand_hide_location_map_for_catalogs() {
    $screen = get_current_screen();
    if ( $screen && $screen->post_type === 'catalogs' ) {
        echo '<style>
            #aqarand-project-location .aqarand-project-location-grid__map {
                display: none;
            }
            #aqarand-project-location .aqarand-project-location-grid {
                grid-template-columns: 1fr;
            }
        </style>';
    }
}

function jawda_get_main_categories_options() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'property_categories';
    $is_arabic  = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : is_rtl();

    $language = $is_arabic ? 'ar' : 'en';
    if (function_exists('aqarand_locations_normalize_language')) {
        $language = aqarand_locations_normalize_language('both', $language);
    }

    $results = $wpdb->get_results(
        "SELECT id, name_ar, name_en FROM {$table_name} ORDER BY name_ar ASC, name_en ASC",
        ARRAY_A
    );

    $placeholder_ar = 'اختر الكاتيجوري الرئيسي';
    $placeholder_en = __('Select a Category', 'aqarand');
    $placeholder    = function_exists('aqarand_locations_get_placeholder')
        ? aqarand_locations_get_placeholder($placeholder_ar, $placeholder_en, $language)
        : (
            $language === 'ar'
                ? $placeholder_ar
                : ($language === 'en'
                    ? $placeholder_en
                    : trim($placeholder_ar . ' / ' . $placeholder_en))
        );

    $options = ['' => $placeholder];

    if ($results) {
        foreach ($results as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $label = function_exists('aqarand_locations_get_label')
                ? aqarand_locations_get_label(
                    $row['name_ar'] ?? '',
                    $row['name_en'] ?? '',
                    $language,
                    sprintf('#%d', (int) $row['id'])
                )
                : (
                    $language === 'en'
                        ? ($row['name_en'] ?? '')
                        : ($language === 'ar'
                            ? ($row['name_ar'] ?? '')
                            : trim(
                                ($row['name_ar'] ?? '') . ' / ' . ($row['name_en'] ?? '')
                            )
                        )
                );

            if ($label === '') {
                $label = (string) $row['id'];
            }

            $options[$row['id']] = $label;
        }
    }

    return $options;
}


function jawda_collect_property_types_map() {
    static $cache = [];

    global $wpdb;

    $types_table       = $wpdb->prefix . 'property_types';
    $pivot_table       = $wpdb->prefix . 'property_type_category_relationships';
    $categories_table  = $wpdb->prefix . 'property_categories';

    $is_arabic = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : is_rtl();
    $language = $is_arabic ? 'ar' : 'en';
    if (function_exists('aqarand_locations_normalize_language')) {
        $language = aqarand_locations_normalize_language('both', $language);
    }

    if (isset($cache[$language])) {
        return $cache[$language];
    }

    $map = [];

    $category_rows = $wpdb->get_results(
        "SELECT id, name_ar, name_en FROM {$categories_table} ORDER BY name_ar ASC, name_en ASC",
        ARRAY_A
    );

    if ($category_rows) {
        foreach ($category_rows as $category_row) {
            $category_id = isset($category_row['id']) ? (int) $category_row['id'] : 0;

            if (!$category_id) {
                continue;
            }

            $key = (string) $category_id;

            $label = function_exists('aqarand_locations_get_label')
                ? aqarand_locations_get_label(
                    $category_row['name_ar'] ?? '',
                    $category_row['name_en'] ?? '',
                    $language,
                    sprintf('#%d', $category_id)
                )
                : (
                    $language === 'en'
                        ? ($category_row['name_en'] ?? '')
                        : ($language === 'ar'
                            ? ($category_row['name_ar'] ?? '')
                            : trim(
                                ($category_row['name_ar'] ?? '') . ' / ' . ($category_row['name_en'] ?? '')
                            )
                        )
                );

            if (!isset($map[$key])) {
                $map[$key] = [
                    'id'      => $key,
                    'label'   => $label !== '' ? $label : (string) $category_id,
                    'name_ar' => isset($category_row['name_ar']) ? (string) $category_row['name_ar'] : '',
                    'name_en' => isset($category_row['name_en']) ? (string) $category_row['name_en'] : '',
                    'types'   => [],
                ];
            }
        }
    }

    $rows = [];

    $pivot_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $pivot_table));

    if ($pivot_exists) {
        $query = implode("\n", [
            "SELECT rel.category_id, rel.property_type_id AS id, types.name_ar, types.name_en",
            "FROM {$pivot_table} AS rel",
            "INNER JOIN {$types_table} AS types ON types.id = rel.property_type_id",
            "ORDER BY rel.category_id ASC, types.name_ar ASC, types.name_en ASC",
        ]);

        $rows = $wpdb->get_results($query, ARRAY_A);
    } else {
        $legacy_column = null;

        foreach (['main_category_id', 'category_id'] as $candidate) {
            $column_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW COLUMNS FROM {$types_table} LIKE %s",
                    $candidate
                )
            );

            if ($column_exists) {
                $legacy_column = $candidate;
                break;
            }
        }

        if ($legacy_column) {
            $query = implode("\n", [
                "SELECT {$legacy_column} AS category_id, id, name_ar, name_en",
                "FROM {$types_table}",
                "WHERE {$legacy_column} IS NOT NULL AND {$legacy_column} <> 0",
                "ORDER BY {$legacy_column} ASC, name_ar ASC, name_en ASC",
            ]);

            $rows = $wpdb->get_results($query, ARRAY_A);
        }
    }

    if ($rows) {
        foreach ($rows as $row) {
            $category_id = isset($row['category_id']) ? (int) $row['category_id'] : 0;
            $type_id     = isset($row['id']) ? (int) $row['id'] : 0;

            if (!$category_id || !$type_id) {
                continue;
            }

            $key = (string) $category_id;

            if (!isset($map[$key])) {
                $fallback_label = function_exists('aqarand_locations_get_label')
                    ? aqarand_locations_get_label('', '', $language, sprintf('#%d', $category_id))
                    : sprintf('#%d', $category_id);

                $map[$key] = [
                    'id'      => $key,
                    'label'   => $fallback_label,
                    'name_ar' => '',
                    'name_en' => '',
                    'types'   => [],
                ];
            }

            $type_label = function_exists('aqarand_locations_get_label')
                ? aqarand_locations_get_label(
                    $row['name_ar'] ?? '',
                    $row['name_en'] ?? '',
                    $language,
                    sprintf('#%d', $type_id)
                )
                : (
                    $language === 'en'
                        ? ($row['name_en'] ?? '')
                        : ($language === 'ar'
                            ? ($row['name_ar'] ?? '')
                            : trim(
                                ($row['name_ar'] ?? '') . ' / ' . ($row['name_en'] ?? '')
                            )
                        )
                );

            $map[$key]['types'][] = [
                'id'      => (string) $type_id,
                'name'    => $type_label !== '' ? $type_label : (string) $type_id,
                'name_ar' => isset($row['name_ar']) ? (string) $row['name_ar'] : '',
                'name_en' => isset($row['name_en']) ? (string) $row['name_en'] : '',
            ];
        }
    }

    $cache[$language] = $map;

    return $map;
}

function jawda_prepare_property_type_tree_for_js() {
    $map  = jawda_collect_property_types_map();
    $tree = [];

    foreach ($map as $category) {
        $types = [];

        if (!empty($category['types']) && is_array($category['types'])) {
            foreach ($category['types'] as $type) {
                $types[] = [
                    'id'      => isset($type['id']) ? (string) $type['id'] : '',
                    'name'    => isset($type['name']) ? (string) $type['name'] : '',
                    'name_ar' => isset($type['name_ar']) ? (string) $type['name_ar'] : '',
                    'name_en' => isset($type['name_en']) ? (string) $type['name_en'] : '',
                ];
            }
        }

        $tree[] = [
            'id'    => isset($category['id']) ? (string) $category['id'] : '',
            'name'  => isset($category['label']) ? (string) $category['label'] : '',
            'name_ar' => isset($category['name_ar']) ? (string) $category['name_ar'] : '',
            'name_en' => isset($category['name_en']) ? (string) $category['name_en'] : '',
            'types' => $types,
        ];
    }

    return $tree;
}

function jawda_fetch_property_types_by_category($category_id) {
    $category_id = absint($category_id);

    if (!$category_id) {
        return [];
    }

    $map = jawda_collect_property_types_map();
    $key = (string) $category_id;

    if (!isset($map[$key])) {
        return [];
    }

    $types = isset($map[$key]['types']) ? $map[$key]['types'] : [];

    return is_array($types) ? $types : [];
}

add_action('admin_enqueue_scripts', 'jawda_enqueue_category_dependency_scripts');
function jawda_enqueue_category_dependency_scripts($hook) {
    global $post;

    if ($hook !== 'post-new.php' && $hook !== 'post.php') {
        return;
    }

    $post_type = '';

    if ($hook === 'post-new.php') {
        if (isset($_GET['post_type'])) {
            $post_type = sanitize_key($_GET['post_type']);
        } elseif (isset($post->post_type)) {
            $post_type = $post->post_type;
        }
    } else {
        $post_type = isset($post->post_type) ? $post->post_type : '';
    }

    if (!in_array($post_type, ['projects', 'property'], true)) {
        return;
    }

    wp_enqueue_script(
        'aqarand-project-meta',
        get_template_directory_uri() . '/assets/js/aqarand-project-meta.js',
        ['jquery', 'carbon-fields-boot'],
        '3.0.0',
        true
    );

    $post_id = ($hook === 'post-new.php') ? 0 : (isset($post->ID) ? (int) $post->ID : 0);
    $selected_property_types = [];
    $selected_property_type  = '';

    if ($post_id && function_exists('carbon_get_post_meta')) {
        if ($post_type === 'projects') {
            $selected_property_types = (array) carbon_get_post_meta($post_id, 'jawda_property_type_ids');
        } elseif ($post_type === 'property') {
            $selected_property_type = (string) carbon_get_post_meta($post_id, 'jawda_property_type_id');
        }
    }

    $selected_property_types = array_map('strval', $selected_property_types);
    $selected_property_type  = $selected_property_type !== '' ? (string) $selected_property_type : '';

    $is_arabic = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : is_rtl();
    $language  = $is_arabic ? 'ar' : 'en';

    if (function_exists('aqarand_locations_normalize_language')) {
        $language = aqarand_locations_normalize_language('both', $language);
    }

    $strings  = [
        'no_categories'    => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('لا توجد تصنيفات متاحة.', __('No categories available.', 'aqarand'), $language)
            : ($language === 'ar'
                ? 'لا توجد تصنيفات متاحة.'
                : ($language === 'en'
                    ? __('No categories available.', 'aqarand')
                    : 'لا توجد تصنيفات متاحة. / ' . __('No categories available.', 'aqarand'))),
        'no_types'         => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('لا توجد أنواع متاحة لهذا التصنيف.', __('No property types available for this category.', 'aqarand'), $language)
            : ($language === 'ar'
                ? 'لا توجد أنواع متاحة لهذا التصنيف.'
                : ($language === 'en'
                    ? __('No property types available for this category.', 'aqarand')
                    : 'لا توجد أنواع متاحة لهذا التصنيف. / ' . __('No property types available for this category.', 'aqarand'))),
        'clear_selection'  => function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('مسح الاختيار', __('Clear selection', 'aqarand'), $language)
            : ($language === 'ar'
                ? 'مسح الاختيار'
                : ($language === 'en'
                    ? __('Clear selection', 'aqarand')
                    : 'مسح الاختيار / ' . __('Clear selection', 'aqarand'))),
        'fallback_category'=> function_exists('aqarand_locations_get_placeholder')
            ? aqarand_locations_get_placeholder('تصنيف #%s', __('Category #%s', 'aqarand'), $language)
            : ($language === 'ar'
                ? 'تصنيف #%s'
                : ($language === 'en'
                    ? __('Category #%s', 'aqarand')
                    : 'تصنيف #%s / ' . __('Category #%s', 'aqarand'))),
    ];

    wp_localize_script(
        'aqarand-project-meta',
        'AqarProjectMeta',
        [
            'property_type_tree'       => jawda_prepare_property_type_tree_for_js(),
            'selected_property_types'  => $selected_property_types,
            'selected_property_type'   => $selected_property_type,
            'post_type'                => $post_type,
            'strings'                  => $strings,
            'language'                => $language,
        ]
    );
}

add_action('carbon_fields_post_meta_container_saved', 'aqarand_sync_project_category_meta_across_languages', 25, 2);
function aqarand_sync_project_category_meta_across_languages($post_id, $container) {
    $post_type = get_post_type($post_id);

    if ($post_type !== 'projects') {
        return;
    }

    if (!function_exists('pll_get_post_translations') || !function_exists('pll_get_post_language')) {
        return;
    }

    $translations = pll_get_post_translations($post_id);

    if (empty($translations) || !is_array($translations)) {
        return;
    }

    $current_language = pll_get_post_language($post_id);

    if (!$current_language || !isset($translations[$current_language])) {
        return;
    }

    $main_category = '';

    if (function_exists('carbon_get_post_meta')) {
        $main_category = carbon_get_post_meta($post_id, 'jawda_main_category_id');
    } else {
        $main_category = get_post_meta($post_id, 'jawda_main_category_id', true);
    }

    if (is_array($main_category)) {
        $main_category = reset($main_category);
    }

    $main_category = is_scalar($main_category) ? (string) $main_category : '';

    if ($main_category === '0') {
        $main_category = '';
    }

    if (function_exists('carbon_get_post_meta')) {
        $property_types_raw = carbon_get_post_meta($post_id, 'jawda_property_type_ids');
    } else {
        $property_types_raw = get_post_meta($post_id, 'jawda_property_type_ids', true);
    }

    if (!is_array($property_types_raw)) {
        $property_types_raw = $property_types_raw !== '' ? [$property_types_raw] : [];
    }

    $property_types = [];

    foreach ($property_types_raw as $type_id) {
        $type_id = is_scalar($type_id) ? (string) $type_id : '';

        if ($type_id === '' || $type_id === '0') {
            continue;
        }

        $property_types[] = $type_id;
    }

    if ($property_types) {
        $property_types = array_values(array_unique($property_types));
    }

    $service_meta_keys = [
        'feature'  => 'jawda_project_service_feature_ids',
        'amenity'  => 'jawda_project_service_amenity_ids',
        'facility' => 'jawda_project_service_facility_ids',
    ];

    $project_services = [];
    $aggregated_features = [];

    foreach ($service_meta_keys as $type => $meta_key) {
        if (function_exists('carbon_get_post_meta')) {
            $raw_values = carbon_get_post_meta($post_id, $meta_key);
        } else {
            $raw_values = get_post_meta($post_id, $meta_key, true);
        }

        if (function_exists('jawda_project_features_normalize_selection')) {
            $normalized = jawda_project_features_normalize_selection($raw_values, [$type]);
        } else {
            $normalized = is_array($raw_values) ? array_filter($raw_values) : ($raw_values !== '' ? [$raw_values] : []);
        }

        $project_services[$meta_key] = $normalized;

        if (!empty($normalized)) {
            $aggregated_features = array_merge($aggregated_features, $normalized);
        }
    }

    if (empty($aggregated_features) && function_exists('jawda_project_features_normalize_selection')) {
        if (function_exists('carbon_get_post_meta')) {
            $legacy_raw = carbon_get_post_meta($post_id, 'jawda_project_feature_ids');
        } else {
            $legacy_raw = get_post_meta($post_id, 'jawda_project_feature_ids', true);
        }

        $legacy_features = jawda_project_features_normalize_selection($legacy_raw);

        if (!empty($legacy_features)) {
            $project_services['jawda_project_service_feature_ids'] = $legacy_features;
            $aggregated_features = $legacy_features;
        }
    }

    if (!empty($aggregated_features)) {
        $aggregated_features = array_map('intval', $aggregated_features);
        $aggregated_features = array_values(array_unique($aggregated_features));
        $aggregated_features = array_map('strval', $aggregated_features);
    } else {
        $aggregated_features = [];
    }

    if (!empty($aggregated_features)) {
        update_post_meta($post_id, 'jawda_project_feature_ids', $aggregated_features);
    } else {
        delete_post_meta($post_id, 'jawda_project_feature_ids');
    }

    foreach ($translations as $language => $translation_id) {
        $translation_id = (int) $translation_id;

        if ($translation_id <= 0 || $translation_id === (int) $post_id) {
            continue;
        }

        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($translation_id, 'jawda_main_category_id', $main_category);
            carbon_set_post_meta($translation_id, 'jawda_property_type_ids', $property_types);
            foreach ($service_meta_keys as $type => $meta_key) {
                carbon_set_post_meta($translation_id, $meta_key, $project_services[$meta_key] ?? []);
            }
            carbon_set_post_meta($translation_id, 'jawda_project_feature_ids', $aggregated_features);
        } else {
            if ($main_category !== '') {
                update_post_meta($translation_id, 'jawda_main_category_id', $main_category);
            } else {
                delete_post_meta($translation_id, 'jawda_main_category_id');
            }

            if (!empty($property_types)) {
                update_post_meta($translation_id, 'jawda_property_type_ids', $property_types);
            } else {
                delete_post_meta($translation_id, 'jawda_property_type_ids');
            }

            foreach ($service_meta_keys as $type => $meta_key) {
                $values = $project_services[$meta_key] ?? [];
                if (!empty($values)) {
                    update_post_meta($translation_id, $meta_key, $values);
                } else {
                    delete_post_meta($translation_id, $meta_key);
                }
            }

            if (!empty($aggregated_features)) {
                update_post_meta($translation_id, 'jawda_project_feature_ids', $aggregated_features);
            } else {
                delete_post_meta($translation_id, 'jawda_project_feature_ids');
            }
        }
    }
}

function jawda_get_property_types_for_meta_box() {
    $options = [];

    $map = jawda_collect_property_types_map();

    foreach ($map as $category) {
        if (empty($category['types']) || !is_array($category['types'])) {
            continue;
        }

        foreach ($category['types'] as $type) {
            if (!isset($type['id'])) {
                continue;
            }

            $value = (string) $type['id'];
            $label = isset($type['name']) && $type['name'] !== '' ? $type['name'] : $value;

            $options[$value] = $label;
        }
    }

    return $options;
}
