<?php
namespace Adminz\Helper;

class WooAccountMenu {
    public $arr_add = [
        // [
        // 'name'=> 'groupbuying',
        // 'label'=> 'GroupBuyingZ',
        // 'callback'=> 'function_name'
        // 'index' => 10
        // 'classes' => 'xxx' // maybe array
        // 'is_menu_item' => true, // set false to only endpoint
        // ]
    ];

    public $arr_remove = [
        // 'dashboard',
        // 'orders',
        // 'downloads',
        // 'edit-address',
    ];

    public $arr_change_label = [
        // 'dashboard'=> 'Hồ sơ'
    ];

    // only dashicons
    public $icons = [
        // 'dashboard' => '\f102'
    ];

    function __construct() {
    }

    function init() {
        

        // make sure in init action hook
        if (did_action('init')) {
            $this->run();
        }else{
            add_action('init', function(){
                $this->run();
            });
        }
        
        if ( current_user_can( 'administrator' ) and isset( $_GET['flush_rewrite_rules'] ) ) {
            flush_rewrite_rules();
        }
    }

    function run(){
        $this->add_nav_item();
        $this->remove_nav_item();
        $this->change_nav_item();
        $this->custom_icons();
    }

    function custom_icons(){
        add_action( 'wp_head', function(){
            if ( !is_account_page() ) {
                return;
            }
            wp_enqueue_style( 'dashicons' );
            // echo "<pre>"; print_r($this->icons); echo "</pre>"; die;
            ?>
            <style type="text/css">
                <?php 
                    foreach ((array)$this->icons as $nav => $dashicon) {
                        ?>
                        .woocommerce-MyAccount-navigation-link--<?= esc_attr($nav) ?> a::before{
                            content: "<?= esc_attr( $dashicon ) ?>";
                            font-family: dashicons;
                        }
                        <?php
                    }
                ?>
            </style>
            <?php
        } );
    }

    function add_nav_item() {
        if ( empty( $this->arr_add ) or !is_array( $this->arr_add ) ) return;
        foreach ( $this->arr_add as $key => $item ) {
            
            // wp rewrite url
            add_rewrite_endpoint( $item['name'], EP_ROOT | EP_PAGES );

            // add function call back
            add_action( 'woocommerce_account_' . $item['name'] . '_endpoint', function () use ($item) {
                $callback = array_slice( $item['callback'], 0, 2 );
                $params   = array_slice( $item['callback'], 2 );
                call_user_func_array( $callback, $params );
                // call_user_func( $item['callback'] );
            } );

            if( !isset( $item['is_menu_item'] ) or ( $item['is_menu_item'] )){
                // add menu item
                add_filter( 'woocommerce_account_menu_items', function ($return) use ($item) {
                    // nếu set index thì chèn vào vị trí index
                    if ( isset( $item['index'] ) ) {
                        $firstPart  = array_slice( $return, 0, (int) $item['index'], true );
                        $secondPart = array_slice( $return, (int) $item['index'], null, true );
                        $return     = $firstPart + [ $item['name'] => $item['label'] ] + $secondPart;
                    } else {
                        $return[ $item['name'] ] = $item['label'];
                    }
                    return $return;
                } );

                // add menu item class
                add_filter( 'woocommerce_account_menu_item_classes', function ($classes, $endpoint) use ($item) {
                    if ( $item['name'] == $endpoint ) {
                        if ( isset( $item['classes'] ) and !empty( $item['classes'] ) ) {
                            // make sure array
                            $_classes = $item['classes'];
                            $_classes = is_string( $_classes ) ? explode( " ", $_classes ) : $_classes;
                            $_classes = (array) $_classes;
                            $classes  = array_merge( $classes, $_classes );
                        }
                    }
                    return $classes;
                }, 10, 2 );
            }
        }
    }

    function remove_nav_item() {
        if ( empty( $this->arr_remove ) or !is_array( $this->arr_remove ) ) return;

        add_filter( 'woocommerce_account_menu_items', function ($items) {
            if ( !empty ( $this->arr_remove ) and is_array( $this->arr_remove ) ) {
                foreach ( $this->arr_remove as $key => $value ) {
                    if ( isset ( $items[ $value ] ) ) {
                        unset ( $items[ $value ] );
                    }
                }
            }
            return $items;
        } );
    }

    function change_nav_item() {
        if ( empty( $this->arr_change_label ) or !is_array( $this->arr_change_label ) ) return;

        add_filter( 'woocommerce_account_menu_items', function ($items) {
            if ( !empty ( $this->arr_change_label ) and is_array( $this->arr_change_label ) ) {
                foreach ( $this->arr_change_label as $key => $value ) {
                    if ( isset ( $items[ $key ] ) ) {
                        $items[ $key ] = $value;
                    }
                }
            }
            return $items;
        } );
    }
}