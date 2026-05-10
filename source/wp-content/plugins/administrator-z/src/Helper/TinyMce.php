<?php 
namespace Adminz\Helper;

class TinyMce{
	function __construct() { }
	function add_extra($field){
		add_filter( "mce_buttons", function ($buttons) use ($field) {
			$buttons[] = $field;
			return $buttons;
		}, 99999 );

		add_filter( 'mce_external_plugins', function ($plugins) use ($field) {
			if(file_exists( ADMINZ_DIR . 'includes/tinymce-plugins/' . $field . '/plugin.min.js')){
				$plugins[ $field ] = ADMINZ_DIR_URL . 'includes/tinymce-plugins/' . $field . '/plugin.min.js';
			}
			return $plugins;
		} );
	}
	
}