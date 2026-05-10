<?php

namespace WpDatabaseHelperV2\Example;

final class MetaBuilder {

    function __construct() {
        //
    }

    private string $option_name = '';
    private mixed $dbValue = false;
    public static function make($option_name = '', $dbValue = false): self {
        $instance = new self();
        $instance->option_name = $option_name;
        $instance->dbValue = $dbValue;
        return $instance;
    }

    public function render() {
        $dbValue = $this->dbValue;
        $option_name = $this->option_name;

        $repeater = \WpDatabaseHelperV2\Fields\WpRepeater::make()
            ->name($option_name)
            ->value($dbValue)
            ->label('Metabox Builder')
            ->visible('show');

        // Chuẩn bị fields schema bên trong repeater
        $fields = [
            // Field label
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('select')
                ->options(get_post_types())
                ->name('_post_type')
                ->label('Post type')
                ->attributes(['placeholder' => 'post, page, product,...'])
                ->default(''),

            // Field label
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('input')
                ->type('text')
                ->name('metabox_label')
                ->label('Metabox Label')
                ->attributes(['placeholder' => 'Post Metadata'])
                ->default(''),
        ];

        //
        if (empty($dbValue)) {
            $fields[] = \WpDatabaseHelperV2\Fields\WpRepeater::make()
                ->name('fields')
                ->label('Fields children')
                ->hideIfDbValueNotSet()
                ->visible('show') // level 1 thì show
                ->hasHiddenFields()
                ->fields($this->___repeaterChildrenFieldDefined('php'));
            // echo '<pre>'; print_r($fields); echo '</pre>'; die;
        }

        //
        else {
            // Tìm level number lớn nhất trong tất cả item
            $__levelItem_func = function ($item) use (&$__levelItem_func): int {
                if (isset($item['fields']) && is_array($item['fields'])) {
                    $childLevels = array_map(fn($child) => $__levelItem_func($child), $item['fields']);
                    return 1 + max($childLevels);
                }

                if (isset($item['children']) && is_array($item['children']) && !empty($item['children'])) {
                    $childLevels = array_map(fn($child) => $__levelItem_func($child), $item['children']);
                    return 1 + max($childLevels);
                }

                return 1; // level 1
            };

            $levels = [];
            foreach ((array)$dbValue as $key => $item) {
                $levels[$key] = $__levelItem_func($item);
            }
            $maxLevel = max($levels);
            $maxLevelIndex = array_search($maxLevel, $levels);

            $item = $dbValue[$maxLevelIndex];

            // Tạo repeater chính cho item max depth
            // Build nested repeater structure by depth level
            $nestedRepeater = null;

            // Note: lặp qua từng level và gán tiếp giá trị vào $nestedRepeater
            for ($i = $maxLevel; $i >= 1; $i--) {
                // Base repeater fields
                $repeaterFields = $this->___repeaterChildrenFieldDefined('php');

                // If this is not the deepest repeater, append previous nested repeater inside "fields"
                if ($nestedRepeater !== null) {
                    $repeaterFields[] = $nestedRepeater;
                }
                // echo '<pre>'; print_r($repeaterFields); echo '</pre>';die;

                // Repeater con render khi có value
                $nestedRepeater = \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name('fields')
                    ->label('Fields children')
                    ->hasHiddenFields()
                    ->hideIfDbValueNotSet()
                    ->fields($repeaterFields)
                    ->default([]);

                // if level > 2
                if ($i >= 2) {
                    // level 2 thì luôn show
                    $nestedRepeater->visible('hidden');
                    // để kind change thì remove .wpdh-append-added
                    $nestedRepeater->addClass('wpdh-append-added');
                }
            }
            // echo '<pre>'; print_r($nestedRepeater); echo '</pre>';die;

            // Cuối cùng, thêm repeater đã xây vào $fields chính
            $fields[] = $nestedRepeater;
        }
        // echo '<pre>'; print_r($fields); echo '</pre>'; die;

        $repeater->fields($fields);
        // echo '<pre>'; print_r($this->___repeaterChildrenFieldDefined('default_value')); echo '</pre>';
        // die;
        $repeater->default(
            [
                [
                    '_post_type' => '',
                    'metabox_label' => '',
                    'fields' => [
                        0 => array_merge(
                            $this->___repeaterChildrenFieldDefined('default_value'),
                            [
                                'adminColumn' => true
                            ]
                        )
                    ]
                ]
            ]
        );

        // echo '<pre>'; print_r($repeater); echo '</pre>';die;
        return $repeater->render();
    }

    public function read() {

        //
        if (!$this->dbValue) {
            return;
        }

        foreach ($this->dbValue as $metabox_builder) {
            //
            if (!($metabox_builder['_post_type'] ?? '')) {
                continue;
            }

            // continue;
            // echo '<pre>'; print_r($metabox_builder); echo '</pre>';

            $WpMeta = \WpDatabaseHelperV2\Meta\WpMeta::make();
            $WpMeta->post_type($metabox_builder['_post_type'] ?? '');
            $WpMeta->label($metabox_builder['metabox_label'] ?? '');
            $fields = [];

            foreach ((array)($metabox_builder['fields'] ?? []) as $key => $fieldSetting) {

                // 
                $kind = $fieldSetting['kind'] ?? '';

                // 
                if (in_array($kind, ['input', 'textarea', 'select', 'tab'])) {
                    $fieldObject = \WpDatabaseHelperV2\Fields\WpField::make();
                }
                if (in_array($kind, ['repeater'])) {
                    $fieldObject = \WpDatabaseHelperV2\Fields\WpRepeater::make();
                }

                //
                $this->__set_value($fieldObject, 'kind', $kind);

                // 
                $label = $fieldSetting['label'] ?? '';
                $this->__set_value($fieldObject, 'label', $label);


                /** ----------- General ----------- */

                // 
                $name = $fieldSetting['name'] ?? '';
                $name = $name ?: sanitize_title($fieldSetting['label'] ?? '');
                $this->__set_value($fieldObject, 'name', $name);

                // 
                $default = $fieldSetting['default'] ?? '';
                $this->__set_value($fieldObject, 'default', $default);

                // 
                $general_attributes = $fieldSetting['general_attributes'] ?? [];
                $attributes = [];
                foreach ((array)$general_attributes as $key => $value) {
                    if (($value['key'] ?? '') and ($value['value'] ?? '')) {
                        $attributes[$value['key']] = $value['value'];
                    }
                }
                $this->__set_value($fieldObject, 'attributes', $attributes);

                // 
                $width = $fieldSetting['width'] ?? false;
                $this->__set_value($fieldObject, 'width', $width);

                // notes
                $notes = $fieldSetting['notes'] ?? '';
                if ($notes) {
                    $notes = explode("\n", $notes);
                    $this->__set_value($fieldObject, 'notes', $notes);
                }

                // 
                $adminColumn = $fieldSetting['adminColumn'] ?? false;
                $this->__set_value($fieldObject, 'adminColumn', $adminColumn);

                /** ----------- Dynamic ----------- */

                // 
                $type = ($fieldSetting['kind'] ?? '') . '_type';
                $type = $fieldSetting[$type] ?? '';
                $this->__set_value($fieldObject, 'type', $type);

                // options
                $options_raw = $fieldSetting[$kind . '_options_raw'] ?? '';
                $options_user_roles = $fieldSetting[$kind . '_options_user_roles'] ?? [];
                $options_post_types = $fieldSetting[$kind . '_options_post_types'] ?? [];
                $options_taxonomies = $fieldSetting[$kind . '_options_taxonomies'] ?? [];
                $options = $this->__build_field_options(
                    $options_raw,
                    $options_user_roles,
                    $options_post_types,
                    $options_taxonomies
                );
                $this->__set_value($fieldObject, 'options', $options);

                // direction
                $direction = $fieldSetting[$kind . '_direction'] ?? '';
                $this->__set_value($fieldObject, 'direction', $direction);

                /** ----------- Input ----------- */
                // nothing

                /** ----------- Tab ----------- */

                // tabNavs
                $tabNavs = $fieldSetting['tabNavs'] ?? '';
                if ($tabNavs) {
                    $tabNavs = explode("\n", $tabNavs);
                    $this->__set_value($fieldObject, 'tabNavs', $tabNavs);
                }

                //
                $fields[] = $fieldObject;

                /** ----------- Repeater ----------- */
                // 
                $childDirection = $fieldSetting[$kind . '_childDirection'] ?? '';
                $this->__set_value($fieldObject, 'childDirection', $childDirection);
                // build repeater children fields
                if ($kind === 'repeater') {

                    // lấy danh sách field con
                    $children_raw = $fieldSetting['fields'] ?? [];

                    $children_objects = [];

                    foreach ((array)$children_raw as $child_field) {

                        //
                        $kind = $child_field['kind'] ?? '';
                        $child_field['object'] = 'WpField';

                        if ($kind === 'repeater') {
                            $child_field['object'] = 'WpRepeater';
                        }

                        // convert sang object (có xử lý đệ quy bên trong rồi)
                        $children_objects[] = $this->field_convert_to_object_func($child_field);
                    }

                    // set vào repeater
                    $this->__set_value($fieldObject, 'fields', $children_objects);
                }

                // 
                $childDirection = $fieldSetting[$kind . '_childDirection'] ?? '';
                $this->__set_value($fieldObject, 'childDirection', $childDirection);
            }

            // echo '<pre>'; print_r($fields); echo '</pre>'; die;

            //
            $WpMeta->fields($fields);
            $WpMeta->register();
        }
    }

    public function field_convert_to_object_func($field) {
        error_log(__CLASS__ . '::' . __FUNCTION__ . '() $field: ' . print_r($field, true));

        // create object
        if (($field['object'] ?? '') == 'WpField') {
            $fieldObject = \WpDatabaseHelperV2\Fields\WpField::make();
        }
        if (($field['object'] ?? '') == 'WpRepeater') {
            $fieldObject = \WpDatabaseHelperV2\Fields\WpRepeater::make();
        }

        // build general_attributes -> attributes
        $general_attributes = $field['general_attributes'] ?? [];
        $attributes = [];
        foreach ((array)$general_attributes as $attr) {
            $attr_key = $attr['key'] ?? '';
            $attr_value = $attr['value'] ?? '';

            if ($attr_key && $attr_value) {
                $attributes[$attr_key] = $attr_value;
            }
        }
        if (!empty($attributes) && method_exists($fieldObject, 'attributes')) {
            $fieldObject->attributes($attributes);
        }

        foreach ((array)$field as $method_name => $method_value) {

            //
            if ($method_name == 'object') {
                continue;
            }

            // 
            if ($method_name == 'fields') {
                $sub_fields = [];
                foreach ((array)$method_value as $key => $value) {
                    $sub_fields[] = $this->field_convert_to_object_func($value);
                }
                $method_value = $sub_fields;
            }

            if (method_exists($fieldObject, $method_name)) {
                $fieldObject->{$method_name}($method_value); // ✅ call method
            } else if ($this->self_debug) {
                $debug = implode(
                    ', ',
                    [
                        'method not found',
                        get_class($fieldObject),
                        $method_name,
                        json_encode($method_value),
                    ]
                );
                error_log($debug);
            }

            // fix for 'data-append-config' => 'inherit' 
            if ($method_name == 'attributes' and ($method_value['data-append-config'] ?? '') == 'inherit') {
                $fieldObject->attributes($this->___clickToAppendAttribute());
            }
        }

        return $fieldObject;
    }

    function __set_value($object, $method_name, $value) {
        if (method_exists($object, $method_name)) {
            $object->{$method_name}($value);
        } else if($this->self_debug) {
            $debug = implode(
                ', ',
                [
                    'method not found',
                    get_class($object),
                    $method_name,
                    json_encode($value),
                ]
            );
            error_log($debug);
        }
    }

    function __build_field_options($raw, $user_roles, $post_types, $taxonomies) {
        $return = [];

        // $raw,
        $raw_array = [];
        $raws = explode("\r\n", $raw);
        $raws = array_filter($raws);
        foreach ($raws as $line) {
            $line = trim($line);
            // xxx:XXX
            $explode = explode(':', $line);
            $key = $explode[0] ?? '';
            $value = $explode[1] ?? '';

            if (!$key or !$value) {
                continue;
            }

            $raw_array[$key] = $value;
        }
        $return += $raw_array;

        // $user_roles,
        global $wpdb;
        $user_ids_array = [];
        foreach ($user_roles as $user_role) {
            // get serialized capabilities
            $capabilities = serialize([$user_role => true]);

            // build sql query
            $sql = $wpdb->prepare(
                "SELECT u.ID, u.display_name
                FROM {$wpdb->users} AS u
                INNER JOIN {$wpdb->usermeta} AS um ON um.user_id = u.ID
                WHERE um.meta_key = %s AND um.meta_value = %s",
                $wpdb->get_blog_prefix() . 'capabilities',
                $capabilities
            );

            // get role label
            $role_label = wp_roles()->roles[$user_role]['name'] ?? '';

            // query data
            $rows = $wpdb->get_results($sql);

            foreach ($rows as $row) {
                // push user label
                $user_ids_array['user_' . $row->ID] = "[$role_label] $row->display_name";
            }
        }
        $return += $user_ids_array;

        // $post_types,
        $post_types_array = [];
        foreach ($post_types as $post_type) {
            $post_type = trim($post_type);

            // skip empty values
            if ($post_type === '') {
                continue;
            }

            // label
            $post_type_label = get_post_type_object($post_type)->label ?? '';

            // query posts by post type
            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
            ]);

            if (empty($posts)) {
                continue;
            }

            foreach ($posts as $post) {
                $post_types_array['post_' . $post->ID] = "[$post_type_label] $post->post_title";
            }
        }

        // echo '<pre>'; print_r($post_types_array); echo '</pre>';
        $return += $post_types_array;

        // $taxonomies
        $taxonomies_array = [];
        foreach ($taxonomies as $taxonomy) {
            $taxonomy = trim($taxonomy);

            // skip empty values
            if ($taxonomy === '') {
                continue;
            }

            // label
            $taxonomy_label = get_taxonomy($taxonomy)->label ?? '';

            // query terms from taxonomy
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                /* add term id and name */
                $taxonomies_array['term_' . $term->term_id] = "[$taxonomy_label] $term->name";
            }
        }

        // echo '<pre>'; print_r($taxonomies_array); echo '</pre>'; die;
        $return += $taxonomies_array;

        return $return;
    }

    public function ___clickToAppendAttribute() {
        $return = [
            'class' => 'clickToAppend',
            'data-append-config' => json_encode(
                [
                    'appendWhenValues' => [
                        'repeater',
                    ],
                    'appendFields' => [
                        'repeater' => [
                            [
                                'object' => 'WpRepeater',
                                'name' => 'fields',
                                'label' => 'Fields children',
                                'visible' => 'show', // trong ajax thì luôn show
                                'hasHiddenFields' => true,
                                'default' => [
                                    0 => $this->___repeaterChildrenFieldDefined('default_value'),
                                ],
                                'fields' => $this->___repeaterChildrenFieldDefined('js'),
                            ],
                        ]
                    ]
                ]
            )
        ];
        // echo '<pre>'; print_r($this->___repeaterChildrenFieldDefined('default_value')); echo '</pre>';
        // echo '<pre>'; print_r($return); echo '</pre>'; 
        // die;
        return $return;
    }

    public function ___repeaterChildrenFieldDefined($context = 'js') {

        $return = [];

        if (isset($context) and $context == 'default_value') {
            foreach ((array)$this->schema as $key => $fieldDefinition) {
                $default = $fieldDefinition['default'] ?? '';
                $name = $fieldDefinition['name'] ?? '';
                $return[$name] = $default;
            }
        }

        if (isset($context) and $context == 'php') {
            foreach ((array)$this->schema as $key => $field) {

                //
                if (!($field['object'] ?? '')) {
                    error_log('object class not found: ' . json_encode($field));
                    continue;
                }

                $return[] = $this->field_convert_to_object_func($field);
            }
        }

        if (isset($context) and $context == 'js') {
            $return = $this->schema;
        }

        return $return;
    }

    private $self_debug = false;
    private $schema = [
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'text',
            'name' => 'label',
            'label' => 'Field label',
            'attributes' => [
                'placeholder' => 'Field label'
            ],
            'default' => '',
        ],

        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'kind',
            'label' => 'Kind',
            'visible' => 'hidden',
            'default' => 'input',
            'options' => [
                'input' => 'Input',
                'textarea' => 'Textarea',
                'select' => 'Select dropdown',
                'repeater' => 'Repeater',
                'tab' => 'Tab',
            ],
            'attributes' => [
                'class' => 'clickToAppend',
                'data-append-config' => 'inherit' // inherit parent
            ]

        ],

        // tab navs
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabNavs',
            'includeHiddenInput' => true,
            'type' => 'nav',
            'visible' => 'hidden',
            'direction' => 'horizontal',
            'tabNavs' => [
                'General',
                'Input',
                'Textarea',
                'Select dropdown',
                'Repeater',
                'Tab',
            ]
        ],

        // ====== General settings =======
        // tab start
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabStart_general',
            'includeHiddenInput' => true,
            'type' => 'start',
            'visible' => 'hidden',
            'label' => 'General',
        ],

        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'text',
            'name' => 'name',
            'label' => 'Field name',
            // 'visible' => 'hidden',
            'attributes' => [
                'placeholder' => 'field_name'
            ],
            'default' => '',
        ],

        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'text',
            'name' => 'default',
            'label' => 'Default value',
            // 'visible' => 'hidden',
            'attributes' => ['placeholder' => 'Default value'],
            'default' => '',
        ],

        // attributes repeater
        [
            'object' => 'WpRepeater',
            'name' => 'general_attributes',
            'label' => 'Attributes',
            'childDirection' => 'horizontal',
            'fields' => [
                [
                    'object' => 'WpField',
                    'kind' => 'input',
                    'type' => 'text',
                    'name' => 'key',
                    'label' => 'Key',
                    'attributes' => [
                        'placeholder' => 'Attribute key'
                    ],
                    'default' => '',
                ],
                [
                    'object' => 'WpField',
                    'kind' => 'input',
                    'type' => 'text',
                    'name' => 'value',
                    'label' => 'Value',
                    'attributes' => [
                        'placeholder' => 'Attribute value'
                    ],
                    'default' => '',
                ],
            ],
            'default' => [
                0 => [
                    'key' => '',
                    'value' => '',
                ]
            ],
        ],

        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'width',
            'label' => 'Field width',
            'options' => [
                '' => 'Default',
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
                '7' => '7',
                '8' => '8',
                '9' => '9',
                '10' => '10',
                '11' => '11',
                '12' => '12',
                'auto' => 'Auto'
            ],
            'default' => '',
        ],

        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'gridColumn',
            'label' => 'Grid Column',
            'options' => [
                '' => 'Default',
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
                '7' => '7',
                '8' => '8',
                '9' => '9',
                '10' => '10',
                '11' => '11',
                '12' => '12',
                'auto' => 'Auto'
            ],
            'default' => '',
        ],

        [
            'object' => 'WpField',
            'kind' => 'textarea',
            'name' => 'notes',
            'label' => 'Notes',
            'notes' => ['each note on a new line'],
            'default' => '',
        ],

        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'name' => 'adminColumn',
            'label' => 'Admin Column',
            'default' => '',
        ],

        // end tab
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabEnd_general',
            'includeHiddenInput' => true,
            'type' => 'end',
        ],

        // ====== Input settings =======
        // tab start
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabStart_input',
            'includeHiddenInput' => true,
            'type' => 'start',
            'visible' => 'hidden',
            'label' => 'Input',
        ],

        //
        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'input_type',
            'label' => 'Type',
            'options' => [
                'text' => 'Text',
                'number' => 'Number',
                'email' => 'Email',
                'password' => 'Password',
                'checkbox' => 'Checkbox',
                'radio' => 'Radio',
                'date' => 'Date',
                'time' => 'Time',
                'color' => 'Color',
                // 'file' => 'File',
                'url' => 'URL',
                'hidden' => 'Hidden',

                //
                'wp_media' => '[new] WP Media',
                'wp_multiple_media' => '[new] WP Multiple Media',
            ]
        ],

        // options textarea
        [
            'object' => 'WpField',
            'kind' => 'textarea',
            'name' => 'input_options_raw',
            'label' => 'Options Raw',
            'attributes' => [
                'placeholder' => "abc:Abc\r\nxyz:Xyz",
            ],
            'default' => '',
            'notes' => ['For checkbox, radio'],
        ],

        // options user roles
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'direction' => 'horizontal',
            'name' => 'input_options_user_roles',
            'label' => 'User Roles',
            'optionsTemplate' => 'user_roles',
            'default' => '',
        ],

        // options post types
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'direction' => 'horizontal',
            'name' => 'input_options_post_types',
            'label' => 'Post Types',
            'optionsTemplate' => 'post_types',
            'default' => '',
        ],

        // options taxonomies
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'direction' => 'horizontal',
            'name' => 'input_options_taxonomies',
            'label' => 'Taxonomies',
            'optionsTemplate' => 'taxonomies',
            'default' => '',
        ],

        // direction
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'radio',
            'direction' => 'horizontal',
            'name' => 'input_direction',
            'label' => 'Direction',
            'default' => 'horizontal',
            'options' => [
                'horizontal' => 'Horizontal',
                'vertical' => 'Vertical',
            ],
        ],


        // end tab
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabEnd_input',
            'includeHiddenInput' => true,
            'type' => 'end',
        ],

        // ====== Textarea settings =======
        // tab start
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabStart_textarea',
            'includeHiddenInput' => true,
            'type' => 'start',
            'visible' => 'hidden',
            'label' => 'Textarea',
        ],

        //
        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'textarea_type',
            'label' => 'Type',
            'options' => [
                'wp_editor' => '[new] WP Editor',
            ]
        ],

        // end tab
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabEnd_textarea',
            'includeHiddenInput' => true,
            'type' => 'end',
        ],

        // ====== Options settings =======
        // tab start
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabStart_select_dropdown',
            'includeHiddenInput' => true,
            'type' => 'start',
            'visible' => 'hidden',
            'label' => 'Select dropdown',
        ],

        // options type
        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'select_type',
            'label' => 'Type',
            'options' => [
                'select2' => 'select2',
            ]
        ],

        // options raw
        [
            'object' => 'WpField',
            'kind' => 'textarea',
            'name' => 'select_options_raw',
            'label' => 'Raw',
            'attributes' => [
                'placeholder' => "abc:Abc\r\nxyz:Xyz",
            ],
            'default' => '',
        ],

        // options user roles
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'direction' => 'horizontal',
            'name' => 'select_options_user_roles',
            'label' => 'User Roles',
            'optionsTemplate' => 'user_roles',
            'default' => '',
        ],

        // options post types
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'direction' => 'horizontal',
            'name' => 'select_options_post_types',
            'label' => 'Post Types',
            'optionsTemplate' => 'post_types',
            'default' => '',
        ],

        // options taxonomies
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'checkbox',
            'direction' => 'horizontal',
            'name' => 'select_options_taxonomies',
            'label' => 'Taxonomies',
            'optionsTemplate' => 'taxonomies',
            'default' => '',
        ],

        // end tab
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabEnd_select_dropdown',
            'includeHiddenInput' => true,
            'type' => 'end',
        ],

        // ====== Repeater settings =======
        // tab start
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabStart_repeater',
            'includeHiddenInput' => true,
            'type' => 'start',
            'visible' => 'hidden',
            'label' => 'Repeater',
        ],

        // direction
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'radio',
            'direction' => 'horizontal',
            'name' => 'repeater_direction',
            'label' => 'Direction',
            'default' => 'vertical',
            'options' => [
                'horizontal' => 'Horizontal',
                'vertical' => 'Vertical',
            ],
        ],

        // child direction
        [
            'object' => 'WpField',
            'kind' => 'input',
            'type' => 'radio',
            'direction' => 'horizontal',
            'name' => 'repeater_childDirection',
            'label' => 'Children direction',
            'default' => 'vertical',
            'options' => [
                'horizontal' => 'Horizontal',
                'vertical' => 'Vertical',
            ],
        ],

        // end tab
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabEnd_repeater',
            'includeHiddenInput' => true,
            'type' => 'end',
        ],

        // ====== Tab settings =======
        // tab start
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabStart_tab',
            'includeHiddenInput' => true,
            'type' => 'start',
            'visible' => 'hidden',
            'label' => 'Tab',
        ],

        // options type
        [
            'object' => 'WpField',
            'kind' => 'select',
            'name' => 'tab_type',
            'label' => 'Type',
            'options' => [
                'nav' => 'Nav',
                'start' => 'Start',
                'end' => 'End',
            ]
        ],

        // options navs
        [
            'object' => 'WpField',
            'kind' => 'textarea',
            'name' => 'tabNavs',
            'label' => 'Tab navs',
            'attributes' => [
                'placeholder' => "Tab 1\r\nTab 2",
            ],
            'default' => '',
            'notes' => ['Each nav name must be unique and new line separated.'],
        ],

        // end tab
        [
            'object' => 'WpField',
            'kind' => 'tab',
            'name' => 'tabEnd_tab',
            'includeHiddenInput' => true,
            'type' => 'end',
        ],

    ];
}
