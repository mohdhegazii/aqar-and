<?php
/*
Plugin Name: Aqarand Locations Admin
Description: Admin interface for managing locations.
Version: 1.0
Author: Jules
*/

if (!defined('ABSPATH')) {
    exit;
}

define('AQARAND_LOCATIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));

add_action('admin_init', 'aqarand_locations_install_check');

function aqarand_locations_install_check() {
    if (!get_option('aqarand_locations_installed')) {
        aqarand_locations_install();
        update_option('aqarand_locations_installed', true);
    }
}

function aqarand_locations_install() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'locations_governorates';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name_ar varchar(255) NOT NULL,
        name_en varchar(255) NOT NULL,
        latitude decimal(10,7) DEFAULT NULL,
        longitude decimal(10,7) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'locations_cities';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        governorate_id mediumint(9) NOT NULL,
        name_ar varchar(255) NOT NULL,
        name_en varchar(255) NOT NULL,
        latitude decimal(10,7) DEFAULT NULL,
        longitude decimal(10,7) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'locations_districts';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        city_id mediumint(9) NOT NULL,
        name_ar varchar(255) NOT NULL,
        name_en varchar(255) NOT NULL,
        latitude decimal(10,7) DEFAULT NULL,
        longitude decimal(10,7) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);
}

require_once AQARAND_LOCATIONS_PLUGIN_DIR . 'governorates.php';
require_once AQARAND_LOCATIONS_PLUGIN_DIR . 'cities.php';
require_once AQARAND_LOCATIONS_PLUGIN_DIR . 'districts.php';

add_action('admin_init', 'aqarand_locations_maybe_upgrade_schema');
function aqarand_locations_maybe_upgrade_schema() {
    global $wpdb;

    $tables = [
        $wpdb->prefix . 'locations_governorates' => [
            'latitude' => 'ALTER TABLE %s ADD COLUMN latitude decimal(10,7) DEFAULT NULL AFTER name_en',
            'longitude' => 'ALTER TABLE %s ADD COLUMN longitude decimal(10,7) DEFAULT NULL AFTER latitude',
        ],
        $wpdb->prefix . 'locations_cities' => [
            'latitude' => 'ALTER TABLE %s ADD COLUMN latitude decimal(10,7) DEFAULT NULL AFTER name_en',
            'longitude' => 'ALTER TABLE %s ADD COLUMN longitude decimal(10,7) DEFAULT NULL AFTER latitude',
        ],
        $wpdb->prefix . 'locations_districts' => [
            'latitude' => 'ALTER TABLE %s ADD COLUMN latitude decimal(10,7) DEFAULT NULL AFTER name_en',
            'longitude' => 'ALTER TABLE %s ADD COLUMN longitude decimal(10,7) DEFAULT NULL AFTER latitude',
        ],
    ];

    foreach ($tables as $table => $columns) {
        foreach ($columns as $column => $query) {
            $has_column = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', $column));
            if (null === $has_column) {
                $wpdb->query(sprintf($query, $table));
            }
        }
    }
}

function aqarand_locations_normalize_coordinate($value) {
    if (!isset($value)) {
        return null;
    }

    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $value = str_replace(',', '.', $value);

    return is_numeric($value) ? $value : null;
}

/**
 * Normalize a language indicator into a consistent value.
 *
 * @param string $value   Raw language indicator (e.g. "ar", "en-US", "both").
 * @param string $default Default value when the indicator cannot be resolved.
 *
 * @return string One of 'ar', 'en', or 'both'.
 */
function aqarand_locations_normalize_language($value, $default = 'ar') {
    $value = strtolower(trim((string) $value));

    if ($value === 'both' || $value === 'bilingual') {
        return 'both';
    }

    if ($value === '') {
        return $default;
    }

    if (strpos($value, 'en') === 0) {
        return 'en';
    }

    if (strpos($value, 'ar') === 0) {
        return 'ar';
    }

    return $default;
}

/**
 * Build a bilingual label from Arabic and English names.
 *
 * @param string $name_ar Arabic label.
 * @param string $name_en English label.
 * @param string $fallback Fallback label when both names are empty.
 *
 * @return string
 */
function aqarand_locations_format_bilingual_label($name_ar, $name_en, $fallback = '') {
    $parts = [];

    foreach ([$name_ar, $name_en] as $name) {
        $name = trim((string) $name);

        if ($name === '') {
            continue;
        }

        if (!in_array($name, $parts, true)) {
            $parts[] = $name;
        }
    }

    if (empty($parts) && $fallback !== '') {
        return $fallback;
    }

    return implode(' / ', $parts);
}

/**
 * Resolve the appropriate label based on the requested language.
 *
 * @param string $name_ar Arabic label.
 * @param string $name_en English label.
 * @param string $language Requested language ('ar', 'en', or 'both').
 * @param string $fallback Fallback label when both names are empty.
 *
 * @return string
 */
function aqarand_locations_get_label($name_ar, $name_en, $language = 'ar', $fallback = '') {
    $name_ar = trim((string) $name_ar);
    $name_en = trim((string) $name_en);
    $language = aqarand_locations_normalize_language($language, 'ar');

    if ($language === 'both') {
        return aqarand_locations_format_bilingual_label($name_ar, $name_en, $fallback);
    }

    if ($language === 'en') {
        if ($name_en !== '') {
            return $name_en;
        }

        if ($name_ar !== '') {
            return $name_ar;
        }

        return $fallback;
    }

    if ($name_ar !== '') {
        return $name_ar;
    }

    if ($name_en !== '') {
        return $name_en;
    }

    return $fallback;
}

/**
 * Resolve bilingual placeholders for select inputs.
 *
 * @param string $placeholder_ar Arabic placeholder.
 * @param string $placeholder_en English placeholder.
 * @param string $language Requested language ('ar', 'en', or 'both').
 *
 * @return string
 */
function aqarand_locations_get_placeholder($placeholder_ar, $placeholder_en, $language = 'ar') {
    $placeholder_ar = trim((string) $placeholder_ar);
    $placeholder_en = trim((string) $placeholder_en);
    $language = aqarand_locations_normalize_language($language, 'ar');

    if ($language === 'both') {
        $parts = [];

        foreach ([$placeholder_ar, $placeholder_en] as $placeholder) {
            if ($placeholder === '') {
                continue;
            }

            if (!in_array($placeholder, $parts, true)) {
                $parts[] = $placeholder;
            }
        }

        return implode(' / ', $parts);
    }

    return $language === 'en' ? $placeholder_en : $placeholder_ar;
}

function aqarand_locations_render_coordinate_fields($args) {
    $defaults = [
        'lat_id'    => '',
        'lat_name'  => 'latitude',
        'lat_value' => '',
        'lng_id'    => '',
        'lng_name'  => 'longitude',
        'lng_value' => '',
        'map_id'    => '',
        'label'     => __('Map Preview', 'aqarand'),
    ];

    $args = wp_parse_args($args, $defaults);

    aqarand_locations_ensure_map_assets();

    if ('' === $args['map_id']) {
        $args['map_id'] = uniqid('aqarand-location-map-');
    }

    $initial_lat = $args['lat_value'] !== '' ? $args['lat_value'] : '30.0444';
    $initial_lng = $args['lng_value'] !== '' ? $args['lng_value'] : '31.2357';

    ?>
    <div class="form-field">
        <label for="<?php echo esc_attr($args['lat_id']); ?>"><?php esc_html_e('Latitude', 'aqarand'); ?></label>
        <input type="text" name="<?php echo esc_attr($args['lat_name']); ?>" id="<?php echo esc_attr($args['lat_id']); ?>" value="<?php echo esc_attr($args['lat_value']); ?>">
    </div>
    <div class="form-field">
        <label for="<?php echo esc_attr($args['lng_id']); ?>"><?php esc_html_e('Longitude', 'aqarand'); ?></label>
        <input type="text" name="<?php echo esc_attr($args['lng_name']); ?>" id="<?php echo esc_attr($args['lng_id']); ?>" value="<?php echo esc_attr($args['lng_value']); ?>">
    </div>
    <div class="form-field">
        <label><?php echo esc_html($args['label']); ?></label>
        <div class="aqarand-location-picker" data-lat-input="#<?php echo esc_attr($args['lat_id']); ?>" data-lng-input="#<?php echo esc_attr($args['lng_id']); ?>">
            <div class="aqarand-location-picker__map" id="<?php echo esc_attr($args['map_id']); ?>" data-initial-lat="<?php echo esc_attr($initial_lat); ?>" data-initial-lng="<?php echo esc_attr($initial_lng); ?>"></div>
            <p class="description"><?php esc_html_e('Click the map to populate the latitude and longitude fields.', 'aqarand'); ?></p>
        </div>
    </div>
    <?php
}

function aqarand_locations_register_map_assets() {
    if (!function_exists('wp_register_style') || !function_exists('wp_register_script')) {
        return;
    }

    if (!wp_style_is('leaflet', 'registered')) {
        wp_register_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );
    }

    if (!wp_style_is('aqarand-locations-admin', 'registered')) {
        wp_register_style(
            'aqarand-locations-admin',
            get_template_directory_uri() . '/admin/locations-map.css',
            ['leaflet'],
            '1.1.0'
        );
    }

    if (!wp_script_is('leaflet', 'registered')) {
        wp_register_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );
    }

    if (!wp_script_is('aqarand-locations-map', 'registered')) {
        wp_register_script(
            'aqarand-locations-map',
            get_template_directory_uri() . '/admin/locations-map.js',
            ['leaflet'],
            '1.1.0',
            true
        );
    }
}

function aqarand_locations_ensure_map_assets() {
    aqarand_locations_register_map_assets();

    if (function_exists('wp_enqueue_style')) {
        wp_enqueue_style('leaflet');
        wp_enqueue_style('aqarand-locations-admin');
    }

    if (function_exists('wp_enqueue_script')) {
        wp_enqueue_script('leaflet');
        wp_enqueue_script('aqarand-locations-map');
    }
}

add_action('admin_enqueue_scripts', 'aqarand_locations_enqueue_assets');
function aqarand_locations_enqueue_assets($hook_suffix) {
    $allowed_pages = [
        'toplevel_page_aqarand-locations-governorates',
        'aqarand-locations-governorates_page_aqarand-locations-cities',
        'aqarand-locations-governorates_page_aqarand-locations-districts',
    ];

    $should_enqueue = in_array($hook_suffix, $allowed_pages, true);

    if (!$should_enqueue && false !== strpos($hook_suffix, 'aqarand-locations-')) {
        $should_enqueue = true;
    }

    if (!$should_enqueue) {
        $page_param = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        if ($page_param && 0 === strpos($page_param, 'aqarand-locations-')) {
            $should_enqueue = true;
        }
    }

    if (!$should_enqueue && in_array($hook_suffix, ['post.php', 'post-new.php'], true)) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if ($screen && 'projects' === $screen->post_type) {
            $should_enqueue = true;
        }
    }

    if (!$should_enqueue) {
        return;
    }

    aqarand_locations_ensure_map_assets();
}

class Aqarand_Locations_Admin {
    private $governorates_page;
    private $cities_page;
    private $districts_page;
    private $governorates_table;
    private $cities_table;
    private $districts_table;

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function admin_menu() {
        $this->governorates_page = add_menu_page(
            'Locations',
            'Locations',
            'manage_options',
            'aqarand-locations-governorates',
            [$this, 'render_governorates_page'],
            'dashicons-location-alt',
            20
        );

        $this->cities_page = add_submenu_page(
            'aqarand-locations-governorates',
            'Cities',
            'Cities',
            'manage_options',
            'aqarand-locations-cities',
            [$this, 'render_cities_page']
        );

        $this->districts_page = add_submenu_page(
            'aqarand-locations-governorates',
            'Districts',
            'Districts',
            'manage_options',
            'aqarand-locations-districts',
            [$this, 'render_districts_page']
        );

        add_action("load-{$this->governorates_page}", [$this, 'on_load_governorates_page']);
        add_action("load-{$this->cities_page}", [$this, 'on_load_cities_page']);
        add_action("load-{$this->districts_page}", [$this, 'on_load_districts_page']);
    }

    public function on_load_governorates_page() {
        $this->governorates_table = new Aqarand_Governorates_List_Table();
        $this->governorates_table->process_bulk_action();
        $this->governorates_table->handle_form_submission();
    }

    public function on_load_cities_page() {
        $this->cities_table = new Aqarand_Cities_List_Table();
        $this->cities_table->process_bulk_action();
        $this->cities_table->handle_form_submission();
    }

    public function on_load_districts_page() {
        $this->districts_table = new Aqarand_Districts_List_Table();
        $this->districts_table->process_bulk_action();
        $this->districts_table->handle_form_submission();
    }

    public function render_governorates_page() {
        $this->governorates_table->render_page();
    }

    public function render_cities_page() {
        $this->cities_table->render_page();
    }

    public function render_districts_page() {
        $this->districts_table->render_page();
    }
}

new Aqarand_Locations_Admin();
