<?php

namespace WpDatabaseHelperV2\Meta;

use WpDatabaseHelperV2\Fields\WpField;
use WpDatabaseHelperV2\Fields\WpRepeater;

class WpMeta {
    protected string $id;
    public function getId() {
        return $this->id;
    }

    private $version;
    public static function getVersion() {
        // __DIR__ = .../src/Database
        $composerFile = dirname(__DIR__, 2) . '/composer.json'; // đi lên 2 cấp để tới root của package

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            return $composerData['version'] ?? '0.0.0';
        }

        return '0.0.0';
    }

    public function __construct() {
        $this->id = 'id_' . rand();
        $this->version = $this->getVersion();
    }

    public static function make(): self {
        \WpDatabaseHelperV2\Services\Assets::get_instance();
        return new self();
    }

    protected string $post_type = '';
    public function post_type(string $post_type): self {
        $this->post_type = $post_type;
        return $this;
    }
    public function getPostType(): string {
        return $this->post_type;
    }

    protected string $taxonomy = '';
    public function taxonomy(string $taxonomy): self {
        $this->taxonomy = $taxonomy;
        return $this;
    }
    public function getTaxonomy(): string {
        return $this->taxonomy;
    }

    protected string $label = '';
    public function label(string $label): self {
        $this->label = $label;
        // Lưu vào registry ngay khi name được set
        self::$registry[$this->label] = $this;
        return $this;
    }
    public function getLabel(): string {
        return $this->label;
    }

    protected static array $registry = [];
    public static function getByLabel(string $label): self {
        // Nếu đã có trong registry → trả về object cũ
        if (isset(self::$registry[$label])) {
            return self::$registry[$label];
        }

        // Nếu chưa có → tạo object mới chỉ với label
        $instance = new self();
        $instance->label = $label;

        return $instance;
    }

    protected array $fields = [];
    public function fields(array $fields): self {
        $this->fields = $fields;
        return $this;
    }

    protected static array $registered_boxes = [];

    public function register() {
        // Create a unique key for this registration to prevent duplicates
        $key = 'pt_' . $this->post_type . '_tax_' . $this->taxonomy . '_lbl_' . $this->label;
        if (isset(self::$registered_boxes[$key])) {
            return;
        }
        self::$registered_boxes[$key] = true;

        $this->setup_for_taxonomy();
        $this->setup_for_post_type();
        $this->setup_ajax();
    }

    function setup_for_taxonomy() {
        if (!$this->getTaxonomy()) {
            return;
        }

        $create_edited_term_func = function ($term_id, $tt_id, $taxonomy) {
            if ($taxonomy !== $this->taxonomy) {
                return;
            }

            foreach ($this->fields as $field) {

                $fieldName = $field->getName();
                $fieldValue = $_POST[$fieldName] ?? null;

                // không có trong POST thì bỏ qua (tránh ghi đè)
                if ($fieldValue === null) {
                    continue;
                }

                update_term_meta($term_id, $fieldName, $fieldValue);
            }
        };

        add_action('edited_term', $create_edited_term_func, 10, 3);
        add_action('create_term', $create_edited_term_func, 10, 3);

        add_action("{$this->taxonomy}_edit_form_fields", function ($term) {
            foreach ($this->fields as $field) {
                $value = get_term_meta($term->term_id, $field->getName(), true);
                $value = metadata_exists('term', $term->term_id, $field->getName())
                    ? $value
                    : false;

                echo '<tr class="form-field">';
                echo '<th scope="row">';
                echo '<label>' . esc_html($field->getLabel()) . '</label>';
                echo '</th>';
                echo '<td>';

                // giữ lại if else để dễ debug
                if ($field instanceof WpRepeater) {
                    echo $field
                        ->value($value)
                        ->namePrefix('') // level 1 then it's ''
                        ->parentRepeater(null)
                        ->render();
                } else if ($field instanceof WpField) {
                    echo $field
                        ->value($value)
                        ->namePrefix('') // level 1 then it's ''
                        ->render();
                }

                echo '</td>';
                echo '</tr>';
            }
        });

        add_filter("manage_edit-{$this->taxonomy}_columns", function ($columns) {
            foreach ($this->fields as $field) {
                if ($field->getAdminColumn()) {
                    $columns[$field->getName()] = $field->getLabel();
                }
            }

            return $columns;
        });

        add_filter("manage_{$this->taxonomy}_custom_column", function ($content, $column, $term_id) {
            foreach ($this->fields as $field) {
                if ($field->getName() === $column && method_exists($field, 'getAdminColumn') && $field->getAdminColumn()) {
                    $value = get_term_meta($term_id, $field->getName(), true);
                    $value = metadata_exists('term', $term_id, $field->getName()) ? $value : false;

                    echo '<div class="wpdh-admin-column-wrap">';

                    echo '<div class="wpdh-meta-value">';

                    $fieldToArray = $field->toArray();
                    $display_value = $this->displayValue($value, $fieldToArray);

                    echo wp_kses_post($display_value);
                    echo '</div>';

                    echo '<div class="wpdh-meta-form hidden" data-term-id="' . esc_attr($term_id) . '" data-field-name="' . esc_attr($field->getName()) . '" data-field-to-array="' . esc_attr(json_encode($field->toArray())) . '">';

                    // giữ lại if else để dễ debug
                    if ($field instanceof WpRepeater) {
                        echo $field
                            ->value($value)
                            ->label('')
                            ->namePrefix('') // level 1 then it's ''
                            ->parentRepeater(null)
                            ->render();
                    } else if ($field instanceof WpField) {
                        echo $field
                            ->value($value)
                            ->label('')
                            ->namePrefix('') // level 1 then it's ''
                            ->render();
                    }

                    echo '<div><small class="wpdh-saved-status"></small></div>';
                    echo '<button type="button" class="button wpdh-save-meta">Save</button>';
                    echo '</div>';

                    echo '</div>'; // .wpdh-admin-column-wrap
                }
            }

            return $content;
        }, 10, 3);
    }

    function setup_for_post_type() {
        if (!$this->getPostType()) {
            return;
        }

        // metabox
        add_action('add_meta_boxes', function () {
            // nếu field có method và cho phép hiển thị
            $hasVisibleField = false;
            foreach ($this->fields as $field) {
                if ($field->getShowInMetaBox()) {
                    $hasVisibleField = true;
                    break;
                }
            }
            if (!$hasVisibleField) {
                return;
            }

            add_meta_box(
                "wpdh_{$this->post_type}_" . sanitize_title($this->label),
                $this->label,
                function ($post) {
                    do_action('wpdh_meta_box_before', $post, $this);
                    echo '<div class="wpdh-metabox">';
                    foreach ($this->fields as $field) {

                        // nếu field không cho hiển thị trong metabox thì bỏ qua
                        if ($field->getShowInMetaBox() === false) {
                            continue;
                        }

                        // false: chưa được lưu
                        // '': đã được lưu và ko có giá trị
                        $dbValue = get_post_meta($post->ID, $field->getName(), true);
                        $dbValue = metadata_exists('post', $post->ID, $field->getName()) ? $dbValue : false;
                        // echo '<pre>'; print_r($dbValue); echo '</pre>';

                        // giữ lại if else để dễ debug
                        if ($field instanceof WpRepeater) {
                            echo $field
                                ->value($dbValue)
                                ->namePrefix('') // level 1 then it's ''
                                ->parentRepeater(null)
                                ->render();
                        } else if ($field instanceof WpField) {
                            echo $field
                                ->value($dbValue)
                                ->namePrefix('') // level 1 then it's ''
                                ->render();
                        }
                    }
                    wp_nonce_field('wpdh_save_meta', 'wpdh_nonce');
                    echo '</div>';
                    do_action('wpdh_meta_box_after', $post, $this);
                    echo \WpDatabaseHelperV2\Services\Renderer::view(
                        'version',
                        [
                            'version' => $this->version
                        ]
                    );
                },
                $this->post_type,
                'normal',
                'default'
            );
        });

        // save meta
        add_action('save_post', function ($post_id, $post) {

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            if (!isset($_POST['wpdh_nonce']) || !wp_verify_nonce($_POST['wpdh_nonce'], 'wpdh_save_meta')) {
                return;
            }

            // get all fields
            foreach ((array)$this->fields as $key => $field) {

                // nếu field không hiển thị trong metabox thì bỏ qua save
                if ($field->getShowInMetaBox() === false) {
                    continue;
                }

                $fieldName = $field->getName();

                // '': đã được lưu và ko có giá trị
                $fieldValue = '';
                if (isset($_POST[$fieldName])) {
                    $fieldValue = $_POST[$fieldName];
                }

                // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $log: ' . print_r($log, true));
                update_post_meta($post_id, $fieldName, $fieldValue);
            }
        }, 10, 2);

        // add columns
        $_filter_name = "manage_{$this->post_type}_posts_columns";
        if ($this->post_type == 'product') {
            $_filter_name = "manage_edit-{$this->post_type}_columns";
        }
        add_filter($_filter_name, function ($columns) {
            foreach ($this->fields as $field) {
                if (method_exists($field, 'getAdminColumn') && $field->getAdminColumn() && $field->getName()) {
                    $columns[$field->getName()] = $field->getLabel();
                }
            }
            return $columns;
        });

        // render column content
        $_filter_name = "manage_{$this->post_type}_posts_custom_column";
        add_action($_filter_name, function ($column, $post_id) {

            foreach ($this->fields as $field) {
                if ($field->getName() === $column && method_exists($field, 'getAdminColumn') && $field->getAdminColumn()) {
                    $value = get_post_meta($post_id, $field->getName(), true);
                    $value = metadata_exists('post', $post_id, $field->getName()) ? $value : false;

                    echo '<div class="wpdh-admin-column-wrap">';

                    echo '<div class="wpdh-meta-value">';

                    $fieldToArray = $field->toArray();
                    $display_value = $this->displayValue($value, $fieldToArray);

                    echo wp_kses_post($display_value);
                    echo '</div>';

                    echo '<div class="wpdh-meta-form hidden" data-post-id="' . esc_attr($post_id) . '" data-field-name="' . esc_attr($field->getName()) . '" data-field-to-array="' . esc_attr(json_encode($field->toArray())) . '">';

                    // giữ lại if else để dễ debug
                    if ($field instanceof WpRepeater) {
                        echo $field
                            ->value($value)
                            ->label('')
                            ->namePrefix('') // level 1 then it's ''
                            ->parentRepeater(null)
                            ->render();
                    } else if ($field instanceof WpField) {
                        echo $field
                            ->value($value)
                            ->label('')
                            ->namePrefix('') // level 1 then it's ''
                            ->removeAttribute('required')
                            ->render();
                    }

                    echo '<div><small class="wpdh-saved-status"></small></div>';
                    echo '<button type="button" class="button wpdh-save-meta">Save</button>';
                    echo '</div>';

                    echo '</div>'; // .wpdh-admin-column-wrap
                }
            }
        }, 10, 2);

        // sortable
        add_filter('manage_edit-' . $this->post_type . '_sortable_columns', function ($columns) {
            foreach ($this->fields as $field) {
                if ($field->getSortable()) {
                    $columns[$field->getName()] = $field->getName();
                }
            }
            return $columns;
        });

        // sort query
        add_action('pre_get_posts', function ($query) {

            // chỉ apply trong admin
            if (! is_admin()) {
                return;
            }

            // chỉ apply main query
            if (!$query->is_main_query()) {
                return;
            }

            // đúng post type
            if ($query->get('post_type') !== $this->post_type) {
                return;
            }

            $orderby = $query->get('orderby');
            if (!$orderby) {
                return;
            }

            //
            foreach ($this->fields as $field) {
                if ($field->getName() === $orderby) {
                    // set meta_key để sort
                    $query->set('meta_key', $orderby);

                    // check kind
                    if ($field->getKind() === 'number') {
                        $query->set('orderby', 'meta_value_num');
                    } else {
                        $query->set('orderby', 'meta_value');
                    }
                }
            }
        });
    }

    function setup_ajax() {
        add_action('wp_ajax_wpdh_save_meta', function () {
            // validate nonce
            $nonce = $_POST['nonce'] ?? '';
            if (empty($nonce) || !wp_verify_nonce($nonce, 'wpdh_nonce')) {
                wp_send_json_error([
                    'message' => 'Invalid nonce'
                ]);
                wp_die();
            }

            // validate post_id
            $post_id = intval($_POST['post_id'] ?? 0);
            $term_id = intval($_POST['term_id'] ?? 0);


            // validate field_name
            $field_name = sanitize_text_field($_POST['field_name'] ?? '');
            if (!$field_name) {
                wp_send_json_error([
                    'message' => 'Invalid field_name'
                ]);
                wp_die();
            }

            // get field_value
            $field_value = $_POST[$field_name] ?? '';

            // check if not changed
            $old_value = get_post_meta($post_id, $field_name, true);
            if ($field_value === $old_value) {
                wp_send_json_error([
                    'message' => 'Not changed'
                ]);
                wp_die();
            }

            // update post meta
            if ($post_id) {
                $updated = update_post_meta($post_id, $field_name, $field_value);
                if ($updated === false) {
                    wp_send_json_error([
                        'message' => 'Failed to update meta'
                    ]);
                    wp_die();
                }
                $fieldToArray = $_POST['fieldToArray'] ?? '';
                $fieldToArray = stripslashes($fieldToArray);
                $fieldToArray = json_decode($fieldToArray, true);
                $value = get_post_meta($post_id, $field_name, true);
                $display_value = $this->displayValue($value, $fieldToArray);
            }

            // update term meta
            if ($term_id) {
                $updated = update_term_meta($term_id, $field_name, $field_value);
                if ($updated === false) {
                    wp_send_json_error([
                        'message' => 'Failed to update meta'
                    ]);
                    wp_die();
                }
                $fieldToArray = $_POST['fieldToArray'] ?? '';
                $fieldToArray = stripslashes($fieldToArray);
                $fieldToArray = json_decode($fieldToArray, true);
                $value = get_term_meta($term_id, $field_name, true);
                $display_value = $this->displayValue($value, $fieldToArray);
            }

            // build message
            $msg = $updated === 0 ? 'No changes detected' : 'Saved successfully';

            wp_send_json_success([
                'message' => $msg,
                'field' => $field_name,
                'post_id' => $post_id,
                'value' => $display_value
            ]);

            wp_die();
        });
    }

    public function displayValue($value, $fieldToArray) {
        //
        $kind = $fieldToArray['kind'] ?? '';
        $type = $fieldToArray['type'] ?? '';
        $options = $fieldToArray['options'] ?? [];


        // default 
        $return = $value;
        if (is_array($value)) {
            $return = serialize($value);
        }

        // is array
        if (is_array($value)) {
            // check if array is one-dimensional
            $isOneDim = true;
            foreach ($value as $v) {
                if (is_array($v)) {
                    $isOneDim = false;
                    break;
                }
            }

            if ($isOneDim) {
                // implode for 1D array
                $return = implode(', ', $value);
            } else {
                // encode for multi-dimensional array
                $return = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
            return $return;
        }

        // wp_media
        if (
            in_array($kind, ['input']) and
            in_array($type, ['wp_media', 'wp_multiple_media']) and
            !empty($value)
        ) {
            $return .= "<div class='wpdh-media-preview'>";
            if ($type == 'wp_multiple_media') {
                $image_ids = unserialize($value);
            } else {
                $image_ids = [$value];
            }

            foreach ((array)($image_ids ?? []) as $id) {
                $url = wp_get_attachment_image_url($id, 'thumbnail');
                if ($url) {
                    $return .= "<img src='{$url}' data-id='{$id}' class='wpdh-media-thumb'>";
                }
            }

            $return .= '</div>';
            return $return;
        }

        // select options
        if (
            in_array($kind, ['select']) and
            isset($options[$value])
        ) {
            $return = $options[$value];
            return $return;
        }

        // checkbox, radio options
        if (
            in_array($kind, ['input']) and
            in_array($type, ['checkbox', 'radio']) and
            isset($options[$value])
        ) {
            $return = $options[$value];
            return $return;
        }

        // default
        return $return;
    }
}
