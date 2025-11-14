<?php

global $jawda_leads_db_version;
$jawda_leads_db_version = '1.3'; // Incremented version to handle schema change

/**
 * register_activation_hook implementation
 *
 * will be called when user activates plugin first time
 * must create needed database tables
 */
function jawda_leads_install()
{
    global $wpdb;
    global $jawda_leads_db_version;

    $table_name = $wpdb->prefix . 'leadstable'; // do not forget about tables prefix

    // Initial schema with DATETIME
    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      name tinytext NOT NULL,
      email text NULL,
      phone tinytext NOT NULL,
      massege text NULL,
      packagename tinytext NULL,
      `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check for version update
    $installed_ver = get_option('jawda_leads_db_version');
    if ($installed_ver != $jawda_leads_db_version) {
        // On version update, modify the column to DATETIME if it's not already
        $wpdb->query("ALTER TABLE $table_name MODIFY `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        update_option('jawda_leads_db_version', $jawda_leads_db_version);
    }
}

add_action('after_switch_theme', 'jawda_leads_install');


/**
 * Trick to update plugin database, see docs
 */
function jawda_leads_update_db_check()
{
    global $jawda_leads_db_version;
    if (get_site_option('jawda_leads_db_version') != $jawda_leads_db_version) {
        jawda_leads_install();
    }
}

add_action('after_switch_theme', 'jawda_leads_update_db_check');


if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Jawda_leads_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'lead',
            'plural' => 'leads',
        ));
    }

    /**
     * [REQUIRED] this is a default column renderer
     */
    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'date':
                $date_obj = new DateTime($item[$column_name]);
                return $date_obj->format('M j, Y') . '<br>' . $date_obj->format('g:i A');
            case 'packagename':
                $full_text = $item[$column_name];
                $url_part = '';
                $title_part = $full_text;

                // Regex to find a URL at the end of the string
                if (preg_match('/(https?:\/\/[^\s]+)$/', $full_text, $matches)) {
                    $url_part = $matches[0];
                    $title_part = trim(str_replace($url_part, '', $full_text));
                }

                if (!empty($url_part)) {
                    return esc_html($title_part) . '<br><a href="' . esc_url($url_part) . '" target="_blank">' . esc_html($url_part) . '</a>';
                }
                return esc_html($full_text); // Fallback if no URL is found
            default:
                return esc_html($item[$column_name]);
        }
    }


    function column_name($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=leads_form&id=%s">%s</a>', $item['id'], __('Edit')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete')),
        );

        return sprintf('%s %s',
            esc_html($item['name']),
            $this->row_actions($actions)
        );
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'name' => __('Name'),
            'date' => __('Date & Time'), // Updated column header
            'email' => __('E-Mail'),
            'phone' => __('phone'),
            'massege' => __('massege'),
            'packagename' => __('Package Name'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', false),
            'date' => array('date', true), // Default sort by date
            'email' => array('email', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadstable';

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) {
                $ids = array_map('intval', $ids);
                if (!empty($ids)) {
                    $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
                    $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN($ids_placeholder)", $ids));
                }
            } elseif (is_numeric($_REQUEST['id'])) {
                $id = (int) $_REQUEST['id'];
                $wpdb->delete($table_name, array('id' => $id), array('%d'));
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadstable';

        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = $this->get_pagenum();
        $offset = ($paged - 1) * $per_page;

        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'date';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}

function jawda_leads_admin_menu()
{
    add_menu_page(__('Leads'), __('Leads'), 'activate_plugins', 'leads', 'jawda_leads_leads_page_handler');
    add_submenu_page('leads', __('Leads'), __('Leads'), 'activate_plugins', 'leads', 'jawda_leads_leads_page_handler');
    add_submenu_page('leads', __('Add new'), __('Add new'), 'activate_plugins', 'leads_form', 'jawda_leads_leads_form_page_handler');
}

add_action('admin_menu', 'jawda_leads_admin_menu');

function jawda_leads_leads_page_handler()
{
    global $wpdb;

    $table = new Jawda_leads_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action() && !empty($_REQUEST['id'])) {
        $count = is_array($_REQUEST['id']) ? count($_REQUEST['id']) : 1;
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d'), $count) . '</p></div>';
    }
    ?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Jawda Leads')?></h1>
    <a class="page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=leads_form');?>"><?php _e('Add new')?></a>
    <div class="alignright">
      <a class="page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=leads_download');?>"><?php _e('Download My Leads')?></a>
    </div>
    <hr class="wp-header-end">

    <?php echo $message; ?>

    <form id="leads-table" method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>"/>
        <?php $table->display() ?>
    </form>

</div>
<?php
}

function jawda_leads_leads_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'leadstable';

    $message = '';
    $notice = '';

    $default = array('id' => 0,'name' => '','email' => '','phone' => null,'massege' => null,'packagename' => null);

    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        $item = shortcode_atts($default, $_REQUEST);

        if ($item['id'] == 0) {
            $result = $wpdb->insert($table_name, $item);
            $item['id'] = $wpdb->insert_id;
            if ($result) {
                $message = __('Item was successfully saved');
            } else {
                $notice = __('There was an error while saving item');
            }
        } else {
            $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
            if ($result) {
                $message = __('Item was successfully updated');
            } else {
                $notice = __('There was an error while updating item');
            }
        }
    }
    else {
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", (int) $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item = $default;
                $notice = __('Item not found');
            }
        }
    }

    add_meta_box('leads_form_meta_box', 'lead data', 'jawda_leads_leads_form_meta_box_handler', 'lead', 'normal', 'default');
    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Add New lead')?> </h2>
    <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=leads');?>"><?php _e('back to list')?></a>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>
        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_meta_boxes('lead', 'normal', $item); ?>
                    <input type="submit" value="<?php _e('Save')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>
<?php
}

function jawda_leads_leads_form_meta_box_handler($item)
{
    ?>
<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="name"><?php _e('Name')?></label>
        </th>
        <td>
    <input id="name" name="name" type="text" style="width: 95%" value="<?php echo esc_attr($item['name']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your name')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="email"><?php _e('E-Mail')?></label>
        </th>
        <td>
            <input id="email" name="email" type="email" style="width: 95%" value="<?php echo esc_attr($item['email']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your E-Mail')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="phone"><?php _e('phone')?></label>
        </th>
        <td>
            <input id="phone" name="phone" type="text" style="width: 95%" value="<?php echo esc_attr($item['phone']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your phone')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="massege"><?php _e('massege')?></label>
        </th>
        <td>
            <input id="massege" name="massege" type="text" style="width: 95%" value="<?php echo esc_attr($item['massege']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your massege')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="packagename"><?php _e('packagename')?></label>
        </th>
        <td>
            <input id="packagename" name="packagename" type="text" style="width: 95%" value="<?php echo esc_attr($item['packagename']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your packagename')?>" required>
        </td>
    </tr>
    </tbody>
</table>
<?php
}