<?php
namespace Adminz\Helper;

class FlatsomeHeaderTransparent {
    
    public $object_id;
    public $object_type = 'WP_Post'; // print get_queried_object for more object type
    public $search = [];
    public $replace = [];

	function __construct() {

	}

    function condition(){

		if ( !$this->object_id ) {
			return;
		}

		if ( !$this->object_type ) {
			return;
		}

        // don't work on mobile php
		if ( wp_is_mobile() ) {
			return;
		}		

        $queried_object = $this->get_queried_object_id();

        if(
			$queried_object['object_id'] == $this->object_id and
			$queried_object['object_type'] == $this->object_type
            ){
            return true;
        }

        return;
    }

	function get_queried_object_id() {

		$object_id      = false;
		$queried_object = get_queried_object();
		$object_type    = $queried_object ? get_class( $queried_object ) : '';

		switch ( $object_type ) {
			case 'WP_Post':
				$object_id = $queried_object->ID ?? '';
				break;

			case 'WP_Term':
				$object_id = $queried_object->term_id ?? '';
				break;

			case 'WP_User':
				$object_id = $queried_object->ID ?? '';
				break;

			case 'WP_Post_Type':
				$object_id = $queried_object->name ?? '';
				break;

			case 'WP_Taxonomy':
				$object_id = $queried_object->name ?? '';
				break;

			default:
				$object_id = false;
				break;
		}

		return [ 
			'object_id'   => $object_id,
			'object_type' => $object_type,
		];
	}

	function init(){
		add_action( 'flatsome_after_body_open', function () {

			if(!$this->condition()){
                return;
            }

			add_action( 'flatsome_before_header', function () {
				ob_start();
			} );

			add_action( 'flatsome_after_header', function () {
				echo str_replace(
					$this->search,
                    $this->replace,
					ob_get_clean()
				);
			} );
		} );
    }
}