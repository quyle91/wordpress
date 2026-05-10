<?php
class Flatsome_family_tree2 {
    public $data = [];
    public $max_level;

    function __construct() {
        $this->create_element();
    }

    function loop_recursive($post_type, $parent_id = 0, $level = 0) {
        $return = []; // Initialize the return array here

        if ($level > ($this->max_level - 1)) {
            return $return;
        }

        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'parent',
            'order' => 'ASC',
            'post_parent' => $parent_id,
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $return[] = [
                    'id' => get_the_ID(),
                    'name' => get_the_title(),
                    'parent' => $parent_id,
                    'level' => $level,
                    'children' => count(get_children([
                        'post_parent' => get_the_ID(),
                        'post_type' => $post_type,
                        'post_status' => 'publish',
                    ])),
                ];

                // Recursively call the function
                $return = array_merge($return, $this->loop_recursive($post_type, get_the_ID(), $level + 1));
            }

            wp_reset_postdata();
        }

        return $return;
    }

    function adminz_family_tree_before_name($item, $atts) {
        echo '<pre>'; print_r($atts); echo '</pre>';
        echo "<div><small>[id:" . ($item['id']) . "]</small></div>";
        echo "<div><small>[parent:" . ($item['parent']) . "]</small></div>";
    }
    
    function adminz_family_tree_after_name($item, $atts) {
        echo "<div><small>[level:" . ($item['level']) . "]</small></div>";
        echo "<div><small>[children:" . ($item['children']) . "]</small></div>";
        echo "<div><small>[fixY:" . ($item['fixY']) . "]</small></div>";
    }

    function create_element() {
        $___ = new \Adminz\Helper\FlatsomeELement;
        $___->shortcode_name = 'adminz_family_tree';
        $___->shortcode_title = 'Family Tree';
        $___->shortcode_icon = 'text';
        $___->options = [
            'post_type' => [
                'type' => 'textfield',
                'heading' => 'Post type',
            ],
            'style' => [
                'type' => 'select',
                'heading' => 'Style',
                'default' => '',
                'options' => [
                    '' => 'default',
                    'classic' => 'Classic',
                ]
            ],
            'parent_id' => [
                'type' => 'textfield',
                'heading' => 'Parent Id',
            ],
            'include_parent' => [
                'type' => 'checkbox',
                'heading' => 'Include parent first',
            ],
            'max_level' => [
                'type' => 'textfield',
                'heading' => 'Max level',
            ],
            'test' => [
                'type' => 'checkbox',
                'heading' => 'Test Hooks',
            ],
        ];
        $___->shortcode_callback = function ($atts, $content = null) {

            // =========================
            // reset state cho mỗi shortcode
            // =========================
            $this->data = [];
            $this->max_level = null;

            extract(shortcode_atts(array(
                'id' => rand(),
                'post_type' => 'page',
                'parent_id' => 0,
                'include_parent' => '',
                'max_level' => PHP_INT_MAX,
                'test' => '',
                'style' => '',
            ), $atts));

            $this->max_level = $max_level;
            if ($test) {
                add_action('adminz_family_tree_before_name', [$this, 'adminz_family_tree_before_name'], 10, 2);
                add_action('adminz_family_tree_after_name', [$this, 'adminz_family_tree_after_name'], 10, 2);
            }

            $level = 0;
            if ($include_parent and $parent_id) {
                $parent_item = [
                    'id' => $parent_id,
                    'name' => get_the_title($parent_id),
                    'parent' => 0,
                    'level' => $level,
                    'children' => count(get_children([
                        'post_parent' => $parent_id,
                        'post_type' => $post_type,
                        'post_status' => 'publish',
                    ])),
                ];

                $this->data = array_merge($this->data, [$parent_item]);
                $level++;
            }

            $this->data = array_merge($this->data, $this->loop_recursive($post_type, $parent_id, $level));

            if (empty($this->data)) {
                return __('Sorry, no posts matched your criteria.');
            }

            // group data by level
            $this->data = $this->group_data_by_level();
            $this->data = $this->group_data_by_parent();
            $return = '';

            // classic style
            if ($style == 'classic') {

                //
                wp_enqueue_script(
                    'adminz_family_tree_classic_js',
                    ADMINZ_DIR_URL . 'assets/flatsome-elements/family_tree/classic.js',
                    array('jquery'),
                    ADMINZ_VERSION,
                    true
                );

                wp_enqueue_style(
                    'adminz_family_tree_classic_css',
                    ADMINZ_DIR_URL . 'assets/flatsome-elements/family_tree/classic.css',
                    array(),
                    ADMINZ_VERSION
                );

                // nếu data trống thì return sớm
                if (empty($this->data)) {
                    return '';
                }

                if (!isset($this->data[0]['items'][0])) {
                    return '';
                }

                // =========================
                // chuẩn bị button
                // =========================
                $button_toggle = '<button type="button" class="tree-toggle button is-outline is-xxsmall primary mb-0 mr-0" aria-expanded="false">+</button>';

                $button_toggle_placeholder = '<span class="tree-toggle-placeholder button is-outline is-xxsmall mb-0 mr-0 disabled">i</span>';

                // =========================
                // build tree đệ quy
                // =========================
                $html = '';

                $render_items = function ($parent_id, $level) use (&$render_items, &$html, $button_toggle, $button_toggle_placeholder, $atts) {

                    if (!isset($this->data[$level]['items'][$parent_id])) {
                        return;
                    }

                    $items = $this->data[$level]['items'][$parent_id];

                    $html .= '<ul class="tree-children adminz-tree-level adminz-tree-level-' . (int) $level . ' hidden">';

                    foreach ($items as $item) {

                        if (empty($item)) {
                            continue;
                        }

                        $item_id = $item['id'];
                        $item_children = (int) ($item['children'] ?? 0);

                        $html .= '<li class="tree-item" data-id="' . esc_attr($item_id) . '">';
                        $html .= '<div class="tree-row">';

                        if ($item_children > 0) {
                            $html .= $button_toggle;
                        } else {
                            $html .= $button_toggle_placeholder;
                        }

                        $html .= '<span class="tree-label">' . $this->html_item_classic($item, $atts) . '</span>';
                        $html .= '</div>';

                        // render tiếp level sau nếu có
                        if ($item_children > 0) {
                            $render_items($item_id, $level + 1);
                        }

                        $html .= '</li>';
                    }

                    $html .= '</ul>';
                };

                // =========================
                // level gốc (root)
                // =========================
                $html .= '<ul class="adminz-tree-level adminz-tree-level-0">';

                $root_items = $this->data[0]['items'][0];

                foreach ($root_items as $item0) {

                    if (empty($item0)) {
                        continue;
                    }

                    $item0_id = $item0['id'];
                    $item0_children = (int) ($item0['children'] ?? 0);

                    $html .= '<li class="tree-item" data-id="' . esc_attr($item0_id) . '">';
                    $html .= '<div class="tree-row">';

                    if ($item0_children > 0) {
                        $html .= $button_toggle;
                    } else {
                        $html .= $button_toggle_placeholder;
                    }

                    $html .= '<span class="tree-label">' . $this->html_item_classic($item0, $atts) . '</span>';
                    $html .= '</div>';

                    if ($item0_children > 0) {
                        $render_items($item0_id, 1);
                    }

                    $html .= '</li>';
                }

                $html .= '</ul>';

                $return = <<<HTML
                <div class="adminz_family_tree_wrap">
                    <div class="adminz_family_tree adminz_family_tree_classic">
                        {$html}
                    </div>
                </div>
                HTML;
            }

            // Default style
            else {
                //
                wp_enqueue_script(
                    'adminz_family_tree_default_js',
                    ADMINZ_DIR_URL . 'assets/flatsome-elements/family_tree/default.js',
                    array('jquery'),
                    ADMINZ_VERSION,
                    true
                );
                wp_enqueue_style(
                    'adminz_family_tree_default_css',
                    ADMINZ_DIR_URL . 'assets/flatsome-elements/family_tree/default.css',
                    array(),
                    ADMINZ_VERSION
                );

                $levelsHtml = '';
                foreach ((array) $this->data as $key => $value) {
                    $items = $value['items'] ?? [];
                    $fixMargin = (int) ($value['fixMargin'] ?? 0);
                    $style = 'margin-bottom: ' . (7 * $fixMargin) . 'px;';
                    $dataFixY = 7 * $fixMargin;

                    // HTML của các group trong level
                    $groupsHtml = '';

                    foreach ((array) $items as $_key => $_value) {

                        // HTML của các item trong group
                        $itemsHtml = '';

                        foreach ((array) $_value as $__key => $__value) {
                            // giữ nguyên hàm hiện tại
                            $itemsHtml .= $this->html_item_default($__value, $atts);
                        }

                        $groupKey = esc_attr($_key);

                        $groupsHtml .= <<<HTML
                        <div class="group group-{$groupKey} flex justify-around">
                            {$itemsHtml}
                        </div>
                    HTML;
                    }

                    $levelKey = esc_attr($key);
                    $styleAttr = esc_attr($style);

                    $levelsHtml .= <<<HTML
                    <div
                        class="level level-{$levelKey} flex justify-around"
                        style="{$styleAttr}"
                        data-fixY="{$dataFixY}">
                        {$groupsHtml}
                    </div>
                HTML;
                }

                $return = <<<HTML
                <div class="adminz_family_tree_wrap">
                    <div class="adminz_family_tree adminz_family_tree_default flex-row-col">
                        <svg></svg>
                        {$levelsHtml}
                    </div>
                </div>
                HTML;
            }

            // remove filter test
            if ($test) {
                remove_action('adminz_family_tree_before_name', [$this, 'adminz_family_tree_before_name'], 10, 2);
                remove_action('adminz_family_tree_after_name', [$this, 'adminz_family_tree_after_name'], 10, 2);
            }

            return $return;
        };

        $___->general_element();
    }

    function html_item_classic($item, $atts) {
        //
        $classes = [
            'item',
            'item-' . $item['id'],
            // 'has-border',
            // 'round',
            // 'no-padding',
            // 'text-center'
        ];

        //
        if ($item['children']) {
            $classes[] = 'has-children';
        }

        //
        $attritube = [];
        // if ($item['fixY']) {
            // $attritube[] = 'data-fixY="' . $item['fixY'] . '"';
        // }

        //
        $itemId = $item['id'] ?? 0;

        //
        $classAttr = '';
        if (!empty($classes) && is_array($classes)) {
            $classAttr = implode(' ', $classes);
        }

        //
        $otherAttr = '';
        if (!empty($attritube) && is_array($attritube)) {
            $otherAttr = implode(' ', $attritube);
        }

        ob_start();
        echo '<div data-id="' . esc_attr($itemId) . '" class="' . esc_attr($classAttr) . '" ' . $otherAttr . '>';

        // before name hook (echo trực tiếp trong callback)
        do_action('adminz_family_tree_before_name', $item, $atts);

        // item name (filter return string)
        echo apply_filters(
            'adminz_family_tree_item_name',
            '<a href="' . esc_url(get_permalink($itemId)) . '">' . esc_html($item['name'] ?? '') . '</a>'
        );

        // before name hook (echo trực tiếp trong callback)
        do_action('adminz_family_tree_after_name', $item, $atts);

        echo '</div>';
        return ob_get_clean();
    }

    function html_item_default($item, $atts) {

        //
        $classes = [
            'item',
            'item-' . $item['id'],
            'has-border',
            'round',
            'no-padding',
            'text-center'
        ];

        //
        if ($item['children']) {
            $classes[] = 'has-children';
        }

        //
        $attritube = [];
        if ($item['fixY']) {
            $attritube[] = 'data-fixY="' . $item['fixY'] . '"';
        }

        //
        $itemId = $item['id'] ?? 0;

        //
        $classAttr = '';
        if (!empty($classes) && is_array($classes)) {
            $classAttr = implode(' ', $classes);
        }

        //
        $otherAttr = '';
        if (!empty($attritube) && is_array($attritube)) {
            $otherAttr = implode(' ', $attritube);
        }

        ob_start();
        echo '<div data-id="' . esc_attr($itemId) . '" class="' . esc_attr($classAttr) . '" ' . $otherAttr . '>';

        // before name hook (echo trực tiếp trong callback)
        do_action('adminz_family_tree_before_name', $item, $atts);

        // item name (filter return string)
        echo apply_filters(
            'adminz_family_tree_item_name',
            '<a href="' . esc_url(get_permalink($itemId)) . '">' . esc_html($item['name'] ?? '') . '</a>'
        );

        // before name hook (echo trực tiếp trong callback)
        do_action('adminz_family_tree_after_name', $item, $atts);

        echo '</div>';
        return ob_get_clean();
    }

    function group_data_by_level() {
        $return = [];
        foreach ((array) $this->data as $key => $value) {
            $return[$value['level']][] = $value;
        }
        return $return;
    }

    function group_data_by_parent() {
        $return = [];
        foreach ((array) $this->data as $key => $value) {
            $level = [];
            $items = [];
            $count_children_is_parent = 0;
            foreach ((array) $value as $_key => $_value) {
                if ($_value['children'] and $_key > 0) {
                    $count_children_is_parent++;
                }
                $_tmp = $_value;
                $_tmp['fixY'] = $count_children_is_parent;
                $items[$_value['parent']][] = $_tmp;
            }

            $level['parent'] = $key;
            $level['fixMargin'] = $count_children_is_parent;
            $level['items'] = $items;
            $return[] = $level;
        }
        // echo "<pre>"; print_r($return); echo "</pre>"; die;
        return $return;
    }
}

new Flatsome_family_tree2();
