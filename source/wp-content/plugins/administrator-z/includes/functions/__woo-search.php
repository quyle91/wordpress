<?php
function adminz_woo_search_field($atts) {
    $classes = adminz_woo_form_field_classes($atts);
    ob_start();
?>
    <div
        id="<?= esc_attr($atts['_id']) ?>"
        class="<?= implode(" ", $classes) ?>">
        <div class="col-inner">
            <?php echo adminz_woo_form_label($atts) ?>
            <?php echo adminz_woo_form_field($atts) ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function adminz_woo_form_field_classes($atts) {
    // get col class
    $classes = ["col", $atts['class'], 'adminz_woo_search_item'];

    // small
    $class_small = "small-" . $atts['col_small'];
    // large
    $class_large = "large-" . $atts['col_large'];

    if ($atts['break_line'] == "true") {
        $class_small = "small-12";
        $class_large = "large-12";
    }

    $classes[] = $class_small;
    $classes[] = $class_large;

    // view more
    if ($atts['hide'] == "true") {
        $classes[] = "view_more";
        $classes[] = "hidden";

        // override parent state
        $parent_state = (array)adminz_tmp('parent_state');
        $parent_state['has_view_more'] = true;
        adminz_tmp('parent_state', $parent_state);
    }

    $classes = array_filter($classes);
    return $classes;
}

function adminz_woo_form_label($atts) {
    if (!$atts['label']) return;
    ob_start();
?>
    <div class="widget">
        <span class="widget-title">
            <small>
                <?= $atts['label'] ?>
            </small>
        </span>
        <div class="is-divider small"></div>
    </div>
<?php
    return do_shortcode(ob_get_clean());
}

function adminz_woo_form_field($atts) {
    // get tmp global variables
    adminz_tmp('taxonomy_options', adminz_get_taxonomy_options('product'),);
    adminz_tmp('meta_options', adminz_get_meta_options('product'));
    adminz_tmp('attribute_options', adminz_get_attribute_options('product'));

    $function_name = __FUNCTION__ . "_" . $atts['type'];

    // do filter first
    ob_start();
    do_action('adminz_woo_form_field', $atts['type'], $atts);
    if ($custom = ob_get_clean()) {
        return $custom;
    }

    // special fields
    if (function_exists($function_name)) {
        return call_user_func($function_name, $atts);
    }

    // field tax
    if (array_key_exists($atts['type'], (array)adminz_tmp('taxonomy_options'))) {
        return adminz_woo_form_field_tax($atts);
    }

    // field attr
    if (array_key_exists($atts['type'], (array)adminz_tmp('attribute_options'))) {
        return adminz_woo_form_field_attribute($atts);
    }

    // field meta
    if (array_key_exists($atts['type'], (array)adminz_tmp('meta_options'))) {
        return adminz_woo_form_field_meta($atts);
    }

    // other
    $function_name = 'adminz_' . $atts['type'];
    if (function_exists($function_name)) {
        echo call_user_func($function_name, $atts);
    }
}

function adminz_rating_filter($atts) {
    $type = $atts['type'];
    $label = $atts['label'] ? $atts['label'] : __("Rate&hellip;", 'woocommerce');
    ob_start();
?>
    <select name="<?= esc_attr($type) ?>" id="">
        <option value=""> <?= __("Select") ?> <?= strtolower($label) ?> </option>
        <?php
        $options = [
            '1' => sprintf(__("Rated %s out of 5", 'woocommerce'), '1/'),
            '2' => sprintf(__("Rated %s out of 5", 'woocommerce'), '2/'),
            '3' => sprintf(__("Rated %s out of 5", 'woocommerce'), '3/'),
            '4' => sprintf(__("Rated %s out of 5", 'woocommerce'), '4/'),
            '5' => sprintf(__("Rated %s out of 5", 'woocommerce'), '5/'),
        ];
        foreach ((array)$options as $key => $value) {
            $selected = ($_GET['rating_filter'] ?? "") == $key ? "selected" : "";
        ?>
            <option <?= esc_attr($selected) ?> value="<?= esc_attr($key) ?>">
                <?= esc_attr($value); ?>
            </option>
        <?php
        }
        ?>
    </select>
<?php
    return ob_get_clean();
}

function adminz_get_filtered_price($use_default = true) {

    // woo default: get prices on context query vars
    if ($use_default) {
        $filter = new class extends \WC_Widget_Price_Filter {
            public function _get_filtered_price() {
                return $this->get_filtered_price();
            }
        };
        return $filter->_get_filtered_price();
    }

    // always on database
    global $wpdb;
    $sql = "
SELECT MIN(min_price) as min_price, MAX(max_price) as max_price
FROM {$wpdb->wc_product_meta_lookup}
WHERE product_id IN (
SELECT ID FROM {$wpdb->posts}
WHERE {$wpdb->posts}.post_type = 'product'
AND {$wpdb->posts}.post_status = 'publish'
)";
    return $wpdb->get_row($sql);
}

function adminz_min_max_price($atts) {

    $filtered_price = adminz_get_filtered_price();

    // Thiết lập giá trị min và max để sử dụng trong HTML
    $min_price = $filtered_price->min_price ?? 0;
    $max_price = $filtered_price->max_price ?? 100;

    // -------------- START COPY FROM WOOCOMMERCE -------
    // Round values to nearest 10 by default.
    $step = max(apply_filters('woocommerce_price_filter_widget_step', 10), 1);

    // Check to see if we should add taxes to the prices if store are excl tax but display incl.
    $tax_display_mode = get_option('woocommerce_tax_display_shop');

    if (wc_tax_enabled() && !wc_prices_include_tax() && 'incl' === $tax_display_mode) {
        $tax_class = apply_filters('woocommerce_price_filter_widget_tax_class', ''); // Uses standard tax class.
        $tax_rates = WC_Tax::get_rates($tax_class);

        if ($tax_rates) {
            $min_price += WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($min_price, $tax_rates));
            $max_price += WC_Tax::get_tax_total(WC_Tax::calc_exclusive_tax($max_price, $tax_rates));
        }
    }

    $min_price = apply_filters('woocommerce_price_filter_widget_min_amount', floor($min_price / $step) * $step);
    $max_price = apply_filters('woocommerce_price_filter_widget_max_amount', ceil($max_price / $step) * $step);

    // -------------- END COPY FROM WOOCOMMERCE -------

    ob_start();
    $current_min = isset($_GET['min_price']) ? $_GET['min_price'] : $min_price;
    $current_max = isset($_GET['max_price']) ? $_GET['max_price'] : $max_price;
?>
    <div
        class="adminz_min_max_price"
        data-woocommerce='<?= json_encode(
                                [
                                    'step' => $step,
                                    'woocommerce_currency' => get_woocommerce_currency_symbol(),
                                    'woocommerce_currency_pos' => get_option('woocommerce_currency_pos'),
                                    'woocommerce_price_thousand_sep' => get_option('woocommerce_price_thousand_sep'),
                                    'woocommerce_price_decimal_sep' => get_option('woocommerce_price_decimal_sep'),
                                    'woocommerce_price_num_decimals' => get_option('woocommerce_price_num_decimals'),
                                ]
                            ) ?>'>
        <div class="slider-container">
            <input
                type="range"
                class="minSlider slider"
                min="0"
                max="<?= $max_price ?>"
                value="<?= $current_min ?>"
                step="<?= $step ?>"
                name="min_price">
            <input
                type="range"
                class="maxSlider slider"
                min="<?= $step ?>"
                max="<?= $max_price ?>"
                value="<?= $current_max ?>"
                step="<?= $step ?>"
                name="max_price">
        </div>
        <div class="values is-small">
            <span class="minValue"><?= wc_price($current_min) ?></span>
            -
            <span class="maxValue"><?= wc_price($current_max) ?></span>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function adminz_woo_form_field_name($atts) {
    ob_start();
?>
    <input type="text" name="s" value="<?= esc_attr($_GET['s'] ?? "") ?>"
        placeholder="<?= esc_attr(adminz_tmp('parent_state')['placeholder'] ?? "search") ?>"></input>
<?php
    return ob_get_clean();
}

function adminz_woo_form_field_submit($atts) {
    ob_start();
?>
    <!-- submit -->
    <button class="button primary" type="submit">
        <?= __("Search") ?>
    </button>
    <!-- read more -->
    <?php
    if (adminz_tmp('parent_state')['has_view_more'] ?? "") {
    ?>
        <button type="button" class="button white view_more is-smaller is-outline">
            <?= esc_attr(adminz_tmp('parent_state')['text_view_more']) ?>
        </button>
    <?php
    }
    ?>
    <!-- reset -->
    <!-- <input type="reset"> -->
<?php
    return ob_get_clean();
}

function adminz_woo_form_field_tax($atts) {
    $type = $atts['type'];
    $label = adminz_tmp('taxonomy_options')[$type];
    $label = str_replace("Tax: ", "", $label);
    ob_start();
?>
    <select name="<?= esc_attr($type) ?>" id="">
        <!-- default-->
        <option value=""> <?= __("Select") ?> <?= strtolower($label) ?> </option>
        <?php
        foreach (get_terms(['taxonomy' => $type]) as $key => $term) {
            $selected = (($_GET[$type] ?? "") == $term->slug) ? "selected" : "";
        ?>
            <option <?= esc_attr($selected) ?> value="<?= esc_attr($term->slug) ?>">
                <?= esc_attr($term->name) ?>
            </option>
        <?php
        }
        ?>
    </select>
<?php
    return ob_get_clean();
}

function adminz_woo_form_field_attribute($atts) {
    $type = $atts['type'];
    $label = adminz_tmp('attribute_options')[$type];
    $label = str_replace("Attribute: ", "", $label);
    $_field = str_replace('pa_', 'filter_', $type);
    ob_start();
?>
    <select name="<?= esc_attr($_field) ?>" id="">
        <!-- default-->
        <option value=""> <?= __("Select") ?> <?= strtolower($label) ?> </option>
        <?php
        foreach (get_terms(['taxonomy' => $type]) as $key => $term) {
            $selected = (($_GET[$_field] ?? "") == $term->slug) ? "selected" : "";
        ?>
            <option <?= esc_attr($selected) ?> value="<?= esc_attr($term->slug) ?>">
                <?= esc_attr($term->name) ?>
            </option>
        <?php
        }
        ?>
    </select>
<?php
    return ob_get_clean();
}

function adminz_woo_form_field_meta($atts) {
    $type = $atts['type'];
    $label = adminz_tmp('meta_options')[$type];
    $label = str_replace("Meta: ", "", $label);
    $_field = "meta_" . $type;
    ob_start();
?>
    <select name="<?= esc_attr($_field) ?>" id="">
        <!-- default-->
        <option value=""> <?= __("Select") ?> <?= strtolower($label) ?> </option>
        <?php
        $options = adminz_get_meta_values('product', [$type]);
        foreach ($options as $key => $option) {
            $selected = (($_GET[$_field] ?? "") == $option) ? "selected" : "";
        ?>
            <option <?= esc_attr($selected) ?> value="<?= esc_attr($option) ?>">
                <?= esc_attr($option) ?>
            </option>
        <?php
        }
        ?>
    </select>
<?php
    return ob_get_clean();
}
