<?php
namespace Adminz\Helper;

class FlatsomeELement {
	public $shortcode_name;
	public $shortcode_title;
	public $shortcode_type;
	public $shortcode_info;
	public $shortcode_allow;
	public $shortcode_compile;
	public $shortcode_overlay;
	public $shortcode_icon = 'icon_box';
	public $shortcode_template;
	public $shortcode_scripts;
	public $shortcode_presets;
	public $shortcode_message;
	public $shortcode_tool;
	public $shortcode_wrap;
	public $shortcode_toolbar;
	public $shortcode_children;
	public $shortcode_inline;
	public $shortcode_tools;
	public $shortcode_nested;
	public $shortcode_resize;
	public $shortcode_callback;
	public $options = [];

	function __construct() {

	}

	function general_element() {

		if ( !$this->shortcode_name ) {
			echo __( 'Missing shortcode name' );
			return;
		}

		if ( !$this->shortcode_title ) {
			echo __( 'Missing shortcode title' );
			return;
		}

        $func = function(){
            $atts              = [];
            $atts['category']  = ADMINZ_NAME;
            $atts['thumbnail'] = get_template_directory_uri() . '/inc/builder/shortcodes/thumbnails/' . $this->shortcode_icon . '.svg';

            // Get all properties of the current object
            $properties = get_object_vars($this);
            foreach ($properties as $property => $value) {
                if ($value !== null) {
                    if (str_starts_with($property, 'shortcode_')) {
                        $atts[substr($property, 10)] = $value;
                    }
                }
            }

            if ($this->options) {
                if (is_callable($this->options)) {
                    $atts['options'] = call_user_func($this->options);
                } else {
                    $atts['options'] = $this->options;
                }

                // advanced 
                if (!isset($atts['options']['advanced_options'])) {
                    $atts['options']['advanced_options'] = require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php');
                }
            }

            // test
            // if($this->shortcode_name == 'adminz_woo_search'){
            // 	echo "<pre>"; var_dump($this); echo "</pre>";
            // 	echo "<pre>"; print_r($atts); echo "</pre>";
            // 	die;
            // }

            $atts['name'] = $this->shortcode_title;
            add_ux_builder_shortcode($this->shortcode_name, $atts);
        };

        if(did_action('ux_builder_setup')) {
            $func();
        }else{
            add_action('ux_builder_setup', $func);
        }

		if ( $this->shortcode_callback ) {
			add_shortcode( $this->shortcode_name, $this->shortcode_callback );
		}
	}
}

/* 

$___                     = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name     = 'adminz_icon';
$___->shortcode_title    = 'Icon';
$___->shortcode_icon     = 'text';
$___->options            = [ 
	//
];
$___->shortcode_callback = function ($atts, $content=null) {
	// 
};
$___->general_element();

 */