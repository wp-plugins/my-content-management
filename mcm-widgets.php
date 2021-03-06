<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'widgets_init', create_function('', 'return register_widget("mcm_search_widget");') );

class mcm_search_widget extends WP_Widget {
	function __construct() {
		parent::__construct( false,$name=__('Custom Post Search','my-content-management') );
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
		$post_type = isset( $instance['mcm_widget_post_type'] ) ? esc_attr( $instance['mcm_widget_post_type']) : '';
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title']) : '';
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
	function __construct() {
		parent::__construct( false,$name=__('Custom Post List','my-content-management') );
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
		$args = array( 
					'type' => $post_type, 
					'display' => $display, 
					'taxonomy' => $taxonomy, 
					'term' => $term, 
					'count' => $count, 
					'order' => $order, 
					'direction' => $direction, 
					'template' => $template, 
					'custom_wrapper' => 'div', 
					'operator' => 'IN'
				);
		$args = apply_filters( 'mcm_custom_posts_widget_args', $args );
		$custom = mcm_get_show_posts( $args );
		echo $before_widget;
		echo $widget_title;
		echo $wrapper;
		echo $custom;
		echo $unwrapper;
		echo $after_widget;
	}

	function form($instance) {
		$post_type = isset( $instance['mcm_posts_widget_post_type'] ) ? esc_attr( $instance['mcm_posts_widget_post_type']) : '';
		$display = isset( $instance['display'] ) ? esc_attr( $instance['display']) : '';
		$count = isset( $instance['count'] ) ? (int) $instance['count'] : -1;
		$direction = isset( $instance['direction'] ) ? esc_attr( $instance['direction']) : 'asc';
		$order = isset( $instance['order'] )? esc_attr( $instance['order']) : '';
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title']) : '';
		$term = isset($instance['term']) ? esc_attr( $instance['term']) : '';
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
			<option value='list'<?php selected( $display, 'list' ); ?>><?php _e('List','my-content-management'); ?></option>
			<option value='excerpt'<?php selected( $display, 'excerpt' ); ?>><?php _e('Excerpt','my-content-management'); ?></option>
			<option value='full'<?php selected( $display, 'full' ); ?>><?php _e('Full','my-content-management'); ?></option>
			<option value='custom'<?php selected( $display, 'custom' ); ?>><?php _e('Custom','my-content-management'); ?></option>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Display order','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
			<option value='menu_order'<?php selected( $order, 'menu_order' ); ?>><?php _e('Menu Order','my-content-management'); ?></option>
			<option value='none'<?php selected( $order, 'none' ); ?>><?php _e('None','my-content-management'); ?></option>
			<option value='ID'<?php selected( $order, 'id' ); ?>><?php _e('Post ID','my-content-management'); ?></option>
			<option value='author'<?php selected( $order, 'author' ); ?>><?php _e('Author','my-content-management'); ?></option>
			<option value='title'<?php selected( $order, 'title' ); ?>><?php _e('Post Title','my-content-management'); ?></option>
			<option value='date'<?php selected( $order, 'date' ); ?>><?php _e('Post Date','my-content-management'); ?></option>
			<option value='modified'<?php selected( $order, 'modified' ); ?>><?php _e('Post Modified Date','my-content-management'); ?></option>
			<option value='rand'<?php selected( $order, 'rand' ); ?>><?php _e('Random','my-content-management'); ?></option>
			<option value='comment_count'<?php selected( $order, 'comment_count' ); ?>><?php _e('Number of comments','my-content-management'); ?></option>	
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

add_action( 'widgets_init', create_function('', 'return register_widget("mcm_meta_widget");') );
class mcm_meta_widget extends WP_Widget {
	function __construct() {
		parent::__construct( false,$name=__('Custom Post Data','my-content-management'), array( 'description' => __( 'Widget displaying data entered in a specific fieldset.', 'my-content-management' ) ) );
	}

	function widget($args, $instance) {
		global $mcm_extras;
		$the_title = apply_filters('widget_title', $instance['title']);
		$fieldset_name  = $instance['fieldset'];
		$display   = $instance['display'];
		
		$widget_title = empty( $the_title ) ? '' : $the_title;
		$widget_title = ($widget_title!='') ? $args['before_title'] . $widget_title . $args['after_title'] : '';		

		$left_column  = isset( $instance['left_column'] ) ? $instance['left_column'] : __( 'Label', 'my-content-management' );
		$right_column = isset( $instance['right_column'] ) ? $instance['right_column'] : __( 'Value', 'my-content-management' );
		
		$fieldset = mcm_get_fieldset_values( $fieldset_name );
		$fieldset = mcm_format_fieldset( $fieldset, $display, array( 'label'=>$left_column, 'values'=>$right_column ), $fieldset_name );
		
		if ( $fieldset ) {
			echo $args['before_widget'];
			echo $widget_title;
			echo $fieldset;
			echo $args['after_widget'];
		}
	}

	function form($instance) {
		global $mcm_extras;
		$types = array_keys( $mcm_extras );		
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$fieldset = isset( $instance['fieldset'] ) ? esc_attr( $instance['fieldset'] ) : '';
		$display = isset( $instance['display'] ) ? esc_attr( $instance['display'] ) : '';
		$left_column = isset( $instance['left_column'] ) ? esc_attr( $instance['left_column'] ) : '';
		$right_column = isset( $instance['right_column'] ) ? esc_attr( $instance['right_column'] ) : '';
	?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','my-content-management'); ?>:</label><br />
		<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>"/>
		</p>	
		<p>
		<label for="<?php echo $this->get_field_id('fieldset'); ?>"><?php _e('Fieldset to display','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('fieldset'); ?>" name="<?php echo $this->get_field_name('fieldset'); ?>">
		<?php
			$fieldsets = '';
			foreach ( $types as $v ) {
				$name = esc_attr( $v );
				$label = esc_html( $v );
				$selected = ( $fieldset == $name )?' selected="selected"' : '';			
				$fieldsets .= "<option value='$name'$selected>$label</option>\n";
			}
			echo $fieldsets;
		?>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('display'); ?>"><?php _e('Display style','my-content-management'); ?></label> <select id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>">
			<option value='list'<?php selected( $display, 'list' ); ?>><?php _e( 'List', 'my-content-management' ); ?></option>
			<option value='table'<?php selected( $display, 'table' ); ?>><?php _e( 'Table', 'my-content-management' ); ?></option>
			<option value='custom'<?php selected( $display, 'custom' ); ?>><?php _e( 'Custom', 'my-content-management' ); ?></option>
		</select>
		</p>
		<?php if ( $display == 'table' ) { ?>
			<p>
			<label for="<?php echo $this->get_field_id('left_column'); ?>"><?php _e('Left column header','my-content-management'); ?>:</label><br />
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('left_column'); ?>" name="<?php echo $this->get_field_name('left_column'); ?>" value="<?php echo $left_column; ?>"/>
			</p>		
			<p>
			<label for="<?php echo $this->get_field_id('right_column'); ?>"><?php _e('Right column header','my-content-management'); ?>:</label><br />
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('right_column'); ?>" name="<?php echo $this->get_field_name('right_column'); ?>" value="<?php echo $right_column; ?>"/>
			</p>		
		<?php } ?>
		</fieldset>
	<?php
	}

	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		$instance['fieldset'] = strip_tags( $new_instance['fieldset'] );
		$instance['display'] = strip_tags( $new_instance['display'] );
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['left_column'] = isset( $new_instance['left_column'] ) ? strip_tags( $new_instance['left_column'] ) : '';
		$instance['right_column'] = isset( $new_instance['right_column'] ) ? strip_tags( $new_instance['right_column'] ) : '';
		return $instance;		
	}
}

/**
 * Using the name of a fieldset, get all values of that fieldset from the current post.
 * 
 */
function mcm_get_fieldset_values( $fieldset, $id = false ) {
	if ( !$id ) {
		if ( !is_singular() ) {
			return '';
		}
		global $post;
		$id = $post->ID;
	}
	$options = get_option( 'mcm_options' );
	$fields  = $options['fields'][$fieldset];
	foreach ( $fields as $group ) {
		$key = $group[0];
		if ( $group[4] != '' ) {
			$value = get_post_meta( $id, $key );
		} else {
			$value = get_post_meta( $id, $key, true );
		}
		$values[$key] = array( 'label'=>$group[1], 'value'=>$value, 'type'=>$group[3] );
	}
	
	return $values;
}

function mcm_format_fieldset( $values, $display, $headers, $fieldset ) {
	$label = $headers['label'];
	$value = $headers['values'];
	$list = '';
	if ( $display == 'table' ) {
		$before = '<table class="mcm_display_fieldset">
					<thead>
						<tr>
							<th scope="col">' . $label . '</th>
							<th scope="col">' . $value . '</th>
						</tr>
					</thead>
					<tbody>';
	} else {
		$before = '<ul class="mcm_display_fieldset">';	
	}
	foreach ( $values as $value ) {
		if ( !empty( $value['value'] ) ) {
			$label = apply_filters( 'mcm_widget_data_label', esc_html( stripslashes( $value['label'] ) ), $value, $display, $fieldset );
			$output = apply_filters( 'mcm_widget_data_value', mcm_format_value( $value['value'], $value['type'] ), $value, $display, $fieldset );
			if ( $display == 'table' ) {
				$list .= '<tr><td class="mcm_field_label">' . $label . '</td><td class="mcm_field_value">' . esc_html( $output ) . '</td></tr>';
			} else {
				$list .= '<li><span class="mcm_field_label">' . $label . '</span> <span class="mcm_field_value">' . esc_html( $output ) . '</span></li>';
			}
		}
	}
	if ( $display == 'table' ) {
		$after = '</tbody>
			</table>';
	} else {
		$after = '</ul>';		
	}
	if ( $display == 'custom' ) {
		// work out a custom mechanism;
		$list = apply_filters( 'mcm_custom_widget_data', $values );
	}
	
	if ( $list ) {
		$list = $before . $list . $after;
	}
	
	return $list;
}

function mcm_format_value( $value, $type ) {
	switch( $type ) {
		case 'text' : $return = stripslashes( $value ); break;
		default: $return = $value;
	}
	
	return $return;
}

/* TODO:

 - Complete format value switch
 - handle repeating values
 - set up custom template field that can be used with draw_template
 
 */