<?php
namespace Adminz\Helper;
class Category {

	function __construct() {
		//
	}

	function init($tax){
		//
		if(!$tax){
			return;
		}
		$this->taxonomy_description( $tax );
	}

	function taxonomy_description( $taxonomy ) {
		// enqueue
		$this->enqueue();

		// filters
		add_filter( $taxonomy . '_edit_form_fields', function ($tag) {
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="description"><?php _e( 'Description' ); ?></label></th>
				<td>
					<?php
					$settings = array( 'wpautop' => true, 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => '15', 'textarea_name' => 'description' );
					wp_editor( html_entity_decode( $tag->description, ENT_QUOTES, 'UTF-8' ), 'description1', $settings ); ?>
					<br />
					<span
						class="description"><?php _e( 'The description is not prominent by default; however, some themes may show it.' ); ?></span>
				</td>
			</tr>
			<style type="text/css">
				.term-description-wrap {
					display: none;
				}
			</style>
			<?php
		} );

		add_action( 'admin_enqueue_scripts', function () use ($taxonomy) {
			$screen = get_current_screen();

			if ( $screen && $screen->taxonomy === $taxonomy ) {
				echo <<<HTML
				<style type="text/css">
					.wp-list-table img {
						max-width: 100%;
						height: auto;
					}
				</style>
				HTML;
			}
		} );
	}

	function enqueue() {
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );
		add_filter( 'term_description', 'do_shortcode' );
	}
}