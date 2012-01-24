<?php
/*
Plugin Name: My Content Management
Plugin URI: http://www.joedolson.com/articles/my-content-management/
Description: Creates a set of common custom post types for extended content management: FAQ, Testimonials, people lists, term lists, etc.
Author: Joseph C Dolson
Author URI: http://www.joedolson.com
Version: 1.1.1
*/
/*  Copyright 2011-2012  Joe Dolson (email : joe@joedolson.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
$mcm_version = '1.1.0';
// Enable internationalisation
load_plugin_textdomain( 'my-content-management',false, dirname( plugin_basename( __FILE__ ) ) . '/lang' ); 

include(dirname(__FILE__).'/mcm-custom-posts.php' );
include(dirname(__FILE__).'/mcm-view-custom-posts.php' );
include(dirname(__FILE__).'/mcm-widgets.php' );

if ( !get_option( 'mcm_version' ) ) {  mcm_install_plugin(); }
if ( version_compare( get_option('mcm_version'), $mcm_version, '<' ) ) { mcm_upgrade_plugin(); }

//Shortcode
function mcm_show_posts($atts) {
	extract(shortcode_atts(array(
				'type' => 'page',
				'display' => 'short',
				'taxonomy' => 'all',
				'term' => '',
				'count' => -1,
				'order' => 'menu_order',
				'direction' => 'DESC',
				'meta_key' => '',
				'template' => '',
				'offset'=> false,
				'id' => false
			), $atts));
	return mcm_get_show_posts( $type, $display, $taxonomy, $term, $count, $order, $direction, $meta_key, $template, $offset, $id );
}

function mcm_show_archive($atts) {
	extract(shortcode_atts(array(
				'type' => false,
				'display' => '',
				'taxonomy' => false,
				'count' => -1,
				'order' => 'menu_order',
				'direction' => 'DESC',
				'meta_key' => '',
				'exclude' => '',
				'template' => '',
				'offset' => '',
			), $atts));
			if ( !$type || !$taxonomy ) return;
		$terms = get_terms( $taxonomy );
		$output = '';
		$exclude = explode(',',$exclude);
		if ( is_array($terms) ) {
			foreach ( $terms as $term ) {
				$tax = $term->name;
				$tax_class = sanitize_title($tax);
				if ( !in_array( $tax, $exclude ) ) {
					$output .= "\n<div class='archive-group'>";
					$output .= "<h2 class='$tax_class'>$tax</h2>";
					$output .= mcm_get_show_posts( $type, $display, $taxonomy, $tax, $count, $menu_order, $direction, $meta_key, $template, $offset, false );
					$output .= "</div>\n";
				}
			}
		}
	return $output;
}

function mcm_search_custom($atts) {
	extract(shortcode_atts(array(
				'type' => 'page'
			), $atts));
	return mcm_search_form( $type );
}
// Shortcodes 
add_shortcode('my_content','mcm_show_posts');
add_shortcode('custom_search','mcm_search_custom');
add_shortcode('my_archive','mcm_show_archive'); 
// Filters
//add_filter('the_content', 'mcm_pre_process_shortcode', 7);
add_filter('pre_get_posts','mcm_searchfilter');
add_filter( 'post_updated_messages', 'mcm_posttypes_messages');

// Actions
add_action('init', 'mcm_taxonomies', 0);
add_action( 'init', 'mcm_posttypes' );
add_action( 'admin_menu', 'mcm_add_custom_boxes' );


function mcm_install_plugin() {
global $types;
	$templates = array();
	if ( is_array( $types ) ) {
		foreach ( $types as $key=>$value ) {
			$templates[$key]['full'] = '<h2>{title}</h2>
{content}
<p>{link_title}</p>';
			$templates[$key]['excerpt'] = '<h3>{title}</h3>
{excerpt}
<p>{link_title}</p>';
			$templates[$key]['list'] = '{link_title}';
			$templates[$key]['wrapper']['item']['full'] = 'div';
			$templates[$key]['wrapper']['item']['excerpt'] = 'div';
			$templates[$key]['wrapper']['item']['list'] = 'li';
			$templates[$key]['wrapper']['list']['full'] = 'div';
			$templates[$key]['wrapper']['list']['excerpt'] = 'div';
			$templates[$key]['wrapper']['list']['list'] = 'ul';	
		}
	} else {
		echo "Why not?"; die;
	}
	$options = array(
		'enabled'=> array(),
		'templates' => $templates
	);
	add_option( 'mcm_options', $options );
}

function mcm_upgrade_plugin() {
// don't need to do anything with versions yet.
	global $mcm_version, $types;
	$from = get_option('mcm_version');
	if ( $mcm_version == $from ) return; 
	
	$newtypes = array('mcm_resources');
	
	$options = get_option('mcm_options');
	$templates = $options['templates'];
	foreach ( $types as $key => $value ) {
		if ( in_array( $key, $newtypes ) ) {
			$templates[$key]['full'] = '<h2>{title}</h2>
{content}
<p>{link_title}</p>';
			$templates[$key]['excerpt'] = '<h3>{title}</h3>
{excerpt}
<p>{link_title}</p>';
			$templates[$key]['list'] = '{link_title}';
			$templates[$key]['wrapper']['item']['full'] = 'div';
			$templates[$key]['wrapper']['item']['excerpt'] = 'div';
			$templates[$key]['wrapper']['item']['list'] = 'li';
			$templates[$key]['wrapper']['list']['full'] = 'div';
			$templates[$key]['wrapper']['list']['excerpt'] = 'div';
			$templates[$key]['wrapper']['list']['list'] = 'ul';	
		}			
	}
	$options['templates'] = $templates;
	update_option( 'mcm_options', $options );
	update_option( 'mcm_version', $mcm_version );
}

add_action( 'in_plugin_update_message-my-content-management/my-content-management.php', 'mcm_plugin_update_message' );
function mcm_plugin_update_message() {
	global $mcm_version;
	define('MCM_PLUGIN_README_URL',  'http://svn.wp-plugins.org/my-content-management/trunk/readme.txt');
	$response = wp_remote_get( MCM_PLUGIN_README_URL, array ('user-agent' => 'WordPress/My Content Management' . $mcm_version . '; ' . get_bloginfo( 'url' ) ) );
	if ( ! is_wp_error( $response ) || is_array( $response ) ) {
		$data = $response['body'];
		$bits=explode('== Upgrade Notice ==',$data);
		echo '<div id="mc-upgrade"><p><strong style="color:#c22;">Upgrade Notes:</strong> '.nl2br(trim($bits[1])).'</p></div>';
	} else {
		printf(__('<br /><strong>Note:</strong> Please review the <a class="thickbox" href="%1$s">changelog</a> before upgrading.','my-content-management'),'plugin-install.php?tab=plugin-information&amp;plugin=my-content-management&amp;TB_iframe=true&amp;width=640&amp;height=594');
	}
}


function mcm_get_support_form() {
global $current_user, $mcm_version;
$textdomain = 'my-content-management';
get_currentuserinfo();
	// send fields for My Content Management
	$version = $mcm_version;
	// send fields for all plugins
	$wp_version = get_bloginfo('version');
	$home_url = home_url();
	$wp_url = get_bloginfo('wpurl');
	$language = get_bloginfo('language');
	$charset = get_bloginfo('charset');
	// server
	$php_version = phpversion();

	// theme data
	$theme_path = get_stylesheet_directory().'/style.css';
	$theme = get_theme_data($theme_path);
		$theme_name = $theme['Name'];
		$theme_uri = $theme['URI'];
		$theme_parent = $theme['Template'];
		$theme_version = $theme['Version'];
	// plugin data

	$plugins = get_plugins();
	$plugins_string = '';

		foreach( array_keys($plugins) as $key ) {
			if ( is_plugin_active( $key ) ) {
				$plugin =& $plugins[$key];
				$plugin_name = $plugin['Name'];
				$plugin_uri = $plugin['PluginURI'];
				$plugin_version = $plugin['Version'];
				$plugins_string .= "$plugin_name: $plugin_version; $plugin_uri\n";
			}
		}
	$data = "
================ Installation Data ====================
==My Content Management:==
Version: $version

==WordPress:==
Version: $wp_version
URL: $home_url
Install: $wp_url
Language: $language
Charset: $charset

==Extra info:==
PHP Version: $php_version
Server Software: $_SERVER[SERVER_SOFTWARE]
User Agent: $_SERVER[HTTP_USER_AGENT]

==Theme:==
Name: $theme_name
URI: $theme_uri
Parent: $theme_parent
Version: $theme_version

==Active Plugins:==
$plugins_string
";
	if ( isset($_POST['mc_support']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'my-content-management-nonce') ) die("Security check failed");	
		$request = stripslashes($_POST['support_request']);
		$has_donated = ( $_POST['has_donated'] == 'on')?"Donor":"No donation";
		$has_read_faq = ( $_POST['has_read_faq'] == 'on')?"Read FAQ":true; // has no faq, for now.
		$subject = "My Content Management support request. $has_donated";
		$message = $request ."\n\n". $data;
		$from = "From: \"$current_user->display_name\" <$current_user->user_email>\r\n";

		if ( !$has_read_faq ) {
			echo "<div class='message error'><p>".__('Please read the FAQ and other Help documents before making a support request.',$textdomain )."</p></div>";
		} else {
			wp_mail( "plugins@joedolson.com",$subject,$message,$from );
		
			if ( $has_donated == 'Donor' || $has_purchased == 'Purchaser' ) {
				echo "<div class='message updated'><p>".__('Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can.',$textdomain )."</p></div>";		
			} else {
				echo "<div class='message updated'><p>".__('I\'ll get back to you as soon as I can, after dealing with any support requests from plug-in supporters.',$textdomain )."</p></div>";				
			}
		}
	}
	
	echo "
	<form method='post' action='".admin_url('options-general.php?page=my-content-management/my-content-management.php')."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('my-content-management-nonce')."' /></div>
		<div>
		<p>".
		__('Please note: I do keep records of donations, but if your donation came from somebody other than your account at this web site, please note this in your message.',$textdomain )
		."</p>
		<p>
		<code>".__('From:','my-content-management')." \"$current_user->display_name\" &lt;$current_user->user_email&gt;</code>
		</p>
		<!--<p>
		<input type='checkbox' name='has_read_faq' id='has_read_faq' value='on' /> <label for='has_read_faq'>".__('I have read <a href="http://www.joedolson.com/articles/my-content-management/">the FAQ for this plug-in</a>.',$textdomain )." <span>(required)</span></label>
		</p>-->
		<p>
		<input type='checkbox' name='has_donated' id='has_donated' value='on' /> <label for='has_donated'>".__('I have <a href="http://www.joedolson.com/donate.php">made a donation to help support this plug-in</a>.',$textdomain )."</label>
		</p>
		<p>
		<label for='support_request'>Support Request:</label><br /><textarea name='support_request' id='support_request' cols='80' rows='10'>".stripslashes($request)."</textarea>
		</p>
		<p>
		<input type='submit' value='".__('Send Support Request',$textdomain )."' name='mc_support' class='button-primary' />
		</p>
		<p>".
		__('The following additional information will be sent with your support request:',$textdomain )
		."</p>
		<div class='mc_support'>
		".wpautop($data)."
		</div>
		</div>
	</form>";
}

// Function to add javascript to the admin header
function mcm_add_scripts() {
	wp_enqueue_script('common');
	wp_enqueue_script('wp-lists');
	wp_enqueue_script('postbox');
}

function mcm_settings_page() {
global $enabled;
$enabled = (isset($_POST['mcm_enabler']))?$_POST['mcm_posttypes']:$enabled;
?>
<div class="wrap">
<div id="icon-index" class="icon32"><br /></div>
<h2><?php _e('My Content Management','my-content-management'); ?></h2>
<div class="mcm-template-guide">
<h3><?php _e('Basic Template Tags','my-content-management'); ?></h3>
<dl>
<dt><code>{id}</code></dt>
<dd><?php _e('Post ID','my-content-management'); ?></dd>

<dt><code>{excerpt}</code></dt>
<dd><?php _e('Post excerpt (with auto paragraphs)','my-content-management'); ?></dd>

<dt><code>{excerpt_raw}</code></dt>
<dd><?php _e('Post excerpt (unmodified)','my-content-management'); ?></dd>

<dt><code>{content}</code></dt>
<dd><?php _e('Post content (with auto paragraphs and shortcodes processed)','my-content-management'); ?></dd>

<dt><code>{content_raw}</code></dt>
<dd><?php _e('Post content (unmodified)','my-content-management'); ?></dd>

<dt><code>{thumbnail}</code></dt>
<dd><?php _e('Featured image as thumbnail.','my-content-management'); ?></dd>

<dt><code>{medium}</code></dt>
<dd><?php _e('Featured image at medium size.','my-content-management'); ?></dd>

<dt><code>{large}</code></dt>
<dd><?php _e('Featured image at large size.','my-content-management'); ?></dd>

<dt><code>{full}</code></dt>
<dd><?php _e('Featured image at original size.','my-content-management'); ?></dd>

<dt><code>{permalink}</code></dt>
<dd><?php _e('Permalink URL for post','my-content-management'); ?></dd>

<dt><code>{link_title}</code></dt>
<dd><?php _e('Post title linked to permalink URL','my-content-management'); ?></dd>

<dt><code>{title}</code></dt>
<dd><?php _e('Post title','my-content-management'); ?></dd>

<dt><code>{shortlink}</code></dt>
<dd><?php _e('Post shortlink','my-content-management'); ?></dd>

<dt><code>{modified}</code></dt>
<dd><?php _e('Post last modified date','my-content-management'); ?></dd>

<dt><code>{date}</code></dt>
<dd><?php _e('Post publication date','my-content-management'); ?></dd>

<dt><code>{author}</code></dt>
<dd><?php _e('Post author display name','my-content-management'); ?></dd>

<dt><code>{terms}</code></dt>
<dd><?php _e('List of taxonomy terms associated with post.','my-content-management'); ?></dd>

</dl>
<p>
<?php _e('Any custom field can also be referenced via shortcode, using the same pattern with the name of the custom field: <code>{custom_field_name}</code>','my-content-management'); ?>
</p>
</div>

	<div class="mcm-settings meta-box-sortables" id="poststuff">
		<div class="postbox" id="mcm-settings">
		<h3><?php _e('Enable Custom Post Types','my-content-management'); ?></h3>
			<div class="inside">
			<?php mcm_show_support_box(); ?>			

			<form method='post' action='<?php echo admin_url('options-general.php?page=my-content-management/my-content-management.php'); ?>'>
				<div><input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('my-content-management-nonce'); ?>' /></div>
				<div>
				<?php mcm_enabler(); ?>
				<p>
					<input type='submit' value='<?php _e('Enable Selected Post Types','my-content-manager'); ?>' name='mcm_enabler' class='button-primary' />
				</p>
				</div>
			</form>
			</div>			
		</div>
		<?php if ( !empty($enabled) ) { ?>
		<?php mcm_template_setter(); ?>
		<?php } ?>

		<div class="postbox" id="get-support">
		<h3><?php _e('Get Plug-in Support','my-content-management'); ?></h3>
			<div class="inside">
			<?php mcm_get_support_form(); ?>
			</div>
		</div>
	</div>
</div>
<?php
}
function mcm_enabler() {
	if ( isset($_POST['mcm_enabler']) ) {
		$enable = $_POST['mcm_posttypes'];
		$option = get_option('mcm_options');
		$option['enabled'] = $enable;
		update_option('mcm_options',$option);
	}
	$option = get_option('mcm_options');
	$enabled = $option['enabled'];
	global $types;
	$checked = '';
	$return = '';
	if ( is_array($types) ) {
		foreach ( $types as $key=>$value ) {
			if ( is_array($enabled) ) {
				if ( in_array( $key, $enabled ) ) { $checked = ' checked="checked"'; } else { $checked = ''; }
			}
			$return .= "<li><input type='checkbox' value='$key' name='mcm_posttypes[]' id='mcm_$key'$checked /> <label for='mcm_$key'>$value[3]</label></li>\n";
		}
	}
	echo "<ul class='mcm_posttypes'>".$return."</ul>";
}

function mcm_template_setter() {
	if ( isset($_POST['mcm_save_templates']) ) {
		$type = $_POST['mcm_post_type'];
		$option = get_option('mcm_options');
		$new = $_POST['templates'];
		$option['templates'][$type] = $new[$type];
		update_option('mcm_options',$option);
	}
	$option = get_option('mcm_options');
	$templates = $option['templates'];
	$enabled = $option['enabled'];
	global $types, $fields, $extras;
	$return = '';
	$list = array('div','ul','ol','dl','section');
	$item = array('div','li','article');
	if ( is_array($enabled) ) {
		foreach ( $enabled as $value ) {
			$pointer = '';
			$display_value = str_replace('mcm_','',$value);
			$template = $templates[$value];
			$label = $types[$value];
			foreach ( $extras as $k=>$v ) {
				if ( $v[0] == $value ) {
					$extra_fields = $fields[$k];
					$pointer = $value;
				}
			}
			if ( $pointer != $value ) { $extra_fields = false; }
			$show_fields = '';
			if ( is_array( $extra_fields ) ) {
				foreach ( $extra_fields as $k=>$v ) {
					$show_fields .= "<p><code>&#123;$v[0]&#125;</code>: $v[1]</p>";
				}
			} else {
				$show_fields = '';
			}
			$extension = '';
			if ( $value == 'mcm_glossary' && function_exists('mcm_set_glossary') ) { $extension = "<h4>Glossary Extension</h4>
			<p>".__('The glossary extension to My Content Management is enabled.','my-content-management')."</p>
			<ul>
			<li><code>[alphabet]</code>: ".__('displays list of linked first characters represented in your Glossary. (Roman alphabet only.)','my-content-management')."</li>
			<li><code>[term id='' term='']</code>: ".__('displays value of term attribute linked to glossary term with ID attribute.','my-content-management')."</li>
			<li><strong>".__('Feature','my-content-management').":</strong> ".__('Adds links throughout content for each term in your glossary.','my-content-management')."</li>
			<li><strong>".__('Feature','my-content-management').":</strong> ".__('Adds character headings to each section of your glossary list.','my-content-management')."</li>
			</ul>"; }
			$show_fields = ($show_fields != '')?"<div class='extra_fields'><h4>".__('Added custom fields:','my-content-management')."</h4>$show_fields</div>":'';
			$extension = ($extension != '')?"<div class='extra_fields'>$extension</div>":'';
			$return .= "
	<div class='postbox' id='mcm-settings-$value'>
	<div class='handlediv' title='Click to toggle'><br /></div><h3 class='hndle'><span>$label[2] ".__('Template Manager','my-content-management')."</span></h3>
		<div class='inside'>
		$show_fields
		$extension
		<form method='post' action='".admin_url('options-general.php?page=my-content-management/my-content-management.php')."'>
			<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('my-content-management-nonce')."' /></div>
			<div><input type='hidden' name='mcm_post_type' value='$value' /></div>
			<div>
			<fieldset>
			<legend>Full</legend>
			<p>Sample shortcode: <code>[my_content type='$display_value' display='full' taxonomy='category_$display_value' order='menu_order']</code></p>
			<p class='wrappers'>
			<label for='mcm_full_list_wrapper_$value'>".__('List Wrapper','my-content-management')."</label> <select name='templates[$value][wrapper][list][full]' id='mcm_full_list_wrapper_$value'>".mcm_option_list( $list, $template['wrapper']['list']['full'] )."</select><br />
			<label for='mcm_full_item_wrapper_$value'>".__('Item Wrapper','my-content-management')."</label> <select name='templates[$value][wrapper][item][full]' id='mcm_full_itemwrapper_$value'>".mcm_option_list( $item, $template['wrapper']['item']['full'] )."</select>
			</p>
			<p>
			<label for='mcm_full_wrapper_$value'>".__('Full Template','my-content-management')."</label><br /> <textarea name='templates[$value][full]' id='mcm_full_wrapper_$value' rows='8' cols='60'>".stripslashes(htmlentities($template['full']))."</textarea>
			</p>
			</fieldset>
			<fieldset>
			<legend>Excerpt</legend>
			<p class='wrappers'>
			<label for='mcm_excerpt_list_wrapper_$value'>".__('List Wrapper','my-content-management')."</label> <select name='templates[$value][wrapper][list][excerpt]' id='mcm_excerpt_list_wrapper_$value'>".mcm_option_list( $list, $template['wrapper']['list']['excerpt'] )."</select><br />
			<label for='mcm_excerpt_item_wrapper_$value'>".__('Item Wrapper','my-content-management')."</label> <select name='templates[$value][wrapper][item][excerpt]' id='mcm_excerpt_item_wrapper_$value'>".mcm_option_list( $item, $template['wrapper']['item']['excerpt'] )."</select>
			</p>
			<p>
			<label for='mcm_excerpt_wrapper_$value'>".__('Excerpt Template','my-content-management')."</label><br /> <textarea name='templates[$value][excerpt]' id='mcm_excerpt_wrapper_$value' rows='4' cols='60'>".stripslashes(htmlentities($template['excerpt']))."</textarea>
			</p>
			</fieldset>
			<fieldset>
			<legend>List</legend>
			<p class='wrappers'>
			<label for='mcm_list_list_wrapper_$value'>".__('List Wrapper','my-content-management')."</label> <select name='templates[$value][wrapper][list][list]' id='mcm_list_list_wrapper_$value'>".mcm_option_list( $list, $template['wrapper']['list']['list'] )."</select><br />
			<label for='mcm_list_item_wrapper_$value'>".__('Item Wrapper','my-content-management')."</label> <select name='templates[$value][wrapper][item][list]' id='mcm_list_item_wrapper_$value'>".mcm_option_list( $item, $template['wrapper']['item']['list'] )."</select>
			</p>
			<p>
			<label for='mcm_list_wrapper_$value'>".__('List Template','my-content-management')."</label><br /> <textarea name='templates[$value][list]' id='mcm_list_wrapper_$value' rows='2' cols='60'>".stripslashes(htmlentities($template['list']))."</textarea>
			</p>
			</fieldset>
			<p>
					<input type='submit' value='".sprintf( __('Save %s Templates','my-content-manager'), $label[2] )."' name='mcm_save_templates' class='button-primary' />
			</p>
			</div>
			</form>
			<h4>".__('Naming for theme templates','my-content-management')."</h4>
			<p>".__('Theme template for this taxonomy:','my-content-management')." <code>taxonomy-mcm_category_$display_value.php</code></p>
			<p>".__('Theme template for this custom post type:','my-content-management')." <code>single-mcm_$display_value.php</code></p>
			<p>".__('Theme template for archive pages with this post type:','my-content-management')." <code>archive-mcm_$display_value.php</code></p>		
			</div>			
		</div>";
		}
	}
	echo $return;
}

function mcm_option_list( $array, $current ) {
	$return = '';
	if ( is_array($array) ) {
		foreach ( $array as $key ) {
			$checked = ( $key == $current && $current != '' )?' selected="selected"':'';
			$return .= "<option value='$key'$checked>&lt;$key&gt; </option>\n";
		}
	}
	$checked = ($current == '')?' selected="selected"':'';
	$return .= "<option value=''$checked>".__('No wrapper','my-content-management')."</option>";
	return $return;
}

function mcm_show_support_box() {
?>
	<div id="support">
		<div class="resources">
		<ul>
		<li><strong><a href="#get-support" rel="external"><?php _e("Get Support",'my-content-management'); ?></a></strong></li>
		<li><a href="http://www.joedolson.com/articles/bugs/"><?php _e("Report a bug",'my-calendar'); ?></a></li>	
		<li><strong><a href="http://www.joedolson.com/donate.php" rel="external"><?php _e("Make a Donation",'my-content-management'); ?></a></strong></li>
			<li><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<div>
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="hosted_button_id" value="YP36SWZTDQAUL" />
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="Make a gift to support My Content Management!" />
			<img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
			</div>
			</form>
			</li>
			<li><a href="http://profiles.wordpress.org/users/joedolson/"><?php _e('Check out my other plug-ins','my-content-management'); ?></a></li>
			<li><a href="http://wordpress.org/extend/plugins/my-content-management/"><?php _e('Rate this plug-in','my-content-management'); ?></a></li>
		</ul>
		</div>
	</div>
<?php
}

// Add the administrative settings to the "Settings" menu.
function mcm_add_support_page() {
    if ( function_exists( 'add_submenu_page' ) ) {
		 $plugin_page = add_options_page( 'My Content Management', 'My Content Management', 'manage_options', __FILE__, 'mcm_settings_page' );
		 add_action( 'admin_head-'. $plugin_page, 'mcm_styles' );
    }
	add_action('admin_print_styles-'. $plugin_page, 'mcm_add_scripts');
 }
function mcm_styles() {
	if ( $_GET['page'] == "my-content-management/my-content-management.php" ) {
		echo '<link type="text/css" rel="stylesheet" href="'.plugins_url('mcm-styles.css', __FILE__ ).'" />';
?>
<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function($) {
		// close postboxes that should be closed
		$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		// postboxes setup
		postboxes.add_postbox_toggles('wrap');
	});
	//]]>
</script>
<?php
	}
}
add_action( 'admin_menu', 'mcm_add_support_page' );

function mcm_plugin_action($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__).'/my-content-management.php')) {
		$links[] = "<a href='options-general.php?page=my-content-management/my-content-management.php'>" . __('Get Support', 'my-content-management', 'my-content-management') . "</a>";
		$links[] = "<a href='http://www.joedolson.com/donate.php'>" . __('Donate', 'my-content-management', 'my-content-management') . "</a>";
	}
	return $links;
}
//Add Plugin Actions to WordPress

add_filter('plugin_action_links', 'mcm_plugin_action', -10, 2);