<?php
/**
 * WP_List_Table class for Alternative Names.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Jawda_Alternative_Names_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Alternative Name', 'aqarand'),
            'plural'   => __('Alternative Names', 'aqarand'),
            'ajax'     => false
        ]);
    }

    public static function get_alt_names($per_page = 20, $page_number = 1) {
        global $wpdb;
        $alt_names_table = $wpdb->prefix . 'property_type_alt_names';
        $types_table = $wpdb->prefix . 'property_types';
        $relationships_table = $wpdb->prefix . 'project_alt_name_relationships';

        $sql = "SELECT an.*, pt.name_en as property_type_name
                FROM {$alt_names_table} an
                LEFT JOIN {$types_table} pt ON an.property_type_id = pt.id";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY an.id DESC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $results = $wpdb->get_results($sql, 'ARRAY_A');

        // Get associated projects for each alt name
        if ($results) {
            $alt_name_ids = wp_list_pluck($results, 'id');
            $placeholders = implode(',', array_fill(0, count($alt_name_ids), '%d'));
            $projects_sql = "SELECT r.alt_name_id, p.post_title
                             FROM {$relationships_table} r
                             JOIN {$wpdb->posts} p ON r.project_id = p.ID
                             WHERE r.alt_name_id IN ($placeholders)";
            $projects_results = $wpdb->get_results($wpdb->prepare($projects_sql, $alt_name_ids), 'ARRAY_A');

            $projects_map = [];
            foreach ($projects_results as $proj) {
                $projects_map[$proj['alt_name_id']][] = $proj['post_title'];
            }

            foreach ($results as $key => $result) {
                $results[$key]['projects'] = isset($projects_map[$result['id']]) ? implode(', ', $projects_map[$result['id']]) : '—';
            }
        }

        return $results;
    }

    public static function delete_alt_name($id) {
        global $wpdb;
        $alt_names_table = $wpdb->prefix . 'property_type_alt_names';
        $relationships_table = $wpdb->prefix . 'project_alt_name_relationships';

        // Deleting the alt name will also delete relationships due to CASCADE FOREIGN KEY.
        $wpdb->delete($alt_names_table, ['id' => $id], ['%d']);
    }

    public static function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'property_type_alt_names';
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    }

    public function no_items() {
        _e('No alternative names found.', 'aqarand');
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'alt_name_ar':
            case 'property_type_name':
            case 'projects':
                return esc_html($item[$column_name]);
            case 'id':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    function column_alt_name_en($item) {
        $delete_nonce = wp_create_nonce('jawda_delete_alt_name');
        $title = '<strong>' . esc_html($item['alt_name_en']) . '</strong>';

        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">' . __('Edit', 'aqarand') . '</a>', esc_attr($_REQUEST['page']), 'edit', absint($item['id'])),
            'delete' => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __('Delete', 'aqarand') . '</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['id']), $delete_nonce)
        ];

        return $title . $this->row_actions($actions);
    }

    function get_columns() {
        return [
            'cb'                 => '<input type="checkbox" />',
            'id'                 => __('ID', 'aqarand'),
            'alt_name_en'        => __('Alternative Name (English)', 'aqarand'),
            'alt_name_ar'        => __('Alternative Name (Arabic)', 'aqarand'),
            'property_type_name' => __('Base Property Type', 'aqarand'),
            'projects'           => __('Associated Projects', 'aqarand'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'id' => ['id', true],
            'alt_name_en' => ['alt_name_en', false],
            'property_type_name' => ['property_type_id', false],
        ];
    }

    public function get_bulk_actions() {
        return ['bulk-delete' => 'Delete'];
    }

    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        $per_page     = $this->get_items_per_page('alt_names_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $this->items = self::get_alt_names($per_page, $current_page);
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $nonce = esc_attr($_REQUEST['_wpnonce']);
            if (!wp_verify_nonce($nonce, 'jawda_delete_alt_name')) {
                die('Security check failed');
            } else {
                self::delete_alt_name(absint($_GET['id']));
                wp_redirect(esc_url_raw(add_query_arg()));
                exit;
            }
        }

        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')) {

            check_admin_referer('bulk-' . $this->_args['plural']);
            $delete_ids = array_map('absint', $_POST['bulk-delete']);

            foreach ($delete_ids as $id) {
                self::delete_alt_name($id);
            }
            wp_redirect(esc_url_raw(add_query_arg()));
            exit;
        }
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['id']
        );
    }
}
