<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $mcm_types,$mcm_fields,$mcm_extras,$mcm_enabled,$mcm_templates,$default_mcm_types,$default_mcm_fields,$default_mcm_extras;

function mcm_posttypes() {
	global $mcm_types, $mcm_enabled;
	$types = $mcm_types; $enabled = $mcm_enabled;
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value =& $types[$key];	
			if ( is_array( $value ) && !empty( $value ) ) {
				$labels = array(
					'name' => $value[3],
					'singular_name' => $value[2],
					'add_new' => __('Add New', 'my-content-management' ),
					'add_new_item' => sprintf( __('Add New %s', 'my-content-management' ), $value[2] ),
					'edit_item' => sprintf( __('Edit %s','my-content-management' ), $value[2] ),
					'new_item' => sprintf( __('New %s','my-content-management' ), $value[2] ),
					'view_item' => sprintf( __('View  %s','my-content-management' ), $value[2] ),
					'search_items' => sprintf( __('Search %s','my-content-management' ), $value[3] ),
					'not_found' =>  sprintf( __('No %s found','my-content-management'), $value[1] ),
					'not_found_in_trash' => sprintf( __('No %s found in Trash','my-content-management' ), $value[1] ), 
					'parent_item_colon' => ''
				);
				$raw = $value[4];
				$slug = ( !isset($raw['slug']) || $raw['slug'] == '' )?$key:$raw['slug'];
				$icon = ($raw['menu_icon']==null)?plugins_url('images',__FILE__)."/$key.png":$raw['menu_icon'];
				$args = array(
					'labels' => $labels,
					'public' => $raw['public'],
					'publicly_queryable' => $raw['publicly_queryable'],
					'exclude_from_search'=> $raw['exclude_from_search'],
					'show_ui' => $raw['show_ui'],
					'show_in_menu' => $raw['show_in_menu'],
					'show_ui' => $raw['show_ui'], 
					'menu_icon' => ($icon=='')?plugins_url('images',__FILE__)."/mcm_resources.png":$icon,
					'query_var' => true,
					'rewrite' => array('slug'=>$slug,'with_front'=>false),
					'hierarchical' => $raw['hierarchical'],
					'has_archive' => true,
					'supports' => $raw['supports'],
					'map_meta_cap'=>true,
					'capability_type'=>'post', // capability type is post type
					'taxonomies'=>array( 'post_tag' )
				); 
				register_post_type($key,$args);
			}
		}
	}
}

function mcm_posttypes_messages( $messages ) {
	global $post, $post_ID, $mcm_types, $mcm_enabled;
	$types = $mcm_types; $enabled = $mcm_enabled;
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value = $types[$key];
			$messages[$key] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __('%1$s Listing updated. <a href="%2$s">View %1$s listing</a>','my-content-management'), $value[2], esc_url( get_permalink( $post_ID ) ) ),
				2 => __('Custom field updated.','my-content-management'),
				3 => __('Custom field deleted.','my-content-management'),
				4 => sprintf( __('%s listing updated.','my-content-management'), $value[2] ),
				/* translators: %s: date and time of the revision */
				5 => isset($_GET['revision']) ? sprintf( __('%1$s restored to revision from %2$ss','my-content-management'), $value[2], wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __('%1$s published. <a href="%2$s">View %3$s listing</a>','my-content-management'), $value[2], esc_url( get_permalink($post_ID) ), $value[0] ),
				7 => sprintf( __('%s listing saved.','my-content-management'), $value[2] ),
				8 => sprintf( __('%1$s listing submitted. <a target="_blank" href="%2$s">Preview %3$s listing</a>','my-content-management'), $value[2], esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ), $value[0] ),
				9 => sprintf( __('%1$s listing scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>','my-content-management'),
				  $value[2], date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ), $value[0] ),
				10 => sprintf( __('%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s listing</a>','my-content-management'), $value[2], esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ), $value[0] ),
			);
		}
	}
	return $messages;
}

function mcm_taxonomies() {
	global $mcm_types,$mcm_enabled;
	$types = $mcm_types; $enabled = $mcm_enabled;
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value =& $types[$key];
			if ( is_array( $value ) && !empty( $value ) ) {		
				$cat_key = str_replace( 'mcm_','', $key );
				register_taxonomy(
					"mcm_category_$cat_key",	// internal name = machine-readable taxonomy name
					array( $key ),	// object type = post, page, link, or custom post-type
					array(
						'hierarchical' => true,
						'label' => sprintf( __( "%s Categories", 'my-content-management' ), $value[2] ),	// the human-readable taxonomy name
						'query_var' => true,	// enable taxonomy-specific querying
						'rewrite' => array( 'slug' => "$cat_key-category" ),	// pretty permalinks for your taxonomy?
					)
				);
			}
		}
	}
}

function mcm_add_custom_boxes() {
global $mcm_fields, $mcm_extras;
$fields = $mcm_fields; $extras = $mcm_extras;
	if ( is_array($fields) ) {
		foreach ( $fields as $key=>$value ) {
			if ( isset($extras[$key]) && is_array( $extras[$key][0] ) ) {
				foreach ( $extras[$key][0] as $k ) {
					mcm_add_custom_box( array($key=>$value), $k, $extras[$key][1] );
				} 
			} else {
				if ( isset( $extras[$key] ) ) {
					if ( !empty( $extras[$key][0] ) ) {
						mcm_add_custom_box( array($key=>$value), $extras[$key][0], $extras[$key][1] );
					}
				}
			}
		}
	}
}


function mcm_add_custom_box( $fields, $post_type='post',$location='side' ) {
    if ( function_exists( 'add_meta_box' ) ) {
		$location = apply_filters( 'mcm_set_location', $location, $fields, $post_type );
		$priority = apply_filters( 'mcm_set_priority', 'default', $fields, $post_type );
        foreach ( array_keys( $fields ) as $field ) {
			$id = sanitize_title( $field );
			$field = stripslashes( $field );
			if ( apply_filters( 'mcm_filter_meta_box', true, $post_type, $id ) ) {
				add_meta_box( $id, $field, 'mcm_build_custom_box', $post_type, $location, $priority, $fields );
			}
        }
    }
}

function mcm_build_custom_box( $post, $fields ) {
	static $nonce_flag = false;
	// Run once
	$id = addslashes( $fields['title'] );
	echo "<div class='mcm_post_fields'>";
	if ( !$nonce_flag ) {
		mcm_echo_nonce();
		$nonce_flag = true;
	}
	mcm_echo_hidden($fields['args'][$id], $id );	
	// Generate box contents
	$i = 0;	
		foreach ( $fields['args'][$id] as $key => $field ) {
			if ( $key !== 'repeatable' ) {
				echo mcm_field_html( $field );
				$i++;
			}
		}
	echo "<br class='clear' /></div>";
}
// this switch statement specifies different types of meta boxes
// you can add more types if you add a case and a corresponding function
// to handle it
function mcm_field_html( $args ) {
	switch ( $args[3] ) {
		case 'textarea':
			return mcm_text_area( $args );
		case 'select':
			return mcm_select( $args );	
		case 'upload':
			return mcm_upload_field( $args );
		case 'chooser':
			return mcm_chooser_field( $args );
		case 'richtext':
			return mcm_rich_text_area( $args );
		default:
			return mcm_text_field( $args, $args[3] );
	}
}

function mcm_upload_field( $args ) {
	global $post;
	$args[1] = stripslashes( $args[1] );
	$description = stripslashes( $args[2] );
	// adjust data
	$single = true;
	$download = '';
	if ( isset( $args[4] ) && $args[4] == 'true' ) {  $single = false; }
	$args[2] = get_post_meta($post->ID, $args[0], $single);
    if ( !empty($args[2]) && $args[2] != '0' ) {
		if ( $single ) {
			$download = '<div><a href="'.$args[2].'">View '.$args[1].'</a></div>';
		} else {
			$download = "<ul>";
			$i = 0;
			foreach ( $args[2] as $file ) {
				if ( $file != '' ) {
					$short = str_replace( site_url(), '', $file );
					$download .= '<li><input type="checkbox" id="del-'.$args[0].$i.'" name="mcm_delete['.$args[0].'][]" value="'.$file.'" /> <label for="del-'.$args[0].$i.'">'.__('Delete','my-content-management').'</label> <a href="'.$short.'">'.$short.'</a></li>';
					$i++;
				}
			}
			$download .= "</ul>";
		}
    } else {
		$download = '';
	}
	$max_upload = (int)(ini_get('upload_max_filesize'));
	$max_post = (int)(ini_get('post_max_size'));
	$memory_limit = (int)(ini_get('memory_limit'));
	$upload_mb = min($max_upload, $max_post, $memory_limit);
	$label_format =
		'<div class="mcm_text_field mcm_field"><input type="hidden" name="%1$s" id="%1$s" value="%3$s" /><label for="%1$s"><strong>%2$s</strong></label><br />'.
		'<input style="width: 80%;" type="file" name="%1$s" id="%1$s" /><br />'.
		sprintf( __( "Upload limit: %s MB",'my-content-management' ),$upload_mb );
		if ( $description != '' ) { $label_format .= '<br /><em>'.$description.'</em>'; } 
		if ( $download != '' ) { $label_format .= $download; }
		$label_format .= "</div>";
		return vsprintf( $label_format, $args );
}

function mcm_chooser_field( $args ) {
	global $post;
	$args[1] = stripslashes( $args[1] );
	$description = stripslashes( $args[2] );	
	// adjust data
	$single = true;
	$download = $value = '';
	if ( isset( $args[4] ) && $args[4] == 'true' ) { $single = false; } 
	$args[2] = get_post_meta($post->ID, $args[0], $single );
	$attr = array( 'height' => 80, 'width'=> 80 );
    if( !empty($args[2]) && $args[2] != '0' ) {
		if ( $single ) {
			$value = '%3$s';
			$url = wp_get_attachment_url( $args[2] );
			$img = wp_get_attachment_image( $args[2], array( 80, 80 ), true, $attr );
			$download .= '<div class="mcm-chooser-image"><a href="'.$url.'">'.$img.'</a><span class="mcm-delete"><input type="checkbox" id="del-'.$args[0].$i.'" name="mcm_delete['.$args[0].'][]" value="'.$args[2].'" /> <label for="del-'.$args[0].'">'.__('Delete','my-content-management').'</label></span></div>';
			$copy = __('Change Media','my-content-management');
		} else {
			$value = '';
			$i = 0;
			foreach ( $args[2] as $attachment ) {
				$url = wp_get_attachment_url( $attachment );
				$img = wp_get_attachment_image( $attachment, array( 80, 80 ), true, $attr );
				$download .= '<div class="mcm-chooser-image"><a href="'.$url.'">'.$img.'</a><span class="mcm-delete"><input type="checkbox" id="del-'.$args[0].$i.'" name="mcm_delete['.$args[0].'][]" value="'.$attachment.'" /> <label for="del-'.$args[0].$i.'">'.__('Delete','my-content-management').'</label></span></div> ';
				$i++;
			}
			$copy = __('Add Media','my-content-management');
		}
    } else {
		$copy = __('Choose Media','my-content-management');
	}
	$label_format =
	'<div class="mcm_chooser_field mcm_field field-holder"><label for="%1$s"><strong>%2$s</strong></label> '.
	'<input type="hidden" name="%1$s" value="'.$value.'" class="textfield" id="%1$s" /> <a href="#" class="button textfield-field">'.$copy.'</a><br />';
	$label_format .= '<br /><div class="selected">'.$description.'</div>';
	if ( $download != '' ) { $label_format .= $download; }
	$label_format .= "</div>";
	return vsprintf( $label_format, $args );
}

function mcm_text_field( $args, $type='text' ) {
	$args[1] = stripslashes( $args[1] );
	$description = stripslashes( $args[2] );
	$types = array( 'color','date','number','tel','time','url' );
	if ( $type == 'mcm_text_field' ) { 
		$type = 'text'; 
	} else { 
		$type = ( in_array( $type, $types ) ) ? $type : 'text'; 
	}
	global $post;
	$name = $args[0];
	$label = $args[1];
	$description = $args[2];
	// adjust data
	$single = true;
	if ( isset( $args[4] ) && $args[4] == 'true' ) { $single = false; }
	$meta = get_post_meta($post->ID, $name, $single);
	$value = ( $single ) ? $meta : '';
	if ( $type == 'date' && $single ) { $value = ( is_numeric( $value ) ) ? date( 'Y-m-d', $value ) : date( 'Y-m-d', strtotime( $value ) ); }
	$output = "<div class='mcm_text_field mcm_field'>";
		$output .=
		'<p>
			<label for="'.$name.'"><strong>'.$label.'</strong></label><br />
			<input style="width: 80%;" type="'.$type.'" name="'.$name.'" value="'.$value.'" id="'.$name.'" />
		</p>';
		if ( is_array( $meta ) ) {
			$i = 1;
			$output .= "<ul>";
			foreach ( $meta as $field ) {
				if ( $field != '' ) {
					$field = htmlentities($field);
					if ( $type == 'date' ) { $field = ( is_numeric( $field ) ) ? date( 'Y-m-d', $field ) : date( 'Y-m-d', strtotime( $field ) ); }
					$output .=
					'<li><span class="mcm-delete"><input type="checkbox" id="del-'.$name.$i.'" name="mcm_delete['.$name.'][]" value="'.$field.'" /> <label for="del-'.$name.$i.'">'.__('Delete','my-content-management').'</label></span> '.$field.'</li>';
					$i++;
				}
			}
			$output .= "</ul>";
		}
	if ( $description != '' ) { $output .= '<em>'.$description.'</em>'; }
	$output .= "</div>";
	return $output;
}

function mcm_select( $args ) {
	global $post;
	$args[1] = stripslashes( $args[1] );
	$choices = $args[2];
	$single = true;
	if ( isset( $args[4] ) && $args[4] == 'true' ) {  $single = false; } 	
	$args[2] = get_post_meta($post->ID, $args[0], $single);
	$label_format = '<p class="mcm_select mcm_field"><label for="%1$s"><strong>%2$s</strong></label><br />'.
		'<select name="%1$s" id="%1$s">'.
			mcm_create_options( $choices, $args[2] ).
		'</select></p>';
	return vsprintf( $label_format, $args );
}

function mcm_create_options( $choices, $selected, $type='select' ) {
	$return = '';
	if (is_array($choices) ) {
		foreach($choices as $value ) {
			$v = esc_attr( $value);
			if ( $type == 'select' ) {
				$chosen = ( $v == $selected )?' selected="selected"':'';
				$return .= "<option value='$value'$chosen>$value</option>";
			} 
		}
	}
	return $return;
}

function mcm_text_area( $args ) {
	global $post;
	$name = $args[0];
	$args[1] = stripslashes( $args[1] );
	$description = stripslashes( $args[2] );	
	$label = $args[1];
	// adjust data
	$single = true;
	if ( isset( $args[4] ) && $args[4] == 'true' ) {  $single = false; }
	$meta = get_post_meta($post->ID, $name, $single);
	$value = ( $single ) ? $meta : '';
	$output = "<div class='mcm_textarea mcm_field'>";
		$output .=
		'<p>
			<label for="'.$name.'"><strong>'.$label.'</strong></label><br />
			<textarea style="width: 90%;" name="'.$name.'" id="'.$name.'">'.$value.'</textarea>
		</p>';
		if ( is_array( $meta ) ) {
			$i = 1;
			$output .= "<ul>";
			foreach ( $meta as $field ) {
				if ( $field != '' ) {
					$field = htmlentities($field);
					$output .=
					'<li><span class="mcm-delete"><input type="checkbox" id="del-'.$name.$i.'" name="mcm_delete['.$name.'][]" value="'.$field.'" /> <label for="del-'.$name.$i.'">'.__('Delete','my-content-management').'</label></span> '.$field.'</li>';
					$i++;
				}
			}
			$output .= "</ul>";
		}
	if ( $description != '' ) { $output .= '<em>'.$description.'</em>'; }
	$output .= "</div>";
	return $output;
}

// this is the text area meta box
function mcm_rich_text_area ( $args ) {
	global $post;
	// adjust data
	$single = true;
	$args[1] = stripslashes( $args[1] );
	$description = stripslashes( $args[2] );
	if ( isset( $args[4] ) && $args[4] == 'true' ) {  $single = false; } 	
	$args[2] = get_post_meta($post->ID, $args[0], $single);
	$meta = $args[2];
	$id = str_replace( array( '_','-'), '', $args[0] );	
	$editor_args = apply_filters( 'mcm_filter_editor_args', array( 'textarea_name'=>$args[0], 'editor_css'=>'<style>.wp_themeSkin iframe { background: #fff; color: #222; }</style>', 'editor_class'=>'mcm_rich_text_editor' ), $args );
	echo "<div class='mcm_rich_text_area'>
				<label for='$id'><strong>$args[1]</strong></label><br />
				<em>$description</em>";
	wp_editor( $meta, $id, $editor_args );
	echo "</div>";
}

/* When the post is saved, saves our custom data */
add_action( 'save_post', 'mcm_save_postdata', 1, 2 );
function mcm_save_postdata( $post_id, $post ) {
	if ( 
		empty( $_POST ) || 
		( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || 
		wp_is_post_revision( $post_id ) || 
		isset( $_POST['_inline_edit'] ) 
		) { return; }
	global $mcm_fields;
	$fields = $mcm_fields;
	// verify this came from our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( isset( $_POST['mcm_nonce_name'] ) ) {
		if ( ! wp_verify_nonce( $_POST['mcm_nonce_name'], plugin_basename(__FILE__) ) ) {
			return $post->ID;
		}		
		// Is the user allowed to edit the post or page?
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post->ID )) {
				return $post->ID;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post->ID )) {
				return $post->ID;
			}
		}
		$these_fields = array();		
		if ( isset( $_POST['mcm_fields'] ) ) {
			$these_fields = $_POST['mcm_fields'];
		} else {
			return;
		}
		foreach ( $fields as $set => $field ) {
			foreach ( $field as $key=>$value ) {
				$custom_field_name = $value[0];
				$custom_field_label = $value[1];
				$custom_field_notes = $value[2];				
				$custom_field_type = $value[3];
				$custom_field_repeatable = ( isset( $value[4] ) ) ? $value[4] : 'false';
				$repeatable = ( isset( $custom_field_repeatable ) && $custom_field_repeatable == 'true' )?true:false;
				
				if ( in_array( $custom_field_name, $these_fields ) && in_array( $set, $_POST['mcm_fieldsets'] ) ) {
					if ( isset( $_POST[$custom_field_name] ) && !$repeatable ) {
						$this_value = apply_filters( 'mcm_filter_saved_data', $_POST[$custom_field_name], $custom_field_name, $custom_field_type );
						update_post_meta( $post->ID, $custom_field_name, $this_value );
					}
					if ( isset( $_POST[$custom_field_name] ) && $repeatable ) {
						if ( $_POST[$custom_field_name] != '' ) {
							$this_value = apply_filters( 'mcm_filter_saved_data', $_POST[$custom_field_name], $custom_field_name, $custom_field_type );
							add_post_meta( $post->ID, $custom_field_name, $this_value );
						}
					}				
					if( !empty( $_FILES[$custom_field_name] ) ) {
						$file   = $_FILES[$custom_field_name];
						$upload = wp_handle_upload($file, array('test_form' => false));
						if(!isset($upload['error']) && isset($upload['file'])) {
							$filetype   = wp_check_filetype( basename( $upload['file'] ), null );
							$title      = $file['name'];
							$ext        = strrchr($title, '.');
							$title      = ($ext !== false) ? substr($title, 0, -strlen($ext)) : $title;
							$attachment = array(
								'post_mime_type'    => $filetype['type'],
								'post_title'        => addslashes($title),
								'post_content'      => '',
								'post_status'       => 'inherit',
								'post_parent'       => $post->ID
							);
							$attach_id = wp_insert_attachment($attachment, $upload['file']);
							$url = wp_get_attachment_url( $attach_id );
							if ( !$repeatable ) {
								update_post_meta( $post->ID, $custom_field_name, $url );
							} else {							
								add_post_meta( $post->ID, $custom_field_name, $url );
							}
						}
					}
					if ( empty( $_FILES[$custom_field_name]['name'] ) && !isset( $_POST[$custom_field_name] ) ) {
						if ( mcm_is_repeatable( $value ) && mcm_has_value( $post->ID, $custom_field_name ) ) {
							// do something here? ...
						} else {
							update_post_meta( $post->ID, $custom_field_name, '' );
						}
					}
				}
			}
		}
		if ( isset( $_POST['mcm_delete'] ) ) {
			foreach ( $_POST['mcm_delete'] as $data=>$deletion ) {
				foreach ( $deletion as $delete ) {
					if ( $delete != '' ) {
						delete_post_meta( $post->ID, $data, $delete );
					}
				}
			}
		}		
	}
}

function mcm_is_repeatable( $value ) {
	if ( is_array( $value ) ) {
		if ( isset($value[4]) && $value[4] == 'true' ) {
			return true; 
		}
	} else {
		$options = get_option( 'mcm_options' );
		$mcm_fields = isset( $options['simplified'] ) ? $options['simplified'] : array() ;
		if ( is_array( $mcm_fields ) ) {		
			foreach ( $mcm_fields as $set ) {
				if ( isset( $set['repetition'] ) && $set['repetition'] == 'true' ) { return true; }
			}
		}
	}
	return false;
}

function mcm_is_richtext( $value ) {
	if ( is_string( $value ) ) { // if this isn't a custom field, ignore it.
		if ( strpos( $value, '_' ) !== 0 ) { return false; }
	}
	if ( is_array( $value ) ) {
		if ( isset($value[3]) && $value[3] == 'richtext' ) {
			return true; 
		}
	} else {
		$options = get_option( 'mcm_options' );
		$mcm_fields = isset( $options['simplified'] ) ? $options['simplified'] : array() ;
		if ( is_array( $mcm_fields ) ) {
			foreach ( $mcm_fields as $set ) {
				if ( isset( $set['type'] ) && $set['type'] == 'richtext' && isset( $set['key'] ) && $set['key'] == $value ) { return true; }
			}
		}
	}
	return false;
}

function mcm_has_value( $post_ID, $key ) {
	$meta = get_post_meta( $post_ID, $key, true );
	if ( $meta ) { return true; }
	return false;
}

function mcm_echo_nonce() {
	// Use nonce for verification ... ONLY USE ONCE!
	echo sprintf(
		'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
		'mcm_nonce_name',
		wp_create_nonce( plugin_basename(__FILE__) )
	);
}

function mcm_echo_hidden($fields, $id ) {
	// finish when I add hidden fields.
	echo '<input type="hidden" name="mcm_fieldsets[]" value="'.$id.'" />';	
	if ( is_array( $fields ) ) {
		foreach ( $fields as $field ) {
			$new_fields[] = $field[0];
		}
		$value = apply_filters( 'mcm_hidden_fields', $new_fields, $fields );
		foreach ( $new_fields as $hidden ) {
			echo '<input type="hidden" name="mcm_fields[]" value="'.$hidden.'" />';
		}
	}
}

// defaults
$d_mcm_args = array(
				'public' => true,
				'publicly_queryable' => true,
				'exclude_from_search'=> false,
				'show_ui' => true,
				'show_in_menu' => true,
				'show_ui' => true, 
				'menu_icon' => null,
				'hierarchical'=>true,
				'supports' => array('title','editor','author','thumbnail','excerpt','custom-fields'),
				'slug' => ''
			);

$default_mcm_types = array( 
	'mcm_faq'=>array(__('faq','my-content-management'),__('faqs','my-content-management'),__('FAQ','my-content-management'),__('FAQs','my-content-management'),$d_mcm_args),
	'mcm_people'=>array(__('person','my-content-management'),__('people','my-content-management'),__('Person','my-content-management'),__('People','my-content-management'),$d_mcm_args),  
	'mcm_testimonials'=>array(__('testimonial','my-content-management'),__('testimonials','my-content-management'),__('Testimonial','my-content-management'),__('Testimonials','my-content-management'),$d_mcm_args),	
	'mcm_locations'=>array(__('location','my-content-management'),__('locations','my-content-management'),__('Location','my-content-management'),__('Locations','my-content-management'), $d_mcm_args),
	'mcm_quotes'=>array(__('quote','my-content-management'),__('quotes','my-content-management'),__('Quote','my-content-management'),__('Quotes','my-content-management'), $d_mcm_args),
	'mcm_glossary'=>array(__('glossary term','my-content-management'),__('glossary terms','my-content-management'),__('Glossary Term','my-content-management'),__('Glossary Terms','my-content-management'), $d_mcm_args),
	'mcm_portfolio'=>array(__('portfolio item','my-content-management'),__('portfolio items','my-content-management'),__('Portfolio Item','my-content-management'),__('Portfolio Items','my-content-management'), $d_mcm_args),
	'mcm_resources'=>array(__('resource','my-content-management'),__('resources','my-content-management'),__('Resource','my-content-management'),__('Resources','my-content-management'), $d_mcm_args)
);

// @fields multidimensional array: array( 'Box set'=> array( array( '_name','label','type') ) )
// @post_type string post_type
// @location string side/normal/advanced
// add custom fields to the custom post types
$default_mcm_fields = 
	array (
		__('Personal Information','my-content-management') => 
		array (
			array( '_title', __('Title','my-content-management'), '','mcm_text_field'),
			array( '_subtitle',__('Subtitle','my-content-management'), '','mcm_text_field'),
			array( '_business',__('Business','my-content-management'),'','mcm_text_field' ),
			array( '_phone', __('Phone Number','my-content-management'), '','tel','true'),
			array( '_email', __('E-mail','my-content-management'), '', 'email')
		),
		__('Location Info','my-content-management') =>
		array (
			array( '_street',__('Street Address','my-content-management'),'','mcm_text_field'),
			array( '_city',__('City','my-content-management'),'','mcm_text_field'),
			array( '_neighborhood',__('Neighborhood','my-content-management'),'','mcm_text_field'),
			array( '_state',__('State','my-content-management'),'','mcm_text_field'),
			array( '_country',__('Country','my-content-management'),'','mcm_text_field'),
			array( '_postalcode',__('Postal Code','my-content-management'),'','mcm_text_field'),				
			array( '_phone',__('Phone','my-content-management'),'','tel'),
			array( '_fax',__('Fax','my-content-management'),'','mcm_text_field'),
			array( '_business',__('Business Name','my-content-management'),'','mcm_text_field'),
			array( '_email',__('Contact Email','my-content-management'),'','email')
		),
		__('Quotation Info','my-content-management') =>
		array (
			array( '_url',__('URL','my-content-management'),'','url'),
			array( '_title',__('Title','my-content-management'),'','mcm_text_field'),
			array( '_location',__('Location','my-content-management'),'','mcm_text_field')		
		),
		__('Testimonial Info','my-content-management') =>
		array (
			array( '_url',__('URL','my-content-management'),'','url'),
			array( '_title',__('Title','my-content-management'),'','mcm_text_field'),
			array( '_location',__('Location','my-content-management'),'','mcm_text_field')		
		),
		__('Portfolio Info','my-content-management') =>
		array (
			array( '_medium',__('Medium','my-content-management'),'','mcm_text_field'),
			array( '_width',__('Width','my-content-management'),'','mcm_text_field'),
			array( '_height',__('Height','my-content-management'),'','mcm_text_field'),
			array( '_depth',__('Depth','my-content-management'),'','mcm_text_field'),
			array( '_price',__('Price','my-content-management'),'','mcm_text_field'),
			array( '_year',__('Year','my-content-management'),'','mcm_text_field')
		),
		__('Resource Info','my-content-management') =>
		array (
			array( '_authors',__('Additional Authors','my-content-management'),'','mcm_text_field','true'),
			array( '_licensing',__('License Terms','my-content-management'),'','mcm_text_area'),
			array( '_show',__('Show on','my-content-management'),'This is a label for advanced use in themes','mcm_text_field')
		)
	);

$default_mcm_extras = 
	array( 
		__('Personal Information','my-content-management') => array( 'mcm_people','side' ),
		__('Location Info','my-content-management') => array( 'mcm_locations','side' ),
		__('Testimonial Info','my-content-management') =>	array( 'mcm_testimonials','side' ),
		__('Quotation Info','my-content-management') =>	array( 'mcm_quotes', 'side' ),
		__('Portfolio Info','my-content-management') => array( 'mcm_portfolio', 'side' ),
		__('Resource Info','my-content-management') => array( 'mcm_resources', 'side' )		
	);