<?php
/**
 * Page handler for Main Categories CRUD.
 *
 * @package Aqarand
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Jawda_Main_Categories_Page {

    /**
     * The list table instance.
     * @var Jawda_Main_Categories_List_Table
     */
    public $list_table;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->list_table = new Jawda_Main_Categories_List_Table();
    }

    /**
     * Handles the display of the page, routing between list and form views.
     */
    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if (($action === 'edit' && $id) || $action === 'add') {
            $this->render_form_page($id);
        } else {
            $this->render_list_page();
        }
    }

    /**
     * Renders the list table page.
     */
    private function render_list_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Main Categories', 'aqarand'); ?></h1>
            <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&action=add" class="page-title-action"><?php _e('Add New', 'aqarand'); ?></a>

            <form method="post">
                <?php
                $this->list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renders the add/edit form page.
     *
     * @param int|null $id The ID of the item being edited, or null for a new item.
     */
    private function render_form_page($id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'property_categories';
        $item = null;
        $name_en = '';
        $name_ar = '';

        if ($id) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
            if ($item) {
                $name_en = $item['name_en'];
                $name_ar = $item['name_ar'];
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo $id ? __('Edit Main Category', 'aqarand') : __('Add New Main Category', 'aqarand'); ?></h1>

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <input type="hidden" name="action" value="save" />
                <?php if ($id) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>" />
                <?php endif; ?>
                <?php wp_nonce_field('jawda_save_main_category', 'jawda_main_category_nonce'); ?>

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
                    </tbody>
                </table>

                <?php submit_button($id ? __('Update', 'aqarand') : __('Add New Main Category', 'aqarand')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handles the form submission for both add and edit actions.
     */
    public function handle_form_submission() {
        if (isset($_POST['action']) && $_POST['action'] === 'save') {

            if (!isset($_POST['jawda_main_category_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jawda_main_category_nonce'])), 'jawda_save_main_category')) {
                wp_die(__('Security check failed.', 'aqarand'));
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'property_categories';
            $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

            $name_en = isset($_POST['name_en']) ? sanitize_text_field(wp_unslash($_POST['name_en'])) : '';
            $name_ar = isset($_POST['name_ar']) ? sanitize_text_field(wp_unslash($_POST['name_ar'])) : '';

            if (empty($name_en) || empty($name_ar)) {
                // You might want to add an admin notice here
                return;
            }

            $data = ['name_en' => $name_en, 'name_ar' => $name_ar];
            $format = ['%s', '%s'];

            if ($id) { // Update
                $wpdb->update($table_name, $data, ['id' => $id], $format, ['%d']);
            } else { // Insert
                $wpdb->insert($table_name, $data, $format);
            }

            $redirect_url = remove_query_arg(['action', 'id', '_wpnonce']);
            wp_redirect($redirect_url);
            exit;
        }
    }
}
