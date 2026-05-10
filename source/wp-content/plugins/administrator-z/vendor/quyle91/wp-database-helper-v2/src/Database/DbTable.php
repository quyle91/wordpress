<?php

namespace WpDatabaseHelperV2\Database;

class DbTable {
    protected string $id;
    public function getId() {
        return $this->id;
    }


    public function __construct() {
        $this->id = 'id_' . rand();
        $this->version = $this->getVersion();
    }

    private $version;
    private function getVersion() {
        // __DIR__ = .../src/Database
        $composerFile = dirname(__DIR__, 2) . '/composer.json'; // đi lên 2 cấp để tới root của package

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            return $composerData['version'] ?? '0.0.0';
        }

        return '0.0.0';
    }

    public static function make(): self {
        return new self();
    }

    protected string $name;
    public function name(string $name): self {
        global $wpdb;
        $this->name = $wpdb->prefix . $name;

        // Lưu vào registry ngay khi name được set
        self::$registry[$this->name] = $this;

        return $this;
    }

    protected static array $registry = [];
    public static function getByName(string $name): self {
        global $wpdb;
        // Nếu tên đã có prefix rồi thì không thêm nữa
        if (strpos($name, $wpdb->prefix) !== 0) {
            $name = $wpdb->prefix . $name;
        }

        if (isset(self::$registry[$name])) {
            return self::$registry[$name];
        }

        $instance = new self();
        $instance->name = $name;

        return $instance;
    }

    protected string $title;
    public function title(string $title): self {
        $this->title = $title;
        return $this;
    }

    protected array $fields = [];
    public function fields(array $fields): self {
        $this->fields = $fields;

        // maybe ad id column
        $this->maybeAddIdColumn();

        return $this;
    }

    public function create(): self {
        // skip if empty name or fields
        if (empty($this->name)) return $this;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $columns_sql = [];
        $primary_keys = [];

        // echo '<pre>'; print_r($this); echo '</pre>'; 

        foreach ($this->fields as $field) {
            if (!$field instanceof DbColumn) continue;

            if ($field->isPrimary()) {
                $primary_keys[] = "`{$field->getName()}`";
            }

            $columns_sql[] = $field->build()->getSql();
        }

        // Thêm primary key nếu có
        if ($primary_keys) {
            $columns_sql[] = 'PRIMARY KEY (' . implode(', ', $primary_keys) . ')';
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$this->name} (\n"
            . implode(",\n", $columns_sql)
            . "\n) $charset_collate;";

        // error_log($sql);

        dbDelta($sql);
        return $this;
    }

    public function drop(bool $force = true): bool {
        global $wpdb;

        // Nếu không có tên bảng -> thoát
        if (empty($this->name)) {
            return false;
        }

        // Nếu force = false → kiểm tra trước khi xóa
        if (!$force && $wpdb->get_var("SHOW TABLES LIKE '{$this->name}'") !== $this->name) {
            return false; // bảng không tồn tại
        }

        $sql = "DROP TABLE IF EXISTS {$this->name};";
        $wpdb->query($sql);

        // Nếu có lỗi, trả false
        return empty($wpdb->last_error);
    }

    public function empty(): bool {
        global $wpdb;

        // Nếu chưa có table name thì thoát
        if (empty($this->name)) {
            return false;
        }

        // Kiểm tra bảng có tồn tại không
        $exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->name
        ));

        if ($exists !== $this->name) {
            return false; // bảng chưa tồn tại
        }

        // Xóa toàn bộ dữ liệu nhưng giữ bảng
        $sql = "TRUNCATE TABLE {$this->name};";
        $wpdb->query($sql);

        return empty($wpdb->last_error);
    }

    public function exists(): bool {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $this->name)
        ) === $this->name;
    }

    public function hasColumn(string $column): bool {
        $return = false;
        foreach ((array)$this->fields as $key => $dbColumn) {
            if (
                $dbColumn instanceof DbColumn and
                $column == $dbColumn->getName()
            ) {
                return true;
            }
        }
        return $return;
    }

    public function hasColumnId(): bool {
        $return = false;
        foreach ((array)$this->fields as $key => $dbColumn) {
            if (
                $dbColumn instanceof DbColumn and
                'id' == $dbColumn->getName() and
                $dbColumn->isPrimary() and
                $dbColumn->isAutoIncrement()
            ) {
                return true;
            }
        }
        return $return;
    }

    public function registerAdminPage(): self {
        // skip if empty name or fields
        if (empty($this->name) || empty($this->fields)) return $this;

        // enqueue
        \WpDatabaseHelperV2\Services\Assets::get_instance();

        // admin menu
        add_action('admin_menu', function () {
            add_management_page(
                $this->title ?? $this->name,
                $this->title ?? $this->name,
                'manage_options',
                $this->name,
                [$this, 'renderAdminPage']
            );
        });

        // quick save
        add_action('wp_ajax_wpdh_AjaxUpdateRecord', [$this, 'handleAjaxUpdateRecord']);

        //
        return $this;
    }

    public function getDbColumns(): array {
        global $wpdb;

        // Nếu chưa có table name
        if (empty($this->name)) {
            return [];
        }

        $results = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->name}",
            ARRAY_A
        );

        $columns = [];

        foreach ($results as $row) {
            $columns[$row['Field']] = [
                'name'     => $row['Field'],
                'type'     => $row['Type'],
                'nullable' => $row['Null'] === 'YES',
                'key'      => $row['Key'],
                'default'  => $row['Default'],
                'extra'    => $row['Extra'],
            ];
        }

        return $columns;
    }

    // get runtime columns
    public function getRuntimeColumns(): array {
        $dbColumns = $this->getDbColumns();

        // Declared fields (giữ nguyên)
        $declared = [];

        foreach ((array) $this->fields as $field) {
            if (!$field instanceof DbColumn) {
                continue;
            }

            $declared[$field->getName()] = $field;
        }

        $runtime = [];

        foreach ($dbColumns as $name => $meta) {

            // Column được khai báo bằng code
            if (isset($declared[$name])) {
                $runtime[$name] = [
                    'source' => 'declared',
                    'column' => $declared[$name],
                    'db'     => $meta,
                ];
                continue;
            }

            // Column add tay trong DB
            $runtime[$name] = [
                'source' => 'database',
                'column' => DbColumn::getByName($name), // chỉ dùng name
                'db'     => $meta,
            ];
        }

        return $runtime;
    }

    public function renderAdminPage() {
        // Nếu bảng chưa tồn tại
        if (!$this->exists()) {
            echo '<div class="notice notice-error"><p>Table <strong>' . esc_html($this->name) . '</strong> does not exist.</p></div>';
            return;
        }

        global $wpdb;
        $table = esc_sql($this->name);

        // Filters
        $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 50;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        $order_by = $_GET['order_by'] ?? 'id';
        $order_dir = strtoupper($_GET['order_dir'] ?? 'DESC');

        // Handle delete action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['wpdh_bulk_action'] ?? '') === 'delete') {
            $ids_csv = $_POST['wpdh_selected_ids'] ?? '';
            $ids_array = array_filter(array_map('intval', explode(',', $ids_csv)));

            if (!empty($ids_array)) {
                $placeholders = implode(',', array_fill(0, count($ids_array), '%d'));
                $sql = "DELETE FROM {$table} WHERE id IN ($placeholders)";
                $wpdb->query($wpdb->prepare($sql, ...$ids_array));

                echo '<div class="updated"><p>Deleted ' . count($ids_array) . ' record(s).</p></div>';
                echo '<meta http-equiv="refresh" content="0;url=?page=' . esc_attr($this->name) . '">';
            }
        }

        // Handle drop table action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['wpdh_action'] ?? '') === 'drop') {
            if (!empty($this->name)) {
                $this->drop(true);
                echo '<div class="updated"><p>Table <strong>' . esc_html($this->name) . '</strong> dropped successfully.</p></div>';
            }
            echo '<meta http-equiv="refresh" content="1;url=?page=' . esc_attr($this->name) . '">';
            return;
        }

        // Handle create record action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['wpdh_action'] ?? '') === 'create') {
            $data = [];

            foreach ((array) $this->fields as $field) {
                if (!$field instanceof DbColumn) {
                    continue;
                }

                $col = $field->getName();

                // Skip id auto increment
                if ($col === 'id') {
                    continue;
                }

                if (isset($_POST[$col])) {
                    $data[$col] = sanitize_text_field($_POST[$col]);
                }
            }

            if (!empty($data)) {
                $wpdb->insert($table, $data);
            }

            echo '<meta http-equiv="refresh" content="0;url=?page=' . esc_attr($this->name) . '">';
            return;
        }

        // Multi-column search
        $search_params = $_GET['s'] ?? [];
        $where_parts = [];
        $params = [];

        if (is_array($search_params)) {
            // foreach ($this->fields as $field) {
            //     $col = $field->getName();
            //     if (!empty($search_params[$col])) {
            //         $like = '%' . $wpdb->esc_like(trim($search_params[$col])) . '%';
            //         $where_parts[] = "`{$col}` LIKE %s";
            //         $params[] = $like;
            //     }
            // }
            foreach ($this->getDbColumns() as $col => $meta) {
                if (!empty($search_params[$col])) {
                    $like = '%' . $wpdb->esc_like(trim($search_params[$col])) . '%';
                    $where_parts[] = "`{$col}` LIKE %s";
                    $params[] = $like;
                }
            }
        }

        $search_logic = ($_GET['s_logic'] ?? 'OR') === 'AND' ? 'AND' : 'AND';
        $where = $where_parts ? 'WHERE ' . implode(" $search_logic ", $where_parts) : '';

        // Query records
        $sql = "SELECT * FROM {$table} {$where} ORDER BY {$order_by} {$order_dir} LIMIT %d OFFSET %d";
        $params_for_query = array_merge($params, [$per_page, $offset]);
        $sql = $wpdb->prepare($sql, ...$params_for_query);
        $records = $wpdb->get_results($sql);

        // Total records for pagination
        if ($where_parts) {
            $count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
            $total = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));
        } else {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }
        $total_pages = ceil($total / $per_page);

        // Build query string for pagination links (search + filter)
        $query_params = array_merge(
            ['s' => $search_params, 's_logic' => $_GET['s_logic'] ?? 'OR'],
            ['per_page' => $per_page, 'order_by' => $order_by, 'order_dir' => $order_dir]
        );
        $search_query = http_build_query($query_params);

        // 
        if ($where_parts) {
            $count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
            $total_items = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));
        } else {
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }

        // fields
        // $fields = $this->fields;
        // Runtime schema (DB là source of truth)
        $fields = $this->getRuntimeColumns();

        // Render view
        echo '<div class="wrap">';
        echo \WpDatabaseHelperV2\Services\Renderer::view('database/table-view', [
            'table_title' => $this->title,
            'table_name' => $this->name,
            'sql' => $sql,
            'fields' => $fields,
            'records' => $records,
            'total_items' => $total_items,
            'paged' => $paged,
            'total_pages' => $total_pages,
            'search' => $search_params,
            'search_query' => $search_query,
            'table_name' => $this->name,
            'show_drop_button' => ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1' || (($_SERVER['HTTP_HOST'] ?? '') || isset($_GET['debug'])) === 'localhost',
        ]);
        echo '</div>';

        echo \WpDatabaseHelperV2\Services\Renderer::view('version', ['version' => $this->version]);
    }

    public function handleAjaxUpdateRecord() {
        check_ajax_referer('wpdh_nonce', 'nonce');

        global $wpdb;

        $record_id = $_POST['record_id'] ?? 0;
        $column_name = sanitize_text_field($_POST['column_name'] ?? '');
        $table_name = sanitize_text_field($_POST['table_name'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');

        if (!is_numeric($record_id) || !$record_id || !$column_name || !$table_name) {
            wp_send_json_error(['message' => 'Invalid record ID or field name or table name']);
        }

        $table = \WpDatabaseHelperV2\Database\DbTable::getByName($table_name);
        // if (!$table->hasColumn($column_name)) {
        //     wp_send_json_error(['message' => "Field '$column_name' does not exist in table"]);
        // }
        $dbColumns = $table->getDbColumns();

        // Column phải tồn tại trong DB thật
        if (!isset($dbColumns[$column_name])) {
            wp_send_json_error([
                'message' => "Field '{$column_name}' does not exist in database"
            ]);
        }

        $record_id = intval($record_id);
        $table_name = esc_sql($table->name);

        // Update
        $updated = $wpdb->update(
            $table_name,
            [$column_name => $value],
            ['id' => $record_id],
            ['%s'],
            ['%d']
        );

        if ($updated === false) {
            wp_send_json_error(['message' => 'Database update failed']);
        }

        // Lấy lại giá trị thực từ DB
        $new_value = $wpdb->get_var(
            $wpdb->prepare("SELECT `$column_name` FROM `$table_name` WHERE `id` = %d", $record_id)
        );

        // Trả message phù hợp
        $msg = $updated === 0 ? 'No changes detected' : 'Saved!';

        wp_send_json_success([
            'message' => $msg,
            'value'   => $new_value,
        ]);
    }

    public function seedDemoData(int $count = 10): self {
        if (!$this->exists()) return $this;

        global $wpdb;

        if (empty($this->name) || empty($this->fields)) return $this;

        // ⚡ Kiểm tra nếu bảng đã có hơn 50 record thì bỏ qua
        $record_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->name}");
        if ($record_count > 50) {
            // error_log("Seed skipped for {$this->name} (already {$record_count} records)");
            return $this;
        }

        $insertable_cols = [];

        foreach ($this->fields as $field) {
            if (!($field instanceof DbColumn)) continue;
            if ($field->isAutoIncrement() || $field->isTimestamp()) continue;
            $insertable_cols[] = $field->getName();
        }

        if (empty($insertable_cols)) return $this;

        for ($i = 0; $i < $count; $i++) {
            $data = [];
            foreach ($insertable_cols as $col) {
                $rand = rand();

                if (str_contains($col, 'title')) {
                    $data[$col] = 'Demo title ' . $rand;
                } elseif (str_contains($col, 'post_id')) {
                    $data[$col] = rand(1, 100000);
                } elseif (str_contains($col, 'name')) {
                    $data[$col] = 'Name ' . rand(1, 100000);
                } elseif (str_contains($col, 'email')) {
                    $data[$col] = 'user' . $rand . '@example.com';
                } else {
                    $data[$col] = 'Value ' . $rand;
                }
            }

            $wpdb->insert($this->name, $data);
        }

        return $this;
    }

    public function maybeAddIdColumn() {
        if (!$this->hasColumnId()) {
            // $this->fields[] = new DbColumn('id', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            $dbColumn = \WpDatabaseHelperV2\Database\DbColumn::make();
            $dbColumn->name('id');
            $dbColumn->type('bigint');
            $dbColumn->notNull(true);
            $dbColumn->autoIncrement(true);
            $dbColumn->unsigned(true);
            // $dbColumn->default(null);
            $dbColumn->onUpdateCurrentTimestamp(false);
            $dbColumn->primary(true);

            // move field to first of fields array
            array_unshift($this->fields, $dbColumn);
        }
        return $this;
    }
}
