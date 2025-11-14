<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class Aqarand_Cities_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'City',
            'plural'   => 'Cities',
            'ajax'     => false
        ]);
    }
    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'name_ar'       => 'Name (Arabic)',
            'name_en'       => 'Name (English)',
            'governorate'   => 'Governorate',
            'latitude'      => 'Latitude',
            'longitude'     => 'Longitude',
            'date'          => 'Date',
        ];
    }
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'locations_cities';
        $gov_table = $wpdb->prefix . 'locations_governorates';
        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
        $orderby = isset($_GET['orderby']) ? esc_sql($_GET['orderby']) : 'id';
        $order = isset($_GET['order']) ? esc_sql($_GET['order']) : 'DESC';
        $offset = ($current_page - 1) * $per_page;
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, g.name_ar as gov_name_ar FROM $table_name c
                 LEFT JOIN $gov_table g ON c.governorate_id = g.id
                 ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }
    protected function get_sortable_columns() {
        return [
            'name_ar'     => ['name_ar', false],
            'name_en'     => ['name_en', false],
            'governorate' => ['governorate_id', false],
            'latitude'    => ['latitude', false],
            'longitude'   => ['longitude', false],
            'date'        => ['created_at', false],
        ];
    }
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name_ar':
            case 'name_en':
                return $item[$column_name];
            case 'governorate':
                return $item['gov_name_ar'];
            case 'date':
                return $item['created_at'];
            case 'latitude':
            case 'longitude':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }
    function column_name_ar($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id']),
        );
      return sprintf('%1$s %2$s', $item['name_ar'], $this->row_actions($actions) );
    }

    protected function get_bulk_actions() {
        return ['delete' => 'Delete'];
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : [];
             if (!is_array($ids)) {
                $ids = [$ids];
            }
            if (empty($ids)) return;
            $this->delete_cities($ids);
        }
    }

    public function render_page() {
        echo '<div class="wrap"><h2>Cities</h2>';
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            $this->render_edit_form();
        } else {
            $this->render_add_form();
            $this->prepare_items();
            $this->display();
        }
        echo '</div>';
    }

    private function render_add_form() {
        global $wpdb;
        $gov_table = $wpdb->prefix . 'locations_governorates';
        $governorates = $wpdb->get_results("SELECT * FROM $gov_table");
        ?>
        <div class="form-wrap">
            <h3>Add New City</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_city">
                <div class="form-field">
                    <label for="name_ar">Name (Arabic)</label>
                    <input type="text" name="name_ar" id="name_ar" required>
                </div>
                <div class="form-field">
                    <label for="name_en">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" required>
                </div>
                <div class="form-field">
                    <label for="governorate_id">Governorate</label>
                    <select name="governorate_id" id="governorate_id" required>
                        <?php foreach ($governorates as $gov): ?>
                            <option value="<?php echo $gov->id; ?>"><?php echo $gov->name_ar; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
                aqarand_locations_render_coordinate_fields([
                    'lat_id' => 'city_latitude_add',
                    'lng_id' => 'city_longitude_add',
                    'map_id' => 'city-map-add',
                ]);
                submit_button('Add City');
                ?>
            </form>
        </div>
        <?php
    }

    private function render_edit_form() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'locations_cities';
        $gov_table = $wpdb->prefix . 'locations_governorates';
        $id = (int)$_GET['id'];
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
        $governorates = $wpdb->get_results("SELECT * FROM $gov_table");
        ?>
        <div class="form-wrap">
            <h3>Edit City</h3>
            <form method="post">
                <input type="hidden" name="action" value="edit_city">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <div class="form-field">
                    <label for="name_ar">Name (Arabic)</label>
                    <input type="text" name="name_ar" id="name_ar" value="<?php echo esc_attr($item['name_ar']); ?>" required>
                </div>
                <div class="form-field">
                    <label for="name_en">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($item['name_en']); ?>" required>
                </div>
                <div class="form-field">
                    <label for="governorate_id">Governorate</label>
                    <select name="governorate_id" id="governorate_id" required>
                        <?php foreach ($governorates as $gov): ?>
                            <option value="<?php echo $gov->id; ?>" <?php selected($item['governorate_id'], $gov->id); ?>>
                                <?php echo $gov->name_ar; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
                aqarand_locations_render_coordinate_fields([
                    'lat_id'    => 'city_latitude_edit',
                    'lat_value' => isset($item['latitude']) ? $item['latitude'] : '',
                    'lng_id'    => 'city_longitude_edit',
                    'lng_value' => isset($item['longitude']) ? $item['longitude'] : '',
                    'map_id'    => 'city-map-edit',
                ]);
                submit_button('Update City');
                ?>
            </form>
        </div>
        <?php
    }

    public function handle_form_submission() {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_city':
                    $this->add_city();
                    break;
                case 'edit_city':
                    $this->edit_city();
                    break;
            }
        }
    }

    private function add_city() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'locations_cities';
        $name_ar = sanitize_text_field($_POST['name_ar']);
        $name_en = sanitize_text_field($_POST['name_en']);
        $governorate_id = (int)$_POST['governorate_id'];
        $latitude = aqarand_locations_normalize_coordinate($_POST['latitude'] ?? null);
        $longitude = aqarand_locations_normalize_coordinate($_POST['longitude'] ?? null);

        $wpdb->insert($table_name, [
            'name_ar'       => $name_ar,
            'name_en'       => $name_en,
            'governorate_id'=> $governorate_id,
            'latitude'      => $latitude,
            'longitude'     => $longitude,
        ]);
    }

    private function edit_city() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'locations_cities';
        $id = (int)$_POST['id'];
        $name_ar = sanitize_text_field($_POST['name_ar']);
        $name_en = sanitize_text_field($_POST['name_en']);
        $governorate_id = (int)$_POST['governorate_id'];
        $latitude = aqarand_locations_normalize_coordinate($_POST['latitude'] ?? null);
        $longitude = aqarand_locations_normalize_coordinate($_POST['longitude'] ?? null);

        $wpdb->update(
            $table_name,
            [
                'name_ar'       => $name_ar,
                'name_en'       => $name_en,
                'governorate_id'=> $governorate_id,
                'latitude'      => $latitude,
                'longitude'     => $longitude,
            ],
            ['id' => $id]
        );
    }

    private function delete_cities($ids) {
        global $wpdb;
        $city_table = $wpdb->prefix . 'locations_cities';
        $district_table = $wpdb->prefix . 'locations_districts';
        $ids_format = implode(',', array_fill(0, count($ids), '%d'));

        $wpdb->query($wpdb->prepare("DELETE FROM $district_table WHERE city_id IN ($ids_format)", $ids));
        $wpdb->query($wpdb->prepare("DELETE FROM $city_table WHERE id IN ($ids_format)", $ids));
    }
}
