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

	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		$instance['mcm_widget_post_type'] = strip_tags($new_instance['mcm_widget_post_type']);
		return $instance;		
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
		$display = ( $instance['display'] == '' )?'list':$instance['display'];
		$count = ( $instance['count'] == '' )?-1:(int) $instance['count'];
		$order = ( $instance['order'] == '' )?'menu_order':$instance['order'];
		$direction = ( $instance['direction'] == '' )?'asc':$instance['direction'];
		//  				$type, $display, $tax, $term, $count, $order, $direction, $meta_key, $template, $offset, $id
		$custom = mcm_get_show_posts( $post_type, $display, 'all', '', $count, $order, $direction, '', '', false, false );
		echo $before_widget;
		echo $custom;
		echo $after_widget;
	}

	function form($instance) {
		global $enabled;
		$post_type = esc_attr($instance['mcm_posts_widget_post_type']);
		$display = esc_attr($instance['display']);
		$count = (int) $instance['count'];
		$direction = esc_attr($instance['direction']);		
		$order = esc_attr($instance['order']);
	?>
		<p>
		<label for="<?php echo $this->get_field_id('mcm_posts_widget_post_type'); ?>"><?php _e('Post type to list','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('mcm_posts_widget_post_type'); ?>" name="<?php echo $this->get_field_name('mcm_posts_widget_post_type'); ?>">
	<?php
		foreach( $enabled as $v ) {
			$display = ucfirst( str_replace( 'mcm_','',$v ) );
			$selected = ($post_type == $v)?' selected="selected"':'';
			echo "<option value='$v'$selected>$display</option>";
		}
	?>
		</select>
		</p>	
		<p>
		<label for="<?php echo $this->get_field_id('display'); ?>"><?php _e('Template','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>">
		<option value='list'<?php echo ($display == 'list')?' selected="selected"':''; ?>><?php _e('List','my-content-management'); ?></option>
		<option value='excerpt'<?php echo ($display == 'excerpt')?' selected="selected"':''; ?>><?php _e('Excerpt','my-content-management'); ?></option>
		<option value='full'<?php echo ($display == 'full')?' selected="selected"':''; ?>><?php _e('Full','my-content-management'); ?></option>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Display order','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
		<option value='menu_order'<?php echo ($order == 'menu_order')?' selected="selected"':''; ?>><?php _e('Menu Order','my-content-management'); ?></option>
		<option value='none'<?php echo ($order == 'none')?' selected="selected"':''; ?>><?php _e('None','my-content-management'); ?></option>
		<option value='ID'<?php echo ($order == 'ID')?' selected="selected"':''; ?>><?php _e('Post ID','my-content-management'); ?></option>
		<option value='author'<?php echo ($order == 'author')?' selected="selected"':''; ?>><?php _e('Author','my-content-management'); ?></option>
		<option value='title'<?php echo ($order == 'title')?' selected="selected"':''; ?>><?php _e('Post Title','my-content-management'); ?></option>
		<option value='date'<?php echo ($order == 'date')?' selected="selected"':''; ?>><?php _e('Post Date','my-content-management'); ?></option>
		<option value='modified'<?php echo ($order == 'modified')?' selected="selected"':''; ?>><?php _e('Post Modified Date','my-content-management'); ?></option>
		<option value='rand'<?php echo ($order == 'rand')?' selected="selected"':''; ?>><?php _e('Random','my-content-management'); ?></option>
		<option value='comment_count'<?php echo ($order == 'comment_count')?' selected="selected"':''; ?>><?php _e('Number of comments','my-content-management'); ?></option>	
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number to display','my-content-management'); ?></label> <input type="text" size="3" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" value="<?php echo $count; ?>" /><br /><span>(<?php _e('-1 to display all posts','my-content-management'); ?>)</span>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('direction'); ?>"><?php _e('Order direction','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('direction'); ?>" name="<?php echo $this->get_field_name('direction'); ?>">
		<option value='modified'<?php echo ($direction == 'asc')?' selected="selected"':''; ?>><?php _e('Ascending (A-Z)','my-content-management'); ?></option>
		<option value='rand'<?php echo ($direction == 'desc')?' selected="selected"':''; ?>><?php _e('Descending (Z-A)','my-content-management'); ?></option>
		</select>
		</p>		
	<?php
	}

	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		$instance['mcm_posts_widget_post_type'] = strip_tags($new_instance['mcm_posts_widget_post_type']);
		$instance['display'] = strip_tags($new_instance['display']);
		$instance['order'] = strip_tags($new_instance['order']);
		$instance['direction'] = strip_tags($new_instance['direction']);
		$instance['count'] = ( $new_instance['count']== '' )?-1:(int) $new_instance['count'];
		return $instance;		
	}
}
