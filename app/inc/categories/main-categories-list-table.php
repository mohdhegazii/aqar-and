<?php
/**
 * WP_List_Table class for Main Categories.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Jawda_Main_Categories_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct([
            'singular' => __('Main Category', 'aqarand'),
            'plural'   => __('Main Categories', 'aqarand'),
            'ajax'     => false
        ]);
    }

    /**
     * Get the table data.
     *
     * @return array
     */
    public static function get_categories($per_page = 20, $page_number = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'property_categories';

        $sql = "SELECT * FROM {$table_name}";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY name_en ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
        return $result;
    }

    /**
     * Delete a category record.
     *
     * @param int $id Category ID.
     */
    public static function delete_category($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'property_categories';
        $wpdb->delete($table_name, ['id' => $id], ['%d']);
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'property_categories';
        $sql = "SELECT COUNT(*) FROM {$table_name}";
        return $wpdb->get_var($sql);
    }

    /**
     * Text displayed when no category data is available.
     */
    public function no_items() {
        _e('No main categories found.', 'aqarand');
    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name_ar':
            case 'name_en':
                return esc_html($item[$column_name]);
            case 'id':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Method for name column.
     */
    function column_name_en($item) {
        $delete_nonce = wp_create_nonce('jawda_delete_main_category');
        $title = '<strong>' . esc_html($item['name_en']) . '</strong>';

        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">' . __('Edit', 'aqarand') . '</a>', esc_attr($_REQUEST['page']), 'edit', absint($item['id'])),
            'delete' => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __('Delete', 'aqarand') . '</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['id']), $delete_nonce)
        ];

        return $title . $this->row_actions($actions);
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'id'      => __('ID', 'aqarand'),
            'name_en' => __('Name (English)', 'aqarand'),
            'name_ar' => __('Name (Arabic)', 'aqarand'),
        ];
        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'id' => array('id', true),
            'name_en' => array('name_en', false),
            'name_ar' => array('name_ar', false)
        );
        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
            'bulk-delete' => 'Delete'
        ];
        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        $per_page     = $this->get_items_per_page('categories_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $this->items = self::get_categories($per_page, $current_page);
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            // In WordPress, nonce is checked via check_admin_referer() function
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            if (!wp_verify_nonce($nonce, 'jawda_delete_main_category')) {
                die('Go get a life script kiddies');
            } else {
                self::delete_category(absint($_GET['id']));
                // esc_url_raw() is used to prevent converting ampersand in url to html entity
                wp_redirect(esc_url_raw(add_query_arg()));
                exit;
            }
        }

        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')) {

            check_admin_referer('bulk-' . $this->_args['plural']);
            $delete_ids = array_map('absint', $_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                self::delete_category($id);
            }

            wp_redirect(esc_url_raw(add_query_arg()));
            exit;
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['id']
        );
    }
}
