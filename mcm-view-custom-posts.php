<?php
/*
@type = string = post_type 
@display = string: custom, full, excerpt, list
@taxonomy = string = taxonomy
@term = string = term slug
@count = integer = number of posts to display
@order = string = orderby options: none,ID,author,title,date,modified,parent,rand,comment_count,menu_order,meta_value,meta_value_num
@direction = string = ASC/DESC
@meta_key = Custom key if order by menu_order
@template = template name or template if $display = 'custom'
@id = specific post ID
*/
function mcm_get_show_posts(  $type, $display, $taxonomy, $term, $count, $order, $direction, $meta_key, $template, $offset, $id ) {
global $templates, $types;
	$keys = array_keys($types);
	$mcm = true;
	// check if this post type is coming from MCM
	if ( !in_array($type,$keys) && !in_array('mcm_'.$type,$keys ) ) {
		$wrapper = ( $template != '' )?$template:'mcm_people';
		$mcm = false;		
	} else {
		if ( strpos($type,'mcm_')===0 ) { $type = $type; } else { $type = 'mcm_'.$type; }
		if ($taxonomy != 'all') {
			if (strpos($taxonomy,'mcm_')===0) {} else { $taxonomy = 'mcm_'.$taxonomy; }
		}
		$wrapper = ( $template != '' )?$template:$type;
	}
	// get wrapper element
	if ( $display == 'custom' ) {
		$elem = '';
		$wrapper = $template;
	} else {
		$elem = ( isset($templates[$wrapper]['wrapper']['list'][$display]) )?$templates[$wrapper]['wrapper']['list'][$display]:'div';	
	}
	$wrapper = trim($wrapper);
	if ( $id == false ) {
		// set up arguments for loop
		wp_reset_query();
		$args = array( 'post_type' => $type, 'posts_per_page'=>$count, 'orderby'=>$order, 'order'=>$direction );
		if ( $offset != false ) { $args['offset']= (int) $offset; }
		if ($taxonomy != 'all' && $term !='' && strpos( $taxonomy, ',') === false ) { $args['tax_query'] = array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $term ) ); }
		if ( strpos( $taxonomy, ',' ) !== false && strpos($term, ',' ) !== false ) {
			$taxonomies = explode( ',', $taxonomy );
			$terms = explode( ',', $term );
			$i = 0;
			$tax_query = array();
			foreach ( $taxonomies as $t ) {
				$array = array( 'taxonomy' => $t, 'field' => 'slug', 'terms' => $terms[$i] );
				$tax_query[] = $array;
				$i++;
			}
			$tax_query['relation']='AND';
			$args['tax_query'] = $tax_query; 
		}
		
		if ( $order == 'meta_value' || $order == 'meta_value_num' ) { $args['meta_key'] = $meta_key; }
		$loop = new WP_Query( $args );
		$column = 'odd';
		$last_term = false;
		$first = true;
		while ( $loop->have_posts() ) : $loop->the_post();
			$p = array();
			$id = get_the_ID();
			$p['id'] = $id;
			$p['excerpt'] = wpautop( get_the_excerpt() );
			$p['excerpt_raw'] = get_the_excerpt();
			$p['content'] = do_shortcode( wpautop( get_the_content() ) );
			$p['content_raw'] = get_the_content();
			$p['thumbnail'] = get_the_post_thumbnail( $id, 'thumbnail', array( 'class'=>'mcm_thumbnail', 'alt'=>trim( strip_tags( get_the_title( $id ) ) ), 'title'=>'' ) );
			$p['medium'] = get_the_post_thumbnail( $id, 'medium', array( 'class'=>'mcm_medium', 'alt'=>trim( strip_tags( get_the_title( $id ) ) ), 'title'=>'' ) );
			$p['large'] = get_the_post_thumbnail( $id, 'large', array( 'class'=>'mcm_large', 'alt'=>trim( strip_tags( get_the_title( $id ) ) ), 'title'=>'' ) );
			$p['full'] = get_the_post_thumbnail( $id, 'full', array( 'class'=>'mcm_large', 'alt'=>trim( strip_tags( get_the_title( $id ) ) ), 'title'=>'' ) );
			$p['permalink'] = get_permalink();
			$p['link_title'] = "<a href='".get_permalink()."'>".get_the_title()."</a>";			
			$p['title'] = get_the_title();
			$p['shortlink'] = wp_get_shortlink();
			$p['modified'] = get_the_modified_date();
			$p['date'] = get_the_date();
			$p['author'] = get_the_author();
			$p['postclass'] = get_post_class();
			$p['terms'] = ($taxonomy != 'all')?get_the_term_list( $id, $taxonomy,'',', ','' ):'';
			$custom_fields = get_post_custom();
				foreach ( $custom_fields as $key=>$value ) {
					if ( is_array( $value ) ) {
						if ( is_array( $value[0] ) ) {
							$p[$key] = explode( ", ", $value[0] );
						} else {
							$p[$key] = $value[0];
						}
					}
				}
			if ( isset($p['_email']) ) { $p['_email'] = mcm_munge($p['_email']); }
			// use this filter to insert any additional custom template tags required		
			$p = apply_filters('mcm_extend_posts', $p, $p );
			// This filter is used to insert alphabetical headings. You can probably find another use for it.
			$return = apply_filters('mcm_filter_posts',$return, $p, $last_term, $elem, $type, $first );
			$first = false;
			$last_term = get_the_title();
			$return .= mcm_run_template( $p, $display, $column, $wrapper );
			switch ($column) {
				case 'odd':	$column = 'even';	break;
				case 'even': $column = 'odd';	break;
			}
		endwhile;
		wp_reset_postdata();
	} else {
		if ( is_int($id) ) {
			$post = get_post($id);
		} else {
			$ids = explode(",",$id);
			foreach ( $ids as $v ) {
				$the_post = get_post( $v );
				$post['id'] = $the_post->ID;
				$post['excerpt'] = wpautop( $the_post->post_excerpt );
				$post['excerpt_raw'] = $the_post->post_excerpt;
				$post['content'] = do_shortcode( wpautop( $the_post->post_content ) );
				$post['content_raw'] = $the_post->post_content;
				$post['thumbnail'] = get_the_post_thumbnail( $the_post->ID, 'thumbnail', array( 'class'=>'', 'alt'=>trim( strip_tags( get_the_title() ) ), 'title'=>'' ) );
				$post['medium'] = get_the_post_thumbnail( $the_post->ID, 'medium', array( 'class'=>'', 'alt'=>trim( strip_tags( get_the_title() ) ), 'title'=>'' ) );
				$post['large'] = get_the_post_thumbnail( $the_post->ID, 'large', array( 'class'=>'', 'alt'=>trim( strip_tags( get_the_title() ) ), 'title'=>'' ) );
				$post['full'] = get_the_post_thumbnail( $the_post->ID, 'full', array( 'class'=>'', 'alt'=>trim( strip_tags( get_the_title() ) ), 'title'=>'' ) );
				$post['permalink'] = get_permalink( $the_post->ID );
				$post['link_title'] = "<a href='".get_permalink( $the_post->ID )."'>".$the_post->post_title."</a>";				
				$post['title'] = $the_post->post_title;
				$post['shortlink'] = wp_get_shortlink( $the_post->ID );
				$post['modified'] = date( get_option('date_format'), strtotime( $the_post->post_modified ) );
				$post['date'] = date( get_option('date_format'), strtotime( $the_post->post_date ) );
				$post['author'] = get_the_author_meta( 'display_name', $the_post->post_author );
				$post['postclass'] = get_post_class( '',$the_post->ID );
				$post['terms'] = ($taxonomy != 'all')?get_the_term_list( $the_post->ID, $taxonomy,'',', ','' ):'';
				$custom_fields = get_post_custom( $the_post->ID );
					foreach ( $custom_fields as $key=>$value ) {
						if ( is_array( $value ) ) {
							if ( is_array( $value[0] ) ) {
								$post[$key] = explode( ", ", $value[0] );
							} else {
								$post[$key] = $value[0];
							}
						}
					}
				if ( isset($post['_email']) ) { $post['_email'] = mcm_munge($post['_email']); }	
				$return .= mcm_run_template( $post, $display, $column, $wrapper );
				$return .= $wrapper;
				switch ($column) {
					case 'odd':	$column = 'even';	break;
					case 'even': $column = 'odd';	break;
				}
			}
		}
	}
	if ( $elem != '' ) { $front = "<$elem>"; $back = "</$elem>"; } else { $elem = $unelem = '';}
	
	if ( $display != 'custom' ) {
	$return = "
		<div class='mcm_posts $type $display'>
		$front
			$return
		$back
		</div>";
	}
	return str_replace("\r\n",'',$return);
}

function mcm_run_template( $post, $display, $column, $type ) {
global $templates;
	$postclass = implode(' ',$post['postclass']);
	$return = '';
	$post['column'] = $column;
	switch ( $display ) {
		case 'custom':
			$template = $type;
			return mcm_draw_template($post,$template);
		break;
		case 'full':
			$wrapper = ( isset($templates[$type]['wrapper']['item']['full']) )?$templates[$type]['wrapper']['item']['full']:'div';
		
			if ( $wrapper != '' ) { $pre = "<$wrapper class='$postclass $column'>"; $posttag = "</$wrapper>"; } else { $pre = $posttag = '';}
			
			if ( isset($templates[$type]['full']) && trim( $templates[$type]['full'] ) != '' ) {
				$return .= "$pre
				".mcm_draw_template($post,$templates[$type]['full'])."
				$posttag";
			} else {
				$return .= "
					<h2>$post[title]</h2>
					$post[content]
					<p>$post[link_title]</p>";
			}
		break;
		case 'excerpt':
			$wrapper = ( isset($templates[$type]['wrapper']['item']['excerpt']) )?$templates[$type]['wrapper']['item']['excerpt']:'div';
			
			if ( $wrapper != '' ) { $pre = "<$wrapper class='$postclass $column'>"; $posttag = "</$wrapper>"; } else { $pre = $posttag = '';}

			if ( isset($templates[$type]['excerpt']) && trim( $templates[$type]['excerpt'] ) != '' ) {
				$return .= "$pre
				".mcm_draw_template($post,$templates[$type]['excerpt'])."
				$posttag";
			} else {		
				$return .= "
					<h3>$post[title]</h3>
					$post[excerpt]
					<p>$post[link_title]</p>";
			}
		break;
		case 'list':
			$wrapper = ( isset($templates[$type]['wrapper']['item']['list']) )?$templates[$type]['wrapper']['item']['list']:'li';
			
			if ( $wrapper != '' ) { $pre = "<$wrapper class='$postclass $column'>"; $posttag = "</$wrapper>"; } else { $pre = $posttag = '';}
			if ( isset($templates[$type]['list']) && trim( $templates[$type]['list'] ) != '' ) {
				$return .= "$pre
				".mcm_draw_template($post,$templates[$type]['list'])."
				$posttag";
			} else {		
				$return .= "$post[link_title]\n";
			}
		break;
		default:
			$wrapper = 'div';
			$return .= "
					<h3>$post[title]</h3>
					$post[excerpt]
					<p>$post[link_title]</p>";			
		break;
	}
	return $return;
}

function mcm_draw_template( $array,$template ) {
	//1st argument: array of details
	//2nd argument: template to print details into
	foreach ($array as $key=>$value) {
		if ( !is_object($value) ) {
			$search = "{".$key."}";
			$template = stripcslashes(str_replace($search,$value,$template));
		} else {
		}
	}
	return trim($template);
}

function mcm_search_form( $post_type ) {
// no arguments
	if (strpos($post_type,'mcm_') === 0 ) { $post_type = $post_type; } else { $post_type = 'mcm_'.$post_type; }
	$nonce = "<input type='hidden' name='_wpnonce' value='".wp_create_nonce('mcm-nonce')."' />";
	$return = "
	<div class='search_container'>
		<form action='".home_url()."' method='get'>
		<div>
		$nonce
		<input type='hidden' name='customsearch' value='$post_type' />
		</div>
		<label for='csearch'>Search</label> <input type='text' class='text' name='s' id='csearch' />
		<input type='submit' value='Search' class='btn'	/>
		</form>	
	</div>";
	return $return;
}

function mcm_searchfilter($query) {
	if ( isset($_GET['customsearch']) ) {
		if ($query->is_search) {
			// Insert the specific post type you want to search
			$post_type = esc_attr( $_GET['customsearch'] );
			$query->set( 'post_type', $post_type );
		}
		return $query;
	}
}

function mcm_munge($address) {
    $address = strtolower($address);
    $coded = "";
    $unmixedkey = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.@";
    $inprogresskey = $unmixedkey;
    $mixedkey="";
    $unshuffled = strlen($unmixedkey);
    for ($i = 0; $i <= strlen($unmixedkey); $i++) {
		$ranpos = rand(0,$unshuffled-1);
		$nextchar = $inprogresskey{$ranpos};
		$mixedkey .= $nextchar;
		$before = substr($inprogresskey,0,$ranpos);
		$after = substr($inprogresskey,$ranpos+1,$unshuffled-($ranpos+1));
		$inprogresskey = $before.''.$after;
		$unshuffled -= 1;
    }
    $cipher = $mixedkey;

    $shift = strlen($address);

    $txt = "<script type=\"text/javascript\">\n" .
           "<!-"."-\n";
    for ($j=0; $j<strlen($address); $j++) {
		if (strpos($cipher,$address{$j}) == -1 ) {
			$chr = $address{$j};
			$coded .= $address{$j};
		} else {
			$chr = (strpos($cipher,$address{$j}) + $shift) % strlen($cipher);
			$coded .= $cipher{$chr};
		}
    }

    $txt .= "\ncoded = \"" . $coded . "\"\n" .
	"  key = \"".$cipher."\"\n".
	"  shift=coded.length\n".
	"  link=\"\"\n".
	"  for (i=0; i<coded.length; i++) {\n" .
	"    if (key.indexOf(coded.charAt(i))==-1) {\n" .
	"      ltr = coded.charAt(i)\n" .
	"      link += (ltr)\n" .
	"    }\n" .
	"    else {     \n".
	"      ltr = (key.indexOf(coded.charAt(i))-
shift+key.length) % key.length\n".
	"      link += (key.charAt(ltr))\n".
	"    }\n".
	"  }\n".
	"document.write(\"<a href='mailto:\"+link+\"'>\"+link+\"</a>\")\n" .
	"\n".
        "//-"."->\n" .
        "<" . "/script><noscript>N/A" .
	"<"."/noscript>";
    return $txt;
}