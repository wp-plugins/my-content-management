<?php

add_action( 'widgets_init', create_function('', 'return register_widget("mcm_search_widget");') );

class mcm_search_widget extends WP_Widget {
	function mcm_search_widget() {
		parent::WP_Widget( false,$name=__('Custom Post Search','my-content-management') );
	}

	function widget($args, $instance) {
		extract($args);
		$post_type = $instance['mcm_widget_post_type'];		
		$search_form = mcm_search_form( $post_type );
		echo $before_widget;
		echo $search_form;
		echo $after_widget;
	}

	function form($instance) {
		global $enabled;
		$post_type = esc_attr($instance['mcm_widget_post_type']);
	?>
		<p>
		<label for="<?php echo $this->get_field_id('mcm_widget_post_type'); ?>"><?php _e('Post type to search','my-content-management'); ?></label> <select<?php echo $disabled; ?> id="<?php echo $this->get_field_id('mcm_widget_post_type'); ?>" name="<?php echo $this->get_field_name('mcm_widget_post_type'); ?>">
	<?php
		foreach( $enabled as $v ) {
			$display = ucfirst( str_replace( 'mcm_','',$v ) );
			$selected = ($post_type == $v)?' selected="selected"':'';
			echo "<option value='$v'$selected>$display</option>";
		}
	?>
		</select>
		</p>	
	<?php
	}  
}


add_action( 'widgets_init', create_function('', 'return register_widget("mcm_posts_widget");') );

class mcm_posts_widget extends WP_Widget {
	function mcm_posts_widget() {
		parent::WP_Widget( false,$name=__('Custom Post List','my-content-management') );
	}

	function widget($args, $instance) {
		extract($args);
		$post_type = $instance['mcm_posts_widget_post_type'];		
		$posts = mcm_get_show_posts( $post_type, 'list', 'all', '', -1, 'menu_order', '', false );
		echo $before_widget;
		echo $posts;
		echo $after_widget;
	}

	function form($instance) {
		global $enabled;
		$post_type = esc_attr($instance['mcm_posts_widget_post_type']);
	?>
		<p>
		<label for="<?php echo $this->get_field_id('mcm_posts_widget_post_type'); ?>"><?php _e('Post type to list','my-content-management'); ?></label> <select<?php echo $disabled; ?> id="<?php echo $this->get_field_id('mcm_posts_widget_post_type'); ?>" name="<?php echo $this->get_field_name('mcm_posts_widget_post_type'); ?>">
	<?php
		foreach( $enabled as $v ) {
			$display = ucfirst( str_replace( 'mcm_','',$v ) );
			$selected = ($post_type == $v)?' selected="selected"':'';
			echo "<option value='$v'$selected>$display</option>";
		}
	?>
		</select>
		</p>	
	<?php
	}  
}
