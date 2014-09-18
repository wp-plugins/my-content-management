<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'widgets_init', create_function('', 'return register_widget("mcm_search_widget");') );

class mcm_search_widget extends WP_Widget {
	function mcm_search_widget() {
		parent::WP_Widget( false,$name=__('Custom Post Search','my-content-management') );
	}

	function widget($args, $instance) {
		extract($args);
		$the_title = apply_filters('widget_title',$instance['title']);
		$widget_title = empty($the_title) ? '' : $the_title;
		$widget_title = ($widget_title!='') ? $before_title . $widget_title . $after_title : '';		
		$post_type = $instance['mcm_widget_post_type'];		
		$search_form = mcm_search_form( $post_type );
		echo $before_widget;
		echo $widget_title;
		echo $search_form;
		echo $after_widget;
	}

	function form($instance) {
		$post_type = isset( $instance['mcm_widget_post_type'] ) ? esc_attr($instance['mcm_widget_post_type']) : '';
		$title = isset( $instance['title'] ) ? esc_attr($instance['title']) : '';
	?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','my-content-management'); ?>:</label><br />
		<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>"/>
		</p>	
		<p>
		<label for="<?php echo $this->get_field_id('mcm_widget_post_type'); ?>"><?php _e('Post type to search','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('mcm_widget_post_type'); ?>" name="<?php echo $this->get_field_name('mcm_widget_post_type'); ?>">
	<?php
		$posts = get_post_types( array( 'public'=>'true' ) ,'object' );
		$post_types = '';
		foreach ( $posts as $v ) {
			$name = $v->name;
			$label = $v->labels->name;
			$selected = ($post_type == $name)?' selected="selected"':'';			
			$post_types .= "<option value='$name'$selected>$label</option>\n";
		}
		echo $post_types;		
	?>
		</select>
		</p>	
	<?php
	}

	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		$instance['mcm_widget_post_type'] = strip_tags($new_instance['mcm_widget_post_type']);
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;		
	}	
}


add_action( 'widgets_init', create_function('', 'return register_widget("mcm_posts_widget");') );

class mcm_posts_widget extends WP_Widget {
	function mcm_posts_widget() {
		parent::WP_Widget( false,$name=__('Custom Post List','my-content-management') );
	}

	function widget($args, $instance) {
		global $mcm_types;
		$types = array_keys( $mcm_types );
		extract($args);
		$the_title = apply_filters('widget_title',$instance['title']);
		$widget_title = empty($the_title) ? '' : $the_title;
		$widget_title = ($widget_title!='') ? $before_title . $widget_title . $after_title : '';		
		$post_type = $instance['mcm_posts_widget_post_type'];
		if ( in_array( $post_type, $types ) ) {
			$display = ( $instance['display'] == '' ) ? 'list' : $instance['display'];
		} else {
			$display = 'custom';
		}
		$count = ( $instance['count'] == '' )?-1:(int) $instance['count'];
		$template = ( $instance['template'] == '' ) ? '' : $instance['template'];
		$wrapper = ( $instance['wrapper'] == '' ) ? '' : $instance['wrapper'];
		$order = ( $instance['order'] == '' )?'menu_order':$instance['order'];
		$direction = ( $instance['direction'] == '' )?'asc':$instance['direction'];
		$term = ( !isset( $instance['term'] ) )?'':$instance['term'];
		$taxonomy = str_replace( 'mcm_','mcm_category_',$post_type );
		if ( $post_type == 'avl-video' ) { $taxonomy = 'avl_category_avl-video'; }
		if ( $post_type == 'post' ) { $taxonomy = 'category'; }
		if ( $post_type == 'page' ) { $taxonomy = ''; }
		if ( $display == 'custom' ) {
			$wrapper = "<ul>";
			$template = "<li>{link_title}</li>";
			$unwrapper = "</ul>";
		} else {
			$template = $wrapper = $unwrapper = '';
		}
		$custom = mcm_get_show_posts( $post_type, $display, $taxonomy, $term, $count, $order, $direction, '', $template, false, false, false, 'div', '','IN' );
		echo $before_widget;
		echo $widget_title;
		echo $wrapper;
		echo $custom;
		echo $unwrapper;
		echo $after_widget;
	}

	function form($instance) {
		$post_type = isset( $instance['mcm_posts_widget_post_type'] ) ? esc_attr($instance['mcm_posts_widget_post_type']) : '';
		$display = isset( $instance['display'] ) ? esc_attr($instance['display']) : '';
		$count = isset( $instance['count'] ) ? (int) $instance['count'] : -1;
		$direction = isset( $instance['direction'] ) ? esc_attr($instance['direction']) : 'asc';
		$order = isset( $instance['order'] )? esc_attr($instance['order']) : '';
		$title = isset( $instance['title'] ) ? esc_attr($instance['title']) : '';
		$term = isset($instance['term']) ? esc_attr($instance['term']) : '';
		$template = isset( $instance['template'] ) ? esc_attr( $instance['template'] ) : '';
		$wrapper = isset( $instance['wrapper'] ) ? esc_attr( $instance['wrapper'] ) : '';
	?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','my-content-management'); ?>:</label><br />
		<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>"/>
		</p>	
		<p>
		<label for="<?php echo $this->get_field_id('mcm_posts_widget_post_type'); ?>"><?php _e('Post type to list','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('mcm_posts_widget_post_type'); ?>" name="<?php echo $this->get_field_name('mcm_posts_widget_post_type'); ?>">
	<?php
		$posts = get_post_types( array( 'public'=>'true' ) ,'object' );
		$post_types = '';
		foreach ( $posts as $v ) {
			$name = $v->name;
			$label = $v->labels->name;
			$selected = ( $post_type == $name )?' selected="selected"' : '';			
			$post_types .= "<option value='$name'$selected>$label</option>\n";
		}
		echo $post_types;
	?>
		</select>
		</p>	
		<p>
		<label for="<?php echo $this->get_field_id('display'); ?>"><?php _e('Template','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>">
			<option value='list'<?php echo ($display == 'list')?' selected="selected"':''; ?>><?php _e('List','my-content-management'); ?></option>
			<option value='excerpt'<?php echo ($display == 'excerpt')?' selected="selected"':''; ?>><?php _e('Excerpt','my-content-management'); ?></option>
			<option value='full'<?php echo ($display == 'full')?' selected="selected"':''; ?>><?php _e('Full','my-content-management'); ?></option>
			<option value='custom'<?php echo ($display == 'custom')?' selected="selected"':''; ?>><?php _e('Custom','my-content-management'); ?></option>
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
		<option value='asc'<?php echo ($direction == 'asc')?' selected="selected"':''; ?>><?php _e('Ascending (A-Z)','my-content-management'); ?></option>
		<option value='desc'<?php echo ($direction == 'desc')?' selected="selected"':''; ?>><?php _e('Descending (Z-A)','my-content-management'); ?></option>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('term'); ?>"><?php _e('Category (single term or comma-separated list)','my-content-management'); ?>:</label><br />
		<input class="widefat" type="text" id="<?php echo $this->get_field_id('term'); ?>" name="<?php echo $this->get_field_name('term'); ?>" value="<?php echo $term; ?>"/>
		</p>
		<fieldset>
		<legend><?php _e( 'Custom Templating','my-content-management' ); ?></legend>
		<p>
		<label for="<?php echo $this->get_field_id('wrapper'); ?>"><?php _e('Wrapper','my-content-management'); ?>:</label><br />
		<select id="<?php echo $this->get_field_id('wrapper'); ?>" name="<?php echo $this->get_field_name('wrapper'); ?>">
			<option value=''><?php _e( 'None','my-content-management' ); ?></option>
			<option value='ul'><?php _e( 'Unordered list','my-content-management' ); ?></option>
			<option value='ol'><?php _e( 'Ordered list','my-content-management' ); ?></option>
			<option value='div'><?php _e( 'Div','my-content-management' ); ?></option>			
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('template'); ?>"><?php _e( 'Template','my-content-management' ); ?>:</label><br />
		<textarea class="widefat" id="<?php echo $this->get_field_id('template'); ?>" cols='40' rows='4' name="<?php echo $this->get_field_name('template'); ?>"><?php echo $template; ?></textarea>
		</p>
		</fieldset>
	<?php
	}

	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		$instance['mcm_posts_widget_post_type'] = strip_tags( $new_instance['mcm_posts_widget_post_type'] );
		$instance['display'] = strip_tags( $new_instance['display'] );
		$instance['order'] = strip_tags( $new_instance['order'] );
		$instance['direction'] = strip_tags( $new_instance['direction'] );
		$instance['count'] = ( $new_instance['count']== '' )?-1:(int) $new_instance['count'];
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['term'] = strip_tags( $new_instance['term'] );
		$instance['wrapper'] = esc_attr( $new_instance['wrapper'] );
		$instance['template'] = $new_instance['template'];
		return $instance;		
	}
}
