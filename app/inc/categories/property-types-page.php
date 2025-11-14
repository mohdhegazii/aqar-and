<?php
/**
 * Page handler for Property Types CRUD.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Jawda_Property_Types_Page {

    public $list_table;

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'start_session'], 1);
        add_action('admin_notices', [$this, 'display_notices']);
        $this->list_table = new Jawda_Property_Types_List_Table();
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function display_notices() {
        if (isset($_SESSION['jawda_admin_notice'])) {
            $notice = $_SESSION['jawda_admin_notice'];
            printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
            unset($_SESSION['jawda_admin_notice']);
        }
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
            <h1 class="wp-heading-inline"><?php _e('Property Types', 'aqarand'); ?></h1>
            <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&action=add" class="page-title-action"><?php _e('Add New', 'aqarand'); ?></a>
            <form method="post">
                <?php $this->list_table->display(); ?>
            </form>
        </div>
        <?php
    }

    private function render_form_page($id = null) {
        global $wpdb;
        $types_table = $wpdb->prefix . 'property_types';
        $item = null;
        $name_en = '';
        $name_ar = '';
        $image_id = 0;
        $selected_categories = [];

        if ($id) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $types_table WHERE id = %d", $id), ARRAY_A);
            if ($item) {
                $name_en = $item['name_en'];
                $name_ar = $item['name_ar'];
                $image_id = $item['image_id'];

                // Get associated categories
                $relationships_table = $wpdb->prefix . 'property_type_category_relationships';
                $results = $wpdb->get_results($wpdb->prepare("SELECT category_id FROM $relationships_table WHERE property_type_id = %d", $id));
                $selected_categories = wp_list_pluck($results, 'category_id');
            }
        }

        $cats_table = $wpdb->prefix . 'property_categories';
        $name_column = aqarand_is_arabic_locale() ? 'name_ar' : 'name_en';
        $categories = $wpdb->get_results("SELECT id, {$name_column} as name FROM {$cats_table} ORDER BY {$name_column} ASC");

        ?>
        <div class="wrap">
            <h1><?php echo $id ? __('Edit Property Type', 'aqarand') : __('Add New Property Type', 'aqarand'); ?></h1>

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <input type="hidden" name="action" value="save" />
                <?php if ($id) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>" />
                <?php endif; ?>
                <?php wp_nonce_field('jawda_save_property_type', 'jawda_property_type_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="name_en"><?php _e('Name (English)', 'aqarand'); ?></label></th>
                            <td><input name="name_en" id="name_en" type="text" value="<?php echo esc_attr($name_en); ?>" required /></td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="name_ar"><?php _e('Name (Arabic)', 'aqarand'); ?></label></th>
                            <td><input name="name_ar" id="name_ar" type="text" value="<?php echo esc_attr($name_ar); ?>" required /></td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="category_ids"><?php _e('Main Categories', 'aqarand'); ?></label></th>
                            <td>
                                <select name="category_ids[]" id="category_ids" multiple="multiple" class="select2-multiple" style="width: 300px;" required>
                                    <?php foreach ($categories as $cat) : ?>
                                        <option value="<?php echo esc_attr($cat->id); ?>" <?php echo in_array($cat->id, $selected_categories) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($cat->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row"><label for="image_id"><?php _e('Image', 'aqarand'); ?></label></th>
                            <td>
                                <div class="image-uploader-wrapper">
                                    <input type="hidden" name="image_id" id="image_id" value="<?php echo esc_attr($image_id); ?>" />
                                    <button type="button" class="button button-secondary" id="upload_image_button"><?php _e('Upload Image', 'aqarand'); ?></button>
                                    <div id="image_preview_wrapper" style="margin-top: 10px;">
                                        <?php if ($image_id) : ?>
                                            <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                                            <a href="#" id="remove_image_button" style="display:inline-block; margin-left:10px;"><?php _e('Remove Image', 'aqarand'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($id ? __('Update', 'aqarand') : __('Add New Property Type', 'aqarand')); ?>
            </form>
        </div>
        <?php
    }

    public function handle_form_submission() {
        if (isset($_POST['action']) && $_POST['action'] === 'save' && isset($_POST['page']) && $_POST['page'] === 'jawda-property-types') {

            if (!isset($_POST['jawda_property_type_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jawda_property_type_nonce'])), 'jawda_save_property_type')) {
                wp_die(__('Security check failed.', 'aqarand'));
            }

            global $wpdb;
            $types_table = $wpdb->prefix . 'property_types';
            $relationships_table = $wpdb->prefix . 'property_type_category_relationships';
            $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $redirect_url = admin_url('admin.php?page=jawda-property-types&action=' . ($id ? 'edit&id=' . $id : 'add'));

            $name_en = isset($_POST['name_en']) ? sanitize_text_field(wp_unslash($_POST['name_en'])) : '';
            $name_ar = isset($_POST['name_ar']) ? sanitize_text_field(wp_unslash($_POST['name_ar'])) : '';
            $category_ids = isset($_POST['category_ids']) ? array_map('absint', $_POST['category_ids']) : [];
            $image_id = isset($_POST['image_id']) ? absint($_POST['image_id']) : 0;

            if (empty($name_en) || empty($name_ar) || empty($category_ids)) {
                $_SESSION['jawda_admin_notice'] = [
                    'type' => 'error',
                    'message' => __('All fields are required. Please select at least one category.', 'aqarand')
                ];
                wp_redirect($redirect_url);
                exit;
            }

            $data = [
                'name_en' => $name_en,
                'name_ar' => $name_ar,
                'image_id' => $image_id
            ];
            $format = ['%s', '%s', '%d'];

            $wpdb->hide_errors();

            if ($id) { // Update existing item
                $result = $wpdb->update($types_table, $data, ['id' => $id], $format, ['%d']);
            } else { // Insert new item
                $result = $wpdb->insert($types_table, $data, $format);
                if ($result) {
                    $id = $wpdb->insert_id;
                }
            }

            if ($result === false) {
                 $_SESSION['jawda_admin_notice'] = [
                    'type' => 'error',
                    'message' => __('Database error:') . ' ' . $wpdb->last_error
                ];
                wp_redirect($redirect_url);
                exit;
            }

            if ($id) {
                // Delete old relationships
                $wpdb->delete($relationships_table, ['property_type_id' => $id], ['%d']);

                // Insert new relationships
                foreach ($category_ids as $category_id) {
                    $wpdb->insert(
                        $relationships_table,
                        [
                            'property_type_id' => $id,
                            'category_id' => $category_id
                        ],
                        ['%d', '%d']
                    );
                }
                 $_SESSION['jawda_admin_notice'] = [
                    'type' => 'success',
                    'message' => __('Property Type saved successfully.', 'aqarand')
                ];
            }

            wp_redirect(admin_url('admin.php?page=jawda-property-types'));
            exit;
        }
    }

    public function enqueue_assets() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'jawda-categories-main_page_jawda-property-types' && (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit']))) {
            // Enqueue media uploader scripts
            wp_enqueue_media();

            // Enqueue our custom script for Select2 and media uploader
            wp_enqueue_script(
                'jawda-property-types-admin',
                get_template_directory_uri() . '/app/inc/categories/js/property-types-admin.js',
                ['jquery', 'carbon-fields-core'], // Carbon Fields core script will load Select2
                '1.1',
                true
            );

            // This inline script is for the media uploader only now
            wp_add_inline_script('jawda-property-types-admin', "
                jQuery(document).ready(function($){
                    var frame;
                    $('#upload_image_button').on('click', function(e) {
                        e.preventDefault();
                        if (frame) {
                            frame.open();
                            return;
                        }
                        frame = wp.media({
                            title: 'Select or Upload Media',
                            button: { text: 'Use this media' },
                            multiple: false
                        });
                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            $('#image_id').val(attachment.id);
                            $('#image_preview_wrapper').html('<img src=\"' + attachment.sizes.thumbnail.url + '\" /><a href=\"#\" id=\"remove_image_button\" style=\"display:inline-block; margin-left:10px;\">Remove Image</a>');
                        });
                        frame.open();
                    });
                    $(document).on('click', '#remove_image_button', function(e){
                        e.preventDefault();
                        $('#image_id').val('');
                        $('#image_preview_wrapper').html('');
                    });
                });
            ");
        }
    }
}
