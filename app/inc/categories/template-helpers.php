<?php
/**
 * Template helper functions for the Categories system.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get the display name for a property type, taking into account alternative names for a specific project.
 *
 * @param int      $property_type_id The ID of the property type.
 * @param int|null $project_id Optional. If provided, will check for an alt name attached to this project.
 * @return string The display name.
 */
function jawda_get_property_type_display_name($property_type_id, $project_id = null) {
    global $wpdb;

    $property_type_id = absint($property_type_id);
    if (!$property_type_id) {
        return '';
    }

    $is_arabic = function_exists('aqarand_is_arabic_locale') && aqarand_is_arabic_locale();

    // Check for an alternative name if a project context is given
    if ($project_id) {
        $project_id = absint($project_id);
        $alt_name_table = $wpdb->prefix . 'property_type_alt_names';
        $relationships_table = $wpdb->prefix . 'project_alt_name_relationships';
        $name_col = $is_arabic ? 'alt_name_ar' : 'alt_name_en';

        $alt_name = $wpdb->get_var($wpdb->prepare(
            "SELECT an.{$name_col}
             FROM {$alt_name_table} an
             JOIN {$relationships_table} r ON an.id = r.alt_name_id
             WHERE an.property_type_id = %d AND r.project_id = %d
             LIMIT 1",
            $property_type_id,
            $project_id
        ));

        if ($alt_name) {
            return esc_html($alt_name);
        }
    }

    // Fallback to the base property type name
    $types_table = $wpdb->prefix . 'property_types';
    $name_col = $is_arabic ? 'name_ar' : 'name_en';

    $base_name = $wpdb->get_var($wpdb->prepare(
        "SELECT {$name_col} FROM {$types_table} WHERE id = %d",
        $property_type_id
    ));

    return esc_html($base_name);
}
