<?php

// Security Check
if (!defined('ABSPATH')) {
    die('Invalid request.');
}

/* -----------------------------------------------------------------------------
# PostTypes
----------------------------------------------------------------------------- */

// Import PostTypes
use PostTypes\PostType;
use PostTypes\Taxonomy;

/* -----------------------------------------------------
# projects (جمع) - العودة إلى الاسم الأصلي
----------------------------------------------------- */

// تعديل الـ rewrite structure للـ projects ليكون ثابت
$projects_options = [
    'supports' => array('title', 'editor', 'thumbnail', 'author'),
    'rewrite'   => [
        'slug'      => 'projects',
        'with_front' => false
    ],
    'has_archive' => true,
    'public'      => true,
];

$projects_names = [
    'name'      => 'projects', // جمع - العودة إلى الاسم الأصلي
    'singular'  => 'Project',
    'plural'    => 'Projects',
    'slug'      => 'projects' // جمع - العودة إلى الاسم الأصلي
];

$projects = new PostType($projects_names, $projects_options);
$projects->icon('dashicons-location-alt');
$projects->register();

// تصنيفات (Taxonomies) للمشاريع
$project_taxonomies = [
    ['projects_category', 'Project Category', 'Projects Categories', 'projects-cat'],
    ['projects_tag', 'Project Tag', 'Projects Tags', 'projects_tag'],
    ['projects_developer', 'Project Developer', 'Projects Developers', 'developer'],
    ['projects_area', 'Project Area', 'Projects Area', 'place'],
    ['projects_type', 'Project Type', 'Projects Type', 'project-type'],
    ['projects_features', 'Project Features', 'Projects Features', 'project-features'],
];

foreach ($project_taxonomies as $taxonomy) {
    list($name, $singular, $plural, $slug) = $taxonomy;
    $tax = new Taxonomy([
        'name'      => $name,
        'singular'  => $singular,
        'plural'    => $plural,
        'slug'      => $slug
    ]);
    $tax->posttype('projects'); // استخدام 'projects' الجمع
    $tax->register();
}

/* -----------------------------------------------------
# properties
----------------------------------------------------- */

$property_options = [
    'supports' => array('title', 'editor', 'thumbnail', 'author')
];
$property_names = [
    'name'      => 'property',
    'singular'  => 'Property',
    'plural'    => 'Properties',
    'slug'      => 'property'
];
$property = new PostType($property_names, $property_options);
$property->icon('dashicons-admin-multisite');
$property->register();

// تصنيفات (Taxonomies) للعقارات
$property_taxonomies = [
    ['property_label', 'Listing Project', 'Listing Projects', 'listing'],
    ['property_type', 'Type', 'Types', 'property-type'],
    ['property_feature', 'Feature', 'Features', 'feature'],
    ['property_city', 'City', 'Cities', 'city'],
    ['property_state', 'State', 'States', 'state'],
    ['property_status', 'Status', 'Status', 'status'],
];

foreach ($property_taxonomies as $taxonomy) {
    list($name, $singular, $plural, $slug) = $taxonomy;
    $tax = new Taxonomy([
        'name'      => $name,
        'singular'  => $singular,
        'plural'    => $plural,
        'slug'      => $slug
    ]);
    $tax->posttype('property');
    $tax->register();
}

/* -----------------------------------------------------
# catalogs
----------------------------------------------------- */

$catalogs_options = [
    'supports'      => array('title', 'editor', 'thumbnail', 'page-attributes'),
    'hierarchical'  => true,
    'public'        => true,
    'has_archive'   => true,
    'rewrite'       => array(
        'slug'         => 'catalog',
        'with_front'   => false,
        'hierarchical' => true,
        'pages'        => true,
    ),
];
$catalogs_names = [
    'name'      => 'catalogs',
    'singular'  => 'Catalog',
    'plural'    => 'Catalogs',
    'slug'      => 'catalog'
];
$catalogs = new PostType($catalogs_names, $catalogs_options);
$catalogs->icon('dashicons-format-aside');
$catalogs->register();

add_filter('post_type_link', 'jawda_project_permalink', 10, 2);
function jawda_project_permalink($post_link, $post) {
    if (is_object($post) && $post->post_type == 'projects') {
        $terms = wp_get_object_terms($post->ID, 'projects_developer');
        if ($terms && !is_wp_error($terms)) {
            // Build the permalink from scratch to ensure the correct structure
            return home_url(user_trailingslashit($terms[0]->slug . '/' . $post->post_name));
        }
    }
    return $post_link;
}

// Flush rewrite rules for hierarchical CPT
add_action('init', 'jawda_flush_rewrite_rules_once', 99);
function jawda_flush_rewrite_rules_once() {
    if (get_option('jawda_rewrite_rules_flushed_v12') != 1) {
        flush_rewrite_rules();
        update_option('jawda_rewrite_rules_flushed_v12', 1);
    }
}

/*
 * Add custom columns to the projects post type list.
 */
add_filter('manage_edit-projects_columns', 'jawda_add_project_list_columns');
function jawda_add_project_list_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key === 'taxonomy-projects_area') {
            $new_columns['location'] = __('Location', 'jawda');
        }
        if ($key === 'taxonomy-projects_type') {
            $new_columns['main_category'] = __('Category', 'jawda');
        }
    }
    return $new_columns;
}

/*
 * Render content for the custom columns on the projects post type list.
 */
add_action('manage_projects_posts_custom_column', 'jawda_render_project_list_custom_columns', 10, 2);
function jawda_render_project_list_custom_columns($column, $post_id) {
    global $wpdb;

    $is_arabic = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : is_rtl();
    $name_col = $is_arabic ? 'name_ar' : 'name_en';
    $base_url = admin_url('edit.php?post_type=projects');

    switch ($column) {
        case 'location':
            $gov_id = get_post_meta($post_id, 'loc_governorate_id', true);
            $city_id = get_post_meta($post_id, 'loc_city_id', true);
            $district_id = get_post_meta($post_id, 'loc_district_id', true);

            $location_parts = [];

            if ($gov_id) {
                $gov_name = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}locations_governorates WHERE id = %d", $gov_id));
                if ($gov_name) {
                    $url = add_query_arg('filter_governorate_id', $gov_id, $base_url);
                    $location_parts[] = sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($gov_name));
                }
            }

            if ($city_id) {
                $city_name = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}locations_cities WHERE id = %d", $city_id));
                if ($city_name) {
                    $url = add_query_arg('filter_city_id', $city_id, $base_url);
                    $location_parts[] = sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($city_name));
                }
            }

            if ($district_id) {
                $district_name = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}locations_districts WHERE id = %d", $district_id));
                if ($district_name) {
                    $url = add_query_arg('filter_district_id', $district_id, $base_url);
                    $location_parts[] = sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($district_name));
                }
            }

            if (!empty($location_parts)) {
                echo implode(', ', $location_parts);
            }
            break;

        case 'main_category':
            $main_category_id = carbon_get_post_meta($post_id, 'jawda_main_category_id');
            if ($main_category_id) {
                $category_name = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}property_categories WHERE id = %d", $main_category_id));
                if ($category_name) {
                    $url = add_query_arg('filter_main_category_id', $main_category_id, $base_url);
                    echo sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($category_name));
                }
            }
            break;
    }
}

/*
 * Modify the main query for the projects list to handle custom filtering.
 */
add_action('pre_get_posts', 'jawda_handle_project_list_custom_filters');
function jawda_handle_project_list_custom_filters($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'projects') {
        return;
    }

    $meta_query = $query->get('meta_query') ?: [];

    // Filter by Governorate
    if (!empty($_GET['filter_governorate_id'])) {
        $meta_query[] = [
            'key' => 'loc_governorate_id',
            'value' => absint($_GET['filter_governorate_id']),
            'compare' => '='
        ];
    }

    // Filter by City
    if (!empty($_GET['filter_city_id'])) {
        $meta_query[] = [
            'key' => 'loc_city_id',
            'value' => absint($_GET['filter_city_id']),
            'compare' => '='
        ];
    }

    // Filter by District
    if (!empty($_GET['filter_district_id'])) {
        $meta_query[] = [
            'key' => 'loc_district_id',
            'value' => absint($_GET['filter_district_id']),
            'compare' => '='
        ];
    }

    // Filter by Main Category
    if (!empty($_GET['filter_main_category_id'])) {
        $meta_query[] = [
            'key' => 'jawda_main_category_id',
            'value' => absint($_GET['filter_main_category_id']),
            'compare' => '='
        ];
    }

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}
