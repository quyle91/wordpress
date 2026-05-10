<?php
function d2c_widget_reset() {
    $name                    = __FUNCTION__;
    $___                     = new \Adminz\Helper\FlatsomeELement;
    $___->shortcode_name     = $name;
    $___->shortcode_title    = ucfirst(str_replace('_', ' ', $name));
    $___->shortcode_icon     = 'text';
    $___->options            = [
        // 'post_parent' => [
        //     'type'    => 'textfield',
        //     'heading' => 'Parent Id',
        // ],
    ];
    $___->shortcode_callback = function ($atts, $content = null) use ($name) {
        extract(shortcode_atts(array(
            'class'      => '',
            'visibility' => '',
            // 'post_parent'      => get_the_ID(),
        ), $atts));

        $classes = [$name];
        if (!empty($class)) $classes[] = $class;
        if (!empty($visibility)) $classes[] = $visibility;


        ob_start();
        echo "<div class='" . implode(' ', $classes) . "'>";

        // code here 
        $page_shop_id = wc_get_page_id('shop');
        $link = get_permalink($page_shop_id);
        echo '<ul>';
        echo '[button text="Clear All" color="white" style="outline" icon="fresh" icon_pos="left" link="' . $link . '"]';
        echo '</ul>';

        echo '</div>';
        return do_shortcode(ob_get_clean());
    };
    // echo "<pre>"; print_r($___); echo "</pre>"; die;
    $___->general_element();
}