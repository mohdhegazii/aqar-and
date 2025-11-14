<?php
/**
 * Database setup for the new Categories system.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Forcefully removes the old category_id column and foreign key from property_types table.
 * This runs once and ensures the schema is corrected.
 */
function jawda_force_schema_update_v4() {
    // Check if this specific migration has already run
    if (get_option('jawda_schema_update_v4_completed')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'property_types';
    $db_name = DB_NAME;

    // Check if the column exists
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
        $db_name, $table_name, 'category_id'
    ));

    if (!empty($column_exists)) {
        // First, check if the foreign key constraint exists
        $constraint_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s AND REFERENCED_TABLE_NAME IS NOT NULL",
            $db_name, $table_name, 'category_id'
        ));

        if (!empty($constraint_exists)) {
             // Get the constraint name
            $constraint_name = $constraint_exists[0]->CONSTRAINT_NAME;
            // Drop the foreign key
            $wpdb->query("ALTER TABLE `{$table_name}` DROP FOREIGN KEY `{$constraint_name}`");
        }

        // Now, drop the column
        $wpdb->query("ALTER TABLE `{$table_name}` DROP COLUMN `category_id`");
    }

    // Mark this migration as complete
    update_option('jawda_schema_update_v4_completed', true);
}
add_action('admin_init', 'jawda_force_schema_update_v4');


/**
 * Creates and updates the custom tables for the categories system.
 * This function is hooked into 'admin_init' and runs only once per version.
 */
function jawda_categories_install() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Check if the installation has already been run for the latest version.
    if (get_option('jawda_categories_installed_v4')) {
        return;
    }

    $charset_collate = $wpdb->get_charset_collate();

    // Table: property_categories
    $table_name_categories = $wpdb->prefix . 'property_categories';
    $sql_categories = "CREATE TABLE $table_name_categories (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name_ar VARCHAR(255) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_categories);

    // Table: property_types (Ensuring category_id is removed)
    $table_name_types = $wpdb->prefix . 'property_types';
    $sql_types = "CREATE TABLE $table_name_types (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name_ar VARCHAR(255) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        image_id BIGINT(20) UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_types);

    // New Table: property_type_category_relationships
    $table_name_relationships = $wpdb->prefix . 'property_type_category_relationships';
    $sql_type_cat_relationships = "CREATE TABLE $table_name_relationships (
        property_type_id BIGINT(20) UNSIGNED NOT NULL,
        category_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY (property_type_id, category_id),
        FOREIGN KEY (property_type_id) REFERENCES {$wpdb->prefix}property_types(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES {$wpdb->prefix}property_categories(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta($sql_type_cat_relationships);

    // Table: property_type_alt_names
    $table_name_alt_names = $wpdb->prefix . 'property_type_alt_names';
    $sql_alt_names = "CREATE TABLE $table_name_alt_names (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        property_type_id BIGINT(20) UNSIGNED NOT NULL,
        alt_name_ar VARCHAR(255) NOT NULL,
        alt_name_en VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (property_type_id) REFERENCES {$wpdb->prefix}property_types(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta($sql_alt_names);

    // Table: project_alt_name_relationships
    $table_name_proj_relationships = $wpdb->prefix . 'project_alt_name_relationships';
    $sql_proj_relationships = "CREATE TABLE $table_name_proj_relationships (
        alt_name_id BIGINT(20) UNSIGNED NOT NULL,
        project_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY (alt_name_id, project_id),
        FOREIGN KEY (alt_name_id) REFERENCES {$wpdb->prefix}property_type_alt_names(id) ON DELETE CASCADE,
        FOREIGN KEY (project_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta($sql_proj_relationships);

    // Mark installation as complete for the new version.
    update_option('jawda_categories_installed_v4', true);
}
add_action('admin_init', 'jawda_categories_install');
