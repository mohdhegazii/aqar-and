<?php
/**
 * Admin menu and page loader for Project Features lookup management.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Project_Features_Admin {
    private $features_page_hook = '';
    private $finishing_page_hook = '';
    private $views_page_hook = '';
    private $orientations_page_hook = '';
    private $facades_page_hook = '';
    private $marketing_page_hook = '';

    private $page_handler;
    private $finishing_page_handler;
    private $views_page_handler;
    private $orientations_page_handler;
    private $facades_page_handler;
    private $marketing_page_handler;

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {
        $this->features_page_hook = add_menu_page(
            __('Featured', 'aqarand'),
            __('Featured', 'aqarand'),
            'manage_options',
            'jawda-project-features',
            [$this, 'render_page'],
            'dashicons-screenoptions',
            22
        );

        add_submenu_page(
            'jawda-project-features',
            __('Features / Amenities / Facilities', 'aqarand'),
            __('Features / Amenities / Facilities', 'aqarand'),
            'manage_options',
            'jawda-project-features',
            [$this, 'render_page']
        );

        $this->finishing_page_hook = add_submenu_page(
            'jawda-project-features',
            __('Finishing Types', 'aqarand'),
            __('Finishing Types', 'aqarand'),
            'manage_options',
            'jawda-project-features-finishing',
            [$this, 'render_finishing_page']
        );

        $this->views_page_hook = add_submenu_page(
            'jawda-project-features',
            __('Unit Views', 'aqarand'),
            __('Unit Views', 'aqarand'),
            'manage_options',
            'jawda-project-features-views',
            [$this, 'render_views_page']
        );

        $this->orientations_page_hook = add_submenu_page(
            'jawda-project-features',
            __('Orientations', 'aqarand'),
            __('Orientations', 'aqarand'),
            'manage_options',
            'jawda-project-features-orientations',
            [$this, 'render_orientations_page']
        );

        $this->facades_page_hook = add_submenu_page(
            'jawda-project-features',
            __('Facades & Positions', 'aqarand'),
            __('Facades & Positions', 'aqarand'),
            'manage_options',
            'jawda-project-features-facades',
            [$this, 'render_facades_page']
        );

        $this->marketing_page_hook = add_submenu_page(
            'jawda-project-features',
            __('Marketing Orientation Labels', 'aqarand'),
            __('Marketing Orientation Labels', 'aqarand'),
            'manage_options',
            'jawda-project-features-marketing-orientation',
            [$this, 'render_marketing_orientation_page']
        );

        add_action("load-{$this->features_page_hook}", [$this, 'on_load']);
        if ($this->finishing_page_hook) {
            add_action("load-{$this->finishing_page_hook}", [$this, 'on_load_finishing']);
        }
        if ($this->views_page_hook) {
            add_action("load-{$this->views_page_hook}", [$this, 'on_load_views']);
        }
        if ($this->orientations_page_hook) {
            add_action("load-{$this->orientations_page_hook}", [$this, 'on_load_orientations']);
        }
        if ($this->facades_page_hook) {
            add_action("load-{$this->facades_page_hook}", [$this, 'on_load_facades']);
        }
        if ($this->marketing_page_hook) {
            add_action("load-{$this->marketing_page_hook}", [$this, 'on_load_marketing_orientation']);
        }
    }

    public function on_load() {
        $this->page_handler = $this->boot_page_handler([
            'page_slug' => 'jawda-project-features',
            'allowed_types' => ['feature', 'amenity', 'facility'],
        ]);
    }

    public function on_load_finishing() {
        $this->finishing_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-finishing',
            'forced_type'      => 'finishing',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['finishing'],
            'labels'           => [
                'list_title'      => __('Finishing Types', 'aqarand'),
                'add_new'         => __('Add Finishing Type', 'aqarand'),
                'add_heading'     => __('Add Finishing Type', 'aqarand'),
                'edit_heading'    => __('Edit Finishing Type', 'aqarand'),
                'add_button'      => __('Add Finishing Type', 'aqarand'),
                'update_button'   => __('Update Finishing Type', 'aqarand'),
                'success_message' => __('Finishing type saved successfully.', 'aqarand'),
                'delete_success'  => __('Finishing type deleted.', 'aqarand'),
                'delete_error'    => __('Failed to delete finishing type.', 'aqarand'),
            ],
        ]);
    }

    public function on_load_views() {
        $this->views_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-views',
            'forced_type'      => 'view',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['view'],
            'labels'           => [
                'list_title'      => __('Unit Views', 'aqarand'),
                'add_new'         => __('Add View', 'aqarand'),
                'add_heading'     => __('Add View', 'aqarand'),
                'edit_heading'    => __('Edit View', 'aqarand'),
                'add_button'      => __('Add View', 'aqarand'),
                'update_button'   => __('Update View', 'aqarand'),
                'success_message' => __('View saved successfully.', 'aqarand'),
                'delete_success'  => __('View deleted.', 'aqarand'),
                'delete_error'    => __('Failed to delete view.', 'aqarand'),
            ],
        ]);
    }

    public function on_load_orientations() {
        $this->orientations_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-orientations',
            'forced_type'      => 'orientation',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['orientation'],
            'labels'           => [
                'list_title'      => __('Orientations', 'aqarand'),
                'add_new'         => __('Add Orientation', 'aqarand'),
                'add_heading'     => __('Add Orientation', 'aqarand'),
                'edit_heading'    => __('Edit Orientation', 'aqarand'),
                'add_button'      => __('Add Orientation', 'aqarand'),
                'update_button'   => __('Update Orientation', 'aqarand'),
                'success_message' => __('Orientation saved successfully.', 'aqarand'),
                'delete_success'  => __('Orientation deleted.', 'aqarand'),
                'delete_error'    => __('Failed to delete orientation.', 'aqarand'),
            ],
        ]);
    }

    public function on_load_facades() {
        $this->facades_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-facades',
            'forced_type'      => 'facade',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['facade'],
            'labels'           => [
                'list_title'      => __('Facades & Positions', 'aqarand'),
                'add_new'         => __('Add Facade / Position', 'aqarand'),
                'add_heading'     => __('Add Facade / Position', 'aqarand'),
                'edit_heading'    => __('Edit Facade / Position', 'aqarand'),
                'add_button'      => __('Add Facade / Position', 'aqarand'),
                'update_button'   => __('Update Facade / Position', 'aqarand'),
                'success_message' => __('Facade saved successfully.', 'aqarand'),
                'delete_success'  => __('Facade deleted.', 'aqarand'),
                'delete_error'    => __('Failed to delete facade.', 'aqarand'),
            ],
        ]);
    }

    public function on_load_marketing_orientation() {
        $this->marketing_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-marketing-orientation',
            'forced_type'      => 'marketing_orientation',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['marketing_orientation'],
            'labels'           => [
                'list_title'      => __('Marketing Orientation Labels', 'aqarand'),
                'add_new'         => __('Add Marketing Label', 'aqarand'),
                'add_heading'     => __('Add Marketing Label', 'aqarand'),
                'edit_heading'    => __('Edit Marketing Label', 'aqarand'),
                'add_button'      => __('Add Marketing Label', 'aqarand'),
                'update_button'   => __('Update Marketing Label', 'aqarand'),
                'success_message' => __('Marketing label saved successfully.', 'aqarand'),
                'delete_success'  => __('Marketing label deleted.', 'aqarand'),
                'delete_error'    => __('Failed to delete marketing label.', 'aqarand'),
            ],
        ]);
    }

    public function render_page() {
        if ($this->page_handler) {
            $this->page_handler->render_page();
        }
    }

    public function render_finishing_page() {
        if ($this->finishing_page_handler) {
            $this->finishing_page_handler->render_page();
        }
    }

    public function render_views_page() {
        if ($this->views_page_handler) {
            $this->views_page_handler->render_page();
        }
    }

    public function render_orientations_page() {
        if ($this->orientations_page_handler) {
            $this->orientations_page_handler->render_page();
        }
    }

    public function render_facades_page() {
        if ($this->facades_page_handler) {
            $this->facades_page_handler->render_page();
        }
    }

    public function render_marketing_orientation_page() {
        if ($this->marketing_page_handler) {
            $this->marketing_page_handler->render_page();
        }
    }

    private function boot_page_handler($args = []) {
        require_once __DIR__ . '/features-list-table.php';
        require_once __DIR__ . '/features-page.php';

        $handler = new Jawda_Project_Features_Page($args);
        $handler->handle_form_submission();
        $handler->list_table->process_bulk_action();
        $handler->list_table->prepare_items();

        return $handler;
    }
}

new Jawda_Project_Features_Admin();
