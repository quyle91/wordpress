<?php 
namespace Adminz\Widget;

class Adminz_RecentPosts extends \WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'adminz_widget_recent_entries',
			'description'                 => __('Your site’s most recent Posts.'),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct('adminz_recent_posts', __('Adminz Recent Posts'), $widget_ops);
		$this->alt_option_name = 'adminz_widget_recent_entries';
	}

	public function widget($args, $instance) {
		if (!isset($args['widget_id'])) {
			$args['widget_id'] = $this->id;
		}

		$title      = (!empty($instance['title'])) ? $instance['title'] : __('Recent Posts');
		$title      = apply_filters('widget_title', $title, $instance, $this->id_base);
		$number     = (!empty($instance['number'])) ? absint($instance['number']) : 5;
		$number     = $number ? $number : 5;
		$show_date  = isset($instance['show_date']) ? $instance['show_date'] : false;
		$show_thumb = isset($instance['show_thumb']) ? $instance['show_thumb'] : false;
		$post_type  = (!empty($instance['post_type'])) ? $instance['post_type'] : 'post'; // Default to 'post'

		$r = new \WP_Query(apply_filters(
			'widget_posts_args',
			array(
				'posts_per_page'      => $number,
				'post_type'           => $post_type, // Use selected post type
				'no_found_rows'       => true,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
			),
			$instance
		));

		if (!$r->have_posts()) {
			return;
		}

		echo $args['before_widget'];

		if ($title) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$format = current_theme_supports('html5', 'navigation-widgets') ? 'html5' : 'xhtml';
		$format = apply_filters('navigation_widgets_format', $format);

		if ('html5' === $format) {
			$title      = trim(strip_tags($title));
			$aria_label = $title ? $title : __('Recent Posts');
			echo '<nav aria-label="' . esc_attr($aria_label) . '">';
		}

		echo '<ul>';
		foreach ($r->posts as $recent_post) {
			$post_title   = get_the_title($recent_post->ID);
			$title        = (!empty($post_title)) ? $post_title : __('(no title)');
			$aria_current = (get_queried_object_id() === $recent_post->ID) ? ' aria-current="page"' : '';

			echo '<li style="display: flex; gap: 10px; padding: 6px 0;">';
			if ($show_thumb && has_post_thumbnail($recent_post->ID)) {
				echo get_the_post_thumbnail($recent_post->ID, 'thumbnail', ['style' => 'width: 50px; height: 50px;']);
			}

            echo '<div>';
			echo '<a href="' . get_permalink($recent_post->ID) . '"' . $aria_current . '>' . $title . '</a>';
			if ($show_date) {
                echo '<div class="post-date">' . get_the_date('', $recent_post->ID) . '</div>';
			}
            echo '</div>';
			echo '</li>';
		}
		echo '</ul>';

		if ('html5' === $format) {
			echo '</nav>';
		}

		echo $args['after_widget'];
	}

	public function update($new_instance, $old_instance) {
		$instance              = $old_instance;
		$instance['title']     = sanitize_text_field($new_instance['title']);
		$instance['number']    = (int) $new_instance['number'];
		$instance['show_date'] = isset($new_instance['show_date']) ? (bool) $new_instance['show_date'] : false;
		$instance['show_thumb'] = isset($new_instance['show_thumb']) ? (bool) $new_instance['show_thumb'] : false;
		$instance['post_type'] = sanitize_text_field($new_instance['post_type']); // Save selected post type
		return $instance;
	}

	public function form($instance) {
		$title     = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number    = isset($instance['number']) ? absint($instance['number']) : 5;
		$show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : false;
		$show_thumb = isset($instance['show_thumb']) ? (bool) $instance['show_thumb'] : false;
		$post_type = isset($instance['post_type']) ? esc_attr($instance['post_type']) : 'post'; // Default to 'post'
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('post_type'); ?>"><?php _e('Select Post Type:'); ?></label>
			<select id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>" class="widefat">
				<option value="post" <?php selected($post_type, 'post'); ?>><?php _e('Post'); ?></option>
				<option value="page" <?php selected($post_type, 'page'); ?>><?php _e('Page'); ?></option>
				<?php 
				$post_types = get_post_types(['public' => true], 'objects');
				foreach ($post_types as $type) {
					if ($type->name !== 'post' && $type->name !== 'page') {
						echo '<option value="' . esc_attr($type->name) . '" ' . selected($post_type, $type->name, false) . '>' . esc_html($type->label) . '</option>';
					}
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>"
				name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1"
				value="<?php echo $number; ?>" size="3" />
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php checked($show_date); ?> id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" />
			<label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Display post date?'); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php checked($show_thumb); ?> id="<?php echo $this->get_field_id('show_thumb'); ?>" name="<?php echo $this->get_field_name('show_thumb'); ?>" />
			<label for="<?php echo $this->get_field_id('show_thumb'); ?>"><?php _e('Display post thumbnail?'); ?></label>
		</p>
		<?php
		// return 'noform';
		return '';
	}
}
