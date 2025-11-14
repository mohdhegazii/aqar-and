<?php
/**
 * Admin Menu setup for the Categories system.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Jawda_Categories_Admin {

    private $main_categories_page_handler;
    private $property_types_page_handler;
    private $alternative_names_page_handler;

    /**
     * Constructor. Hooks into admin_menu.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    /**
     * Registers the main menu and sub-menus for the Categories system.
     */
    public function register_admin_menu() {
        $main_categories_hook = add_menu_page(
            __('Categories', 'aqarand'),
            __('Categories', 'aqarand'),
            'manage_options',
            'jawda-categories-main',
            [$this, 'render_main_categories_page'],
            'dashicons-tag',
            21 // Position after Locations
        );

        add_submenu_page(
            'jawda-categories-main',
            __('Main Categories', 'aqarand'),
            __('Main Categories', 'aqarand'),
            'manage_options',
            'jawda-categories-main', // This makes it the default page for the main menu
            [$this, 'render_main_categories_page']
        );

        $property_types_hook = add_submenu_page(
            'jawda-categories-main',
            __('Property Types', 'aqarand'),
            __('Property Types', 'aqarand'),
            'manage_options',
            'jawda-property-types',
            [$this, 'render_property_types_page']
        );

        $alternative_names_hook = add_submenu_page(
            'jawda-categories-main',
            __('Alternative Names', 'aqarand'),
            __('Alternative Names', 'aqarand'),
            'manage_options',
            'jawda-alternative-names',
            [$this, 'render_alternative_names_page']
        );

        add_action("load-{$main_categories_hook}", [$this, 'on_load_main_categories_page']);
        add_action("load-{$property_types_hook}", [$this, 'on_load_property_types_page']);
        add_action("load-{$alternative_names_hook}", [$this, 'on_load_alternative_names_page']);
    }

    /**
     * On-load handler for the main categories page.
     * Instantiates the page handler and processes form submissions.
     */
    public function on_load_main_categories_page() {
        require_once __DIR__ . '/main-categories-list-table.php';
        require_once __DIR__ . '/main-categories-page.php';
        $this->main_categories_page_handler = new Jawda_Main_Categories_Page();
        $this->main_categories_page_handler->handle_form_submission();
        $this->main_categories_page_handler->list_table->process_bulk_action();
        $this->main_categories_page_handler->list_table->prepare_items();
    }

    /**
     * On-load handler for the property types page.
     */
    public function on_load_property_types_page() {
        require_once __DIR__ . '/property-types-list-table.php';
        require_once __DIR__ . '/property-types-page.php';
        $this->property_types_page_handler = new Jawda_Property_Types_Page();
        $this->property_types_page_handler->handle_form_submission();
        $this->property_types_page_handler->list_table->process_bulk_action();
        $this->property_types_page_handler->list_table->prepare_items();
    }

    /**
     * On-load handler for the alternative names page.
     */
    public function on_load_alternative_names_page() {
        require_once __DIR__ . '/alternative-names-list-table.php';
        require_once __DIR__ . '/alternative-names-page.php';
        $this->alternative_names_page_handler = new Jawda_Alternative_Names_Page();
        $this->alternative_names_page_handler->handle_form_submission();
        $this->alternative_names_page_handler->list_table->process_bulk_action();
        $this->alternative_names_page_handler->list_table->prepare_items();
    }

    /**
     * Renders the Main Categories page.
     */
    public function render_main_categories_page() {
        if ($this->main_categories_page_handler) {
            $this->main_categories_page_handler->render_page();
        }
    }

    /**
     * Renders the Property Types page.
     */
    public function render_property_types_page() {
        if ($this->property_types_page_handler) {
            $this->property_types_page_handler->render_page();
        }
    }

    /**
     * Renders the Alternative Names page.
     */
    public function render_alternative_names_page() {
        if ($this->alternative_names_page_handler) {
            $this->alternative_names_page_handler->render_page();
        }
    }
}

// Instantiate the admin menu class.
new Jawda_Categories_Admin();
