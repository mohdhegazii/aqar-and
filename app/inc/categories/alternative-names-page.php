<?php
/**
 * Page handler for Alternative Names CRUD.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Jawda_Alternative_Names_Page {

    public $list_table;

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        $this->list_table = new Jawda_Alternative_Names_List_Table();
    }

    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if (($action === 'edit' && $id) || $action === 'add') {
            $this->render_form_page($id);
        } else {
            $this->render_list_page();
        }
    }

    private function render_list_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Alternative Names', 'aqarand'); ?></h1>
            <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&action=add" class="page-title-action"><?php _e('Add New', 'aqarand'); ?></a>
            <form method="post">
                <?php $this->list_table->display(); ?>
            </form>
        </div>
        <?php
    }

    private function render_form_page($id = 0) {
        global $wpdb;
        $alt_names_table = $wpdb->prefix . 'property_type_alt_names';
        $relationships_table = $wpdb->prefix . 'project_alt_name_relationships';

        $item = null;
        $alt_name_en = '';
        $alt_name_ar = '';
        $property_type_id = 0;
        $selected_project_ids = [];

        if ($id) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $alt_names_table WHERE id = %d", $id), ARRAY_A);
            if ($item) {
                $alt_name_en = $item['alt_name_en'];
                $alt_name_ar = $item['alt_name_ar'];
                $property_type_id = $item['property_type_id'];
                $selected_project_ids = $wpdb->get_col($wpdb->prepare("SELECT project_id FROM $relationships_table WHERE alt_name_id = %d", $id));
            }
        }

        $property_types = $wpdb->get_results("SELECT id, name_en FROM {$wpdb->prefix}property_types ORDER BY name_en ASC");

        // Get all projects
        $all_projects = get_posts([
            'post_type' => 'projects',
            'post_status' => ['publish', 'draft'],
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ?>
        <div class="wrap">
            <h1><?php echo $id ? __('Edit Alternative Name', 'aqarand') : __('Add New Alternative Name', 'aqarand'); ?></h1>

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <input type="hidden" name="action" value="save" />
                <?php if ($id) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>" />
                <?php endif; ?>
                <?php wp_nonce_field('jawda_save_alt_name', 'jawda_alt_name_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="alt_name_en"><?php _e('Alternative Name (English)', 'aqarand'); ?></label></th>
                            <td><input name="alt_name_en" id="alt_name_en" type="text" value="<?php echo esc_attr($alt_name_en); ?>" required /></td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="alt_name_ar"><?php _e('Alternative Name (Arabic)', 'aqarand'); ?></label></th>
                            <td><input name="alt_name_ar" id="alt_name_ar" type="text" value="<?php echo esc_attr($alt_name_ar); ?>" required /></td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="property_type_id"><?php _e('Base Property Type', 'aqarand'); ?></label></th>
                            <td>
                                <select name="property_type_id" id="property_type_id" class="jawda-searchable-select" style="width: 50%;" required>
                                    <option value=""><?php _e('Select a Property Type', 'aqarand'); ?></option>
                                    <?php foreach ($property_types as $type) : ?>
                                        <option value="<?php echo esc_attr($type->id); ?>" <?php selected($property_type_id, $type->id); ?>><?php echo esc_html($type->name_en); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row"><label for="project_id"><?php _e('Associated Project', 'aqarand'); ?></label></th>
                            <td>
                                <select name="project_id" id="project_id" class="jawda-searchable-select" style="width: 50%;">
                                    <option value=""><?php _e('Select a Project', 'aqarand'); ?></option>
                                    <?php foreach ($all_projects as $project) : ?>
                                        <option value="<?php echo esc_attr($project->ID); ?>" <?php selected(isset($selected_project_ids[0]) ? $selected_project_ids[0] : 0, $project->ID); ?>>
                                            <?php echo esc_html($project->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($id ? __('Update', 'aqarand') : __('Add New Alternative Name', 'aqarand')); ?>
            </form>
        </div>
        <?php
    }

    public function handle_form_submission() {
        if (isset($_POST['action']) && $_POST['action'] === 'save' && isset($_POST['page']) && $_POST['page'] === 'jawda-alternative-names') {

            if (!isset($_POST['jawda_alt_name_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jawda_alt_name_nonce'])), 'jawda_save_alt_name')) {
                wp_die(__('Security check failed.', 'aqarand'));
            }

            global $wpdb;
            $alt_names_table = $wpdb->prefix . 'property_type_alt_names';
            $relationships_table = $wpdb->prefix . 'project_alt_name_relationships';

            $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

            $alt_name_en = isset($_POST['alt_name_en']) ? sanitize_text_field(wp_unslash($_POST['alt_name_en'])) : '';
            $alt_name_ar = isset($_POST['alt_name_ar']) ? sanitize_text_field(wp_unslash($_POST['alt_name_ar'])) : '';
            $property_type_id = isset($_POST['property_type_id']) ? absint($_POST['property_type_id']) : 0;
            $project_id = isset($_POST['project_id']) ? absint($_POST['project_id']) : 0;

            if (empty($alt_name_en) || empty($alt_name_ar) || empty($property_type_id) || empty($project_id)) {
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('All fields are required, including the associated project.', 'aqarand'); ?></p>
                    </div>
                    <?php
                });
                return;
            }

            $data = [
                'alt_name_en' => $alt_name_en,
                'alt_name_ar' => $alt_name_ar,
                'property_type_id' => $property_type_id,
            ];
            $format = ['%s', '%s', '%d'];

            if ($id) {
                $wpdb->update($alt_names_table, $data, ['id' => $id], $format, ['%d']);
            } else {
                $wpdb->insert($alt_names_table, $data, $format);
                $id = $wpdb->insert_id;
            }

            // Handle relationships
            if ($id) {
                // Delete old relationships
                $wpdb->delete($relationships_table, ['alt_name_id' => $id], ['%d']);

                // Insert new relationship
                if (!empty($project_id)) {
                    $wpdb->insert($relationships_table, [
                        'alt_name_id' => $id,
                        'project_id'  => $project_id,
                    ], ['%d', '%d']);
                }
            }

            $redirect_url = admin_url('admin.php?page=jawda-alternative-names');
            wp_redirect($redirect_url);
            exit;
        }
    }

    public function enqueue_assets() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'jawda-categories-main_page_jawda-alternative-names' && (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit']))) {
            wp_enqueue_script('jawda-categories-admin', get_template_directory_uri() . '/app/inc/categories/js/admin.js', ['jquery', 'select2'], '1.3', true);
        }
    }
}
