<?php
namespace Adminz\Helper;

class Endpoint {
	public $endpoints = [
		// [
		// 	'slug' => 'abc',
		// 	'title' => 'ABC',
		// 	'role' => ['administrator']
		// ],
		// [
		// 	'slug' => 'abc1',
		// 	'title' => 'ABC1',
		// 	'role' => ['administrator']
		// ]
	];

	function __construct() {
		
	}

	function init(){
		foreach ( $this->endpoints as $endpoint ) {
			// register
			add_action( 'init', function () use ($endpoint) {
				if(!$this->check_user_has_endpoint($endpoint['role'])){
					return;
				}
				$slug = $endpoint['slug'];
				add_rewrite_rule( '^' . $slug . '/?$', 'index.php?' . $slug . '=1', 'top' );
				add_rewrite_tag( '%' . $slug . '%', '([^&]+)' );
			} );

			// add to query_vars
			add_filter( 'query_vars', function ($vars) use ($endpoint) {
				if ( !$this->check_user_has_endpoint( $endpoint['role'] ) ) {
					return $vars;
				}
				$slug   = $endpoint['slug'];
				$vars[] = $slug;
				return $vars;
			} );

			// echo
			add_action( 'template_redirect', function () use ($endpoint) {
				if ( !$this->check_user_has_endpoint( $endpoint['role'] ) ) {
					return;
				}
				global $wp_query;
				$slug       = $endpoint['slug'];

				if ( isset( $wp_query->query_vars[ $slug ] ) ) {
					
					// body class 
					add_filter('body_class', function() use($endpoint){
						$class[] = 'adminz_endpoint';
						$class[] = $endpoint['slug'];
						return $class;
					});

					do_action( "adminz_endpoint_before", $endpoint );
					
					get_header();
					do_action( "adminz_endpoint", $endpoint );
					get_footer();

					do_action( "adminz_endpoint_after", $endpoint );
					exit;
				}
			} );
		}

		if ( current_user_can( 'administrator' ) and isset( $_GET['flush_rewrite_rules'] ) ) {
			flush_rewrite_rules();
		}
	}
	
	function check_user_has_endpoint($roles){
		// not login
		if(!is_user_logged_in()){
			return;
		}

		// check role
		$current_user  = wp_get_current_user();
		$user_roles    = $current_user->roles;
		return count(array_intersect( $user_roles, $roles ));
	}
}