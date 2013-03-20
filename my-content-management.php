<?php
/*
Plugin Name: My Content Management
Plugin URI: http://www.joedolson.com/articles/my-content-management/
Description: Creates a set of common custom post types for extended content management: FAQ, Testimonials, people lists, term lists, etc.
Author: Joseph C Dolson
Author URI: http://www.joedolson.com
Version: 1.3.3
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
$mcm_version = '1.3.3';
// Enable internationalisation
load_plugin_textdomain( 'my-content-management',false, dirname( plugin_basename( __FILE__ ) ) . '/lang' ); 

include(dirname(__FILE__).'/mcm-custom-posts.php' );
include(dirname(__FILE__).'/mcm-view-custom-posts.php' );
include(dirname(__FILE__).'/mcm-widgets.php' );

if ( !get_option( 'mcm_version' ) ) {  mcm_install_plugin(); }
if ( version_compare( get_option('mcm_version'), $mcm_version, '<' ) ) { mcm_upgrade_plugin(); }

function mcm_is_plugin_installed($plugin_dir) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$plugins = get_plugins($plugin_dir);
	if ($plugins) return true;
	return false;
}
if ( isset($_GET['ignore']) && $_GET['ignore'] == 'glossary' ) {
	update_option( 'mcm_glossary_ignore', 'true' );
}
if ( ! ( mcm_is_plugin_installed('/my-content-glossary') ) && get_option( 'mcm_glossary_ignore' ) != 'true' ) {
	function mcm_glossary_notice() {
		if ( current_user_can( 'install_plugins' ) ) {
			$install = admin_url('plugin-install.php?tab=search&s=my+content+glossary');
			$ignore = admin_url('options-general.php?page=my-content-management/my-content-management.php&ignore=glossary');
			echo "<div class='error'><p>The My Content Management Glossary Filter is now an independent plug-in and must be installed separately. <a href='$install'>Install My Content Glossary</a>. <a href='$ignore'>Ignore</a></p></div>";
		}
	}
	add_action('admin_notices', 'mcm_glossary_notice' );
}

// eventually, options. For now, not.
$mcm_options = get_option('mcm_options');
$mcm_enabled = $mcm_options['enabled'];
$mcm_templates = $mcm_options['templates'];
$mcm_types = $mcm_options['types'];
$mcm_fields = $mcm_options['fields'];
$mcm_extras = $mcm_options['extras'];

//Shortcode
function mcm_show_posts($atts) {
	extract(shortcode_atts(array(
				'type' => 'page',
				'display' => 'excerpt',
				'taxonomy' => 'all',
				'term' => '',
				'operator'=>'IN',
				'count' => -1,
				'order' => 'menu_order',
				'direction' => 'DESC',
				'meta_key' => '',
				'template' => '',
				'year' => '',
				'month' => '',
				'week' => '',
				'day' => '',
				'cache'=> false,
				'offset'=> false,
				'id' => false,
				'custom_wrapper'=>'div',
				'custom' => false
			), $atts));
	return mcm_get_show_posts( $type, $display, $taxonomy, $term, $count, $order, $direction, $meta_key, $template, $cache, $offset, $id, $custom_wrapper, $custom, $operator, $year, $month, $week, $day );
}

add_action('post_edit_form_tag', 'mcm_post_edit_form_tag');
function mcm_post_edit_form_tag() {
    echo ' enctype="multipart/form-data"';
}

function mcm_show_archive($atts) {
	extract(shortcode_atts(array(
				'type' => false,
				'display' => 'list',
				'taxonomy' => false,
				'count' => -1,
				'order' => 'menu_order',
				'direction' => 'DESC',
				'meta_key' => '',
				'exclude' => '',
				'include' => '',
				'template' => '',
				'offset' => '',
				'cache' => false,
				'show_links' => false,
				'custom_wrapper' => 'div',
				'custom' => false,
				'year' => '',
				'month' => '',
				'week' => '',
				'day' => ''
			), $atts));
			if ( !$type || !$taxonomy ) return;
		$terms = get_terms( $taxonomy );
		$output = '';
		$linker = "<ul class='archive-links'>";
		$exclude = explode(',',$exclude);
		$include = explode(',',$include);
		if ( is_array($terms) ) {
			foreach ( $terms as $term ) {
				$taxo = $term->name;
				$tax = $term->slug;
				$tax_class = sanitize_title($tax);
				if ( (!empty($exclude) && $exclude[0] != '' && !in_array( $tax, $exclude ))
					|| (!empty($include) && $include[0]!='' && in_array($tax, $include))
					|| $exclude[0]=='' && $include[0]=='' ) {
					$linker .= "<li><a href='#$tax_class'>$taxo</a></li>";
					$output .= "\n<div class='archive-group'>";
					$output .= "<h2 class='$tax_class' id='$tax_class'>$taxo</h2>";
					$output .= mcm_get_show_posts( $type, $display, $taxonomy, $tax, $count, $order, $direction, $meta_key, $template, $cache, $offset, false, $custom_wrapper, $custom, 'IN', $year, $month, $week, $day );
					$output .= "</div>\n";
				}
			}
		$linker .= "</ul>";
		}
		if ( $show_links == false ) { $linker = ''; } else { $linker = $linker; }
	return $linker . $output;
}

// filter to auto replace content with full template
add_filter( 'the_content','mcm_replace_content', 10, 2 );
function mcm_replace_content( $content, $id=false ) {
	global $template;
	if ( !is_main_query() && !$id ) { return $content; }
	$post_type = get_post_type();
	$mcm_options = get_option('mcm_options');
	if ( strpos( $template, $post_type ) !== false ) { return $content; }
	$enabled = $mcm_options['enabled'];
	if ( $enabled && is_singular( $enabled ) ) {
		$id = get_the_ID();
		$template = mcm_get_single_post( $post_type, $id );
		return $template;
	} else {
		return $content;
	}
}

function mcm_search_custom($atts) {
	extract(shortcode_atts(array(
				'type' => 'page'
			), $atts));
	return mcm_search_form( $type );
}

function mcm_munger($atts) {
	extract(shortcode_atts(array(
				'address' => ''
			), $atts));
	return mcm_munge( $address );
}
// Shortcodes 
add_shortcode('my_content','mcm_show_posts');
add_shortcode('custom_search','mcm_search_custom');
add_shortcode('my_archive','mcm_show_archive'); 
add_shortcode('email','mcm_munger');
// Filters
//add_filter('the_content', 'mcm_pre_process_shortcode', 7);
add_filter('pre_get_posts','mcm_searchfilter');
add_filter( 'post_updated_messages', 'mcm_posttypes_messages');

// Actions
add_action( 'init', 'mcm_taxonomies', 0);
add_action( 'init', 'mcm_posttypes' );
add_action( 'admin_menu', 'mcm_add_custom_boxes' );

//Theme support -- not having post thumbnails enabled can cause fatal errors when thumbnail is requested by info query.
function mcm_grant_support() {
	add_theme_support( 'post-thumbnails' );
}
add_action('after_setup_theme','mcm_grant_support');

function mcm_install_plugin() {
global $default_mcm_types, $default_mcm_fields, $default_mcm_extras;
$types = $default_mcm_types;
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
		'templates' => $templates,
		'types' => $default_mcm_types,
		'fields' => $default_mcm_fields,
		'extras' => $default_mcm_extras
	);
	if ( get_option( 'mcm_options' ) == '' ) { // this should protect against deleting changes.
		add_option( 'mcm_options', $options );
	}
}

function mcm_upgrade_plugin() {
//  no upgrade routine for 1.2.0
//  no upgrade routine for 1.3.0
	global $mcm_version,$default_mcm_types, $default_mcm_fields, $default_mcm_extras;;
	$from = get_option('mcm_version');
	if ( $mcm_version == $from ) { return; }
	switch ( $from ) {
		case '1.2.1':
		break;
		case '1.2.0':
	$options = get_option('mcm_options');
	$options['types'][]=$default_mcm_types;
	$options['fields']=$default_mcm_fields;
	$options['extras']=$default_mcm_extras;
	update_option( 'mcm_options', $options );
		default:
		break;
	}
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
	$wp_url = site_url();
	$language = get_bloginfo('language');
	$charset = get_bloginfo('charset');
	// server
	$php_version = phpversion();

	// theme data
	if ( function_exists( 'wp_get_theme' ) ) {
	$theme = wp_get_theme();
		$theme_name = $theme->Name;
		$theme_uri = $theme->ThemeURI;
		$theme_parent = $theme->Template;
		$theme_version = $theme->Version;	
	} else {
	$theme_path = get_stylesheet_directory().'/style.css';	
	$theme = get_theme_data($theme_path);
		$theme_name = $theme['Name'];
		$theme_uri = $theme['URI'];
		$theme_parent = $theme['Template'];
		$theme_version = $theme['Version'];
	}
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
	$request = '';
	if ( isset($_POST['mc_support']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'my-content-management-nonce') ) die("Security check failed");	
		$request = stripslashes($_POST['support_request']);
		$has_donated = ( $_POST['has_donated'] == 'on')?"Donor":"No donation";
		$has_purchased = ( $_POST['has_purchased'] == 'on')?"Purchaser":"No purchase";		
		$has_read_faq = ( $_POST['has_read_faq'] == 'on')?"Read FAQ":true; // has no faq, for now.
		$subject = "My Content Management support request. $has_donated $has_purchased";
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
		<code>".__('From:','my-content-management')." \"$current_user->display_name\" &lt;$current_user->user_email&gt;</code> &larr; ".__('Can\'t get email at this address? Provide a different one below.','my-content-management')."
		</p>
		<!--<p>
		<input type='checkbox' name='has_read_faq' id='has_read_faq' value='on' /> <label for='has_read_faq'>".__('I have read <a href="http://www.joedolson.com/articles/my-content-management/">the FAQ for this plug-in</a>.',$textdomain )." <span>(required)</span></label>
		</p>-->
		<p>
		<input type='checkbox' name='has_donated' id='has_donated' value='on' /> <label for='has_donated'>".__('I have <a href="http://www.joedolson.com/donate.php">made a donation to help support this plug-in</a>.',$textdomain )."</label>
		</p>
		<p>
		<input type='checkbox' name='has_purchased' id='has_purchased' value='on' /> <label for='has_purchased'>".__('I have <a href="http://www.joedolson.com/articles/my-content-management/guide/">purchased the User\'s Guide</a>, but could not find an answer to this question.','my-content-management')."</label>
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
	wp_register_script( 'addfields', plugins_url( 'js/jquery.addfields.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'addfields' );
}

function mcm_settings_page() {
global $mcm_enabled;
$enabled = $mcm_enabled;
$enabled = (isset($_POST['mcm_enabler']))?$_POST['mcm_posttypes']:$enabled;
?>
	<div class='wrap mcm-settings'>
	<div id="icon-index" class="icon32"><br /></div>
	<h2><?php _e('My Content Management','my-content-management'); ?></h2>
	<div class="postbox-container" style="width: 70%">
	
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="postbox">
		<h3><?php _e('Enable Custom Post Types','my-content-management'); ?></h3>
			<div class="inside">
			<?php mcm_updater(); ?>
			<form method='post' action='<?php echo admin_url('options-general.php?page=my-content-management/my-content-management.php'); ?>'>
				<div><input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('my-content-management-nonce'); ?>' /></div>
				<div>
				<?php mcm_enabler(); ?>
				<p>
					<input type='submit' value='<?php _e('Enable Selected Post Types','my-content-manager'); ?>' name='mcm_enabler' class='button-primary' /> <a href="<?php echo admin_url('options-general.php?page=my-content-management/my-content-management.php&mcm_add=new'); ?>"><?php _e('Add new post type','my-content-manager'); ?></a>
				</p>
				</div>
			</form>
			</div>			
		</div>
		<?php if ( !empty($enabled) ) { ?>
		<?php mcm_template_setter(); ?>
		<?php } ?>
		</div>
		</div>

		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="postbox" id="get-support">
		<h3><?php _e('Get Plug-in Support','my-content-management'); ?></h3>
			<div class="inside">
			<?php mcm_get_support_form(); ?>
			</div>
		</div>
		</div>
		</div>
	</div>
	<div class="postbox-container" style="width: 20%">
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="mcm-template-guide postbox" id="get-support">
		<h3><?php _e('Support My Content Management','my-content-management'); ?></h3>
			<div class="inside">
				<?php mcm_show_support_box(); ?>		
			</div>
		</div>
		</div>
		</div>	
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="mcm-template-guide postbox" id="get-support">
		<h3><?php _e('Basic Template Tags','my-content-management'); ?></h3>
			<div class="inside">
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

			<dt><code>{edit_link}</code></dt>
			<dd><?php _e('When logged in, display link to edit the current post.','my-content-management'); ?></dd>

			</dl>
			<p>
			<?php _e('Any custom field can also be referenced via shortcode, using the same pattern with the name of the custom field: <code>{custom_field_name}</code>','my-content-management'); ?>
			</p>
			</div>
		</div>
		</div>
		</div>
	</div>
</div>
<?php
}

function mcm_enabler() {
	if ( isset($_POST['mcm_enabler']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'my-content-management-nonce') ) die("Security check failed");		
		$enable = $_POST['mcm_posttypes'];
		$option = get_option('mcm_options');
		$option['enabled'] = $enable;
		update_option('mcm_options',$option);
		echo "<div class='updated fade'><p>".__('Enabled post types updated','my-content-management')."</p></div>";
	}
	$option = get_option('mcm_options');
	$enabled = $option['enabled'];
	$types = $option['types'];
	$checked = '';
	$return = '';
	if ( is_array($types) ) {
		foreach ( $types as $key=>$value ) {
			if ( $key && !is_int($key) ) {
				if ( is_array($enabled) ) {
					if ( in_array( $key, $enabled ) ) { $checked = ' checked="checked"'; } else { $checked = ''; }
				}
				$return .= "<li><input type='checkbox' value='$key' name='mcm_posttypes[]' id='mcm_$key'$checked /> <label for='mcm_$key'>$value[3] (<code>$key</code>) <small><a href='".admin_url("options-general.php?page=my-content-management/my-content-management.php&mcm_edit=$key")."'>".__('Edit','my-content-management')." '$value[3]'</a> &bull; <a href='".admin_url("options-general.php?page=my-content-management/my-content-management.php&mcm_delete=$key")."'>".__('Delete','my-content-management')."  '$value[3]'</a></small></label></li>\n";
			}
		}
	}
	echo "<ul class='mcm_posttypes'>".$return."</ul>";
}

function mcm_updater() {
	if ( isset($_POST['mcm_updater']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'my-content-management-nonce') ) die("Security check failed");	
		if ( !isset($_POST['mcm_new'] ) ) {
			$type = $_POST['mcm_type'];
			$option = get_option('mcm_options');
			$ns = $_POST[$type];
			$new = array( $ns['pt1'],$ns['pt2'],$ns['pt3'],$ns['pt4'],array( 'public' => ( isset($ns['public']) && $ns['public'] == 1 )?true:false,
					'publicly_queryable' => ( isset($ns['publicly_queryable']) && $ns['publicly_queryable'] == 1 )?true:false,
					'exclude_from_search'=> ( isset($ns['exclude_from_search']) && $ns['exclude_from_search']==1)?true:false,
					'show_in_menu' => ( isset($ns['show_in_menu']) && $ns['show_in_menu'] == 1 )?true:false,
					'show_ui' => ( isset($ns['show_ui']) && $ns['show_ui'] == 1 )?true:false, 
					'hierarchical' => ( isset($ns['hierarchical']) && $ns['hierarchical'] == 1 )?true:false,
					'menu_icon' => ( !isset($ns['menu_icon']) || $ns['menu_icon']=='')?null:$ns['menu_icon'],
					'supports' => $ns['supports'],
					'slug' => $ns['slug'] ) );
			$option['types'][$type] = $new;
			update_option('mcm_options',$option);			
			echo "<div class='updated fade'><p>".__('Post type settings modified.','my-content-management')."</p></div>";
		} else {
			$option = get_option('mcm_options');
			$ns = $_POST['new'];
			$type = 'mcm_'.sanitize_title($ns['pt1']);			
			$new = array( $ns['pt1'],$ns['pt2'],$ns['pt3'],$ns['pt4'],array( 'public' => ( isset($ns['public']) && $ns['public'] == 1 )?true:false,
					'publicly_queryable' => ( isset($ns['publicly_queryable']) && $ns['hierarchical'] == 1 )?true:false,
					'exclude_from_search'=> ( isset($ns['exclude_from_search']) && $ns['exclude_from_search']==1)?true:false,
					'show_in_menu' => ( isset($ns['show_in_menu']) && $ns['show_in_menu'] == 1 )?true:false,
					'show_ui' => ( isset($ns['show_ui']) && $ns['show_ui'] == 1 )?true:false,
					'hierarchical' => ( isset($ns['hierarchical']) && $ns['hierarchical'] == 1 )?true:false,					
					'menu_icon' => ( !isset($ns['menu_icon']) || $ns['menu_icon']=='')?null:$ns['menu_icon'],
					'supports' => $ns['supports'],
					'slug' => $ns['slug'] ) );
			$option['types'][$type] = $new;
			update_option('mcm_options',$option);		
			echo "<div class='updated fade'><p>".__('Added new custom post type.','my-content-management')."</p></div>";
		
		}
	}
	global $mcm_types;
	$types = $mcm_types;
	$checked = '';
	if ( isset( $_GET['mcm_delete']) ) { $message = mcm_delete_type( $_GET['mcm_delete'] ); echo $message; }
	if ( isset($_GET['mcm_edit']) ) { $type = $_GET['mcm_edit']; } else { $type = 'new'; }
	$before ="<div class='mcm_edit_post_type'><form method='post' action='".admin_url('options-general.php?page=my-content-management/my-content-management.php')."'>
				<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('my-content-management-nonce')."' /></div>			
				<div>";
	$post_typing = "<div><input type='hidden' name='mcm_type' value='$type' /></div>";
	$after = "<p>
					<input type='submit' value='".sprintf( __('Edit type "%1$s"','my-content-manager'), $type )."' name='mcm_updater' class='button-primary' /> <a href='".admin_url('options-general.php?page=my-content-management/my-content-management.php&mcm_add=new')."'>".__('Add new post type','my-content-management')."</a>
				</p>
				</div>
			</form></div>";
	$return = '';
	if ( is_array($types) ) {
		if ( $type != 'new' ) {
			$data = $types[$type];
		} else {
			$data = false;
		}
		if ( $data && isset($_GET['mcm_edit']) ) {
			if ( !isset($data[4]['slug']) ) { $data[4]['slug'] = $type; }
			$return = $before;
			$return .= $post_typing;
			$return .= "
			<p><label for='pt1'>".__('Singular Name, lower','my-content-management')."</label><br /><input type='text' name='${type}[pt1]' id='pt1' value='$data[0]' /></p>
			<p><label for='pt3'>".__('Singular Name, upper','my-content-management')."</label><br /><input type='text' name='${type}[pt3]' id='pt3' value='$data[2]' /></p>			
			<p><label for='pt2'>".__('Plural Name, lower','my-content-management')."</label><br /><input type='text' name='${type}[pt2]' id='pt2' value='$data[1]' /></p>
			<p><label for='pt4'>".__('Plural Name, upper','my-content-management')."</label><br /><input type='text' name='${type}[pt4]' id='pt4' value='$data[3]' /></p>			
			";
			foreach ( $data[4] as $key=>$value ) {
				if ( is_bool( $value ) ) {
					$checked = ($value == true)?' checked="checked"':'';
					$return .= "<p><input type='checkbox' name='${type}[$key]' value='1' id='$key'$checked /> <label for='$key'>".ucwords(str_replace('_',' ',$key))."</label></p>";				
				} else 
				if ( is_array( $value ) ) {
					$return .= "<p><label for='$key'>".ucwords(str_replace('_',' ',$key))."</label><br /><select multiple='multiple' name='${type}[${key}][]' id='$key'>";
					$supports = array( 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats' );
					foreach ( $supports as $s ) {
						$selected = ( in_array( $s, $value ) )?' selected="selected"':'';
						$return .= "<option value='$s'$selected>$s</option>";
					}
					$return .= "</select></p>";
				} else {
					$defaults = array( 'mcm_faqs','mcm_people','mcm_testimonials','mcm_locations','mcm_quotes','mcm_glossary','mcm_portfolio','mcm_resources');
					if ( !$value && in_array( $type, $defaults ) && $key=='menu_icon' ) { $value = plugins_url( 'images',__FILE__ )."/$type.png"; }
					$return .= "<p><label for='$key'>".ucwords(str_replace('_',' ',$key))."</label> <input type='text' name='${type}[$key]' size='32' value='$value' /></p>";				
				}
			}
			$return .= $after;
		}
	}
	if ( $type == 'new' && isset($_GET['mcm_add']) && $_GET['mcm_add']=='new' ) {
		global $d_mcm_args;
			$return = $before;
			$return .= "
			<p><label for='pt1'>".__('Singular Name, lower','my-content-management')."</label><br /><input type='text' name='new[pt1]' id='pt1' value='' /></p>
			<p><label for='pt3'>".__('Singular Name, upper','my-content-management')."</label><br /><input type='text' name='new[pt3]' id='pt3' value='' /></p>
			<p><label for='pt2'>".__('Plural Name, lower','my-content-management')."</label><br /><input type='text' name='new[pt2]' id='pt2' value='' /></p>
			<p><label for='pt4'>".__('Plural Name, upper','my-content-management')."</label><br /><input type='text' name='new[pt4]' id='pt4' value='' /></p>
			";
			foreach ( $d_mcm_args as $key=>$value ) {
				if ( is_bool( $value ) ) {
					$checked = ($value == true)?' checked="checked"':'';
					$return .= "<p><input type='checkbox' name='new[$key]' value='1' id='$key'$checked /> <label for='$key'>".ucwords(str_replace('_',' ',$key))."</label></p>";				
				} else 
				if ( is_array( $value ) ) {
					$return .= "<p><label for='$key'>".ucwords(str_replace('_',' ',$key))."</label><br /><select multiple='multiple' name='new[${key}][]' id='$key'>";
					$supports = array( 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats' );
					foreach ( $supports as $s ) {
						$selected = ( in_array( $s, $value ) )?' selected="selected"':'';
						$return .= "<option value='$s'$selected>$s</option>";
					}
					$return .= "</select></p>";
				} else {
					$return .= "<p><label for='$key'>".ucwords(str_replace('_',' ',$key))."</label> <input type='text' name='new[$key]' value='$value' /></p>";				
				}
			}
			$return .= "<p>
					<input type='hidden' name='mcm_new' value='new' />
					<input type='submit' value='".__('Add New Custom Post Type','my-content-manager')."' name='mcm_updater' class='button-primary' />
				</p>
				</div>
			</form></div>";
	}
	echo $return;
}

function mcm_delete_type( $type ) {
	$options = get_option('mcm_options');
	$types = $options['types'];
	$templates = $options['templates'];
	$enabled = $options['enabled'];
	if ( isset( $types[$type] ) ) {
		unset($options['types'][$type]);
		unset($options['templates'][$type]);
			$key = array_search( $type, $enabled );
			if ( $key ) { unset($options['enabled'][$key]);	}
		update_option('mcm_options',$options);
		return "<div class='updated fade'><p>".sprintf(__('Custom post type "%1$s" has been deleted.','my-content-management'),$type)."</p></div>";
	}
	return "<div class='error'><p>".sprintf(__('Custom post type "%1$s" was not found, and could not be deleted.','my-content-management'),$type)."</p></div>";
}

function mcm_template_setter() {
	if ( isset($_POST['mcm_save_templates']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'my-content-management-nonce') ) die("Security check failed");		
		$type = $_POST['mcm_post_type'];
		$option = get_option('mcm_options');
		$new = $_POST['templates'];
		$option['templates'][$type] = $new[$type];
		update_option('mcm_options',$option);
		echo "<div class='updated fade'><p>".__('Post Type templates updated','my-content-management')."</p></div>";
	}
	$option = get_option('mcm_options');
	$templates = $option['templates'];
	$enabled = $option['enabled'];
	$types = $option['types']; $fields = $option['fields']; $extras = $option['extras'];
	$return = '';
	$list = array('div','ul','ol','dl','section');
	$item = array('div','li','article');
	$default = array(
		'full' => "{title}
		{content}

		{link_title}", 
			'excerpt' => "{title}
		{excerpt}

		{link_title}", 
			'list' => "{link_title}",
			'wrapper' => array (
				'item' => 
					array ( 'full' => 'div', 'excerpt' => 'div', 'list' => 'li' ),
				'list' => 
					array ( 'full' => 'div', 'excerpt' => 'div', 'list' => 'ul' ) 
			) 
		);
	if ( is_array($enabled) ) {
		foreach ( $enabled as $value ) {
			if ( isset($types[$value] ) ) {
				$pointer = array();
				$display_value = str_replace('mcm_','',$value);
				$template = (isset($templates[$value]))?$templates[$value]:$default;
				$label = $types[$value];
				$extra_fields = array();
				foreach ( $extras as $k=>$v ) {
					if ( is_string($v[0]) && $v[0] == $value ) {
						$extra_fields[] = $fields[$k];
						$pointer[] = $value;
					} else {
						if ( is_array( $v[0] ) ) {
							foreach ( $v[0] as $ka => $va ) {
								if ( $va == $value ) {
									$extra_fields[] = $fields[$k];
									$pointer[] = $value; 
								}
							}
						}
					}
				}
				if ( !in_array( $value, $pointer ) ) { $extra_fields = false; }
				$show_fields = '';
				if ( is_array( $extra_fields ) ) {
					foreach ( $extra_fields as $k=>$v ) {
						if ( is_array( $v ) ) {
							foreach ( $v as $f ) {
								$show_fields .= "<p><code>&#123;$f[0]&#125;</code>: $f[1]</p>";
							}
						} else {
							$show_fields .= "<p><code>&#123;$v[0]&#125;</code>: $v[1]</p>";						
						}
					}
				} else {
					$show_fields = '';
				}
				$extension = '';
				if ( $value == 'mcm_glossary' && function_exists('mcm_set_glossary') ) { $extension = "<h4>Glossary Extension</h4>
				<p>".__('The glossary extension to My Content Management is enabled.','my-content-management')."</p>
				<ul>
				<li><code>[alphabet numbers='true']</code>: ".__('displays list of linked first characters represented in your Glossary. (Roman alphabet only, including numbers 0-9 by default.)','my-content-management')."</li>
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
				<p>Sample shortcode: <code>[my_content type='$display_value' display='full' taxonomy='mcm_category_$display_value' order='menu_order']</code></p>
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
			} else {
				//unset here? JCD
			}
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
		<div class="buy">
		<a href="http://www.joedolson.com/articles/my-content-management/guide/" rel="external"><?php _e("Need Help? <strong>Buy the User's Guide</strong>",'my-content-management'); ?></a>
		</div>
		<div class="resources">
		<p>
		<a href="https://twitter.com/intent/tweet?screen_name=joedolson&text=My%20Content%20Management%20is%20awesome!" class="twitter-mention-button" data-size="large" data-related="joedolson">Tweet to @joedolson</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>		
		<ul>
		<li><strong><a href="#get-support" rel="external"><?php _e("Get Support",'my-content-management'); ?></a></strong> &bull; <a href="http://www.joedolson.com/articles/bugs/"><?php _e("Report a bug",'my-content-management'); ?></a></li>	
			<li><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<div>
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="hosted_button_id" value="YP36SWZTDQAUL" />
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="Make a gift to support My Content Management!" />
			<img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
			</div>
			</form>
			</li>
			<li><a href="http://profiles.wordpress.org/users/joedolson/"><?php _e('Check out my other plug-ins','my-content-management'); ?></a></li>
			<li><a href="http://wordpress.org/extend/plugins/my-content-management/"><?php _e('Rate this plug-in','my-content-management'); ?></a></li>
			<li><a href='http://translate.joedolson.com/projects/my-content-management'><?php _e('Help translate this plug-in','my-content-management'); ?></a></li>
		</ul>
		</div>
	</div>
<?php
}

add_action( 'admin_menu','mcm_add_fields_pages');

function mcm_add_fields_pages() {
    if ( function_exists( 'add_submenu_page' ) ) {
		$post_types = get_post_types('','object');
		$submenu_page = add_submenu_page( "edit.php", "Posts > My Content Management > Custom Fields", "Custom Fields", 'manage_options', "post_fields", 'mcm_assign_custom_fields' );
		add_action( 'admin_head-'. $submenu_page, 'mcm_styles' );		
		foreach ( $post_types as $type ) {
			$name = $type->name;
			$label = $type->labels->name;
			$submenu_page = add_submenu_page( "edit.php?post_type=$name", "$label > My Content Management > Custom Fields", "Custom Fields", 'manage_options', $name."_fields", 'mcm_assign_custom_fields' );
			add_action( 'admin_head-'. $submenu_page, 'mcm_styles' );
		}
    }
}

add_action( 'admin_head', 'mcm_admin_style' );
function mcm_admin_style() {
?>
<style type='text/css'>
#normal-sortables .mcm_field { float: left; width:50%; }
</style>
<?php
}

function mcm_assign_custom_fields() {
?>
<div class="wrap">
<div id="icon-index" class="icon32"><br /></div>
<h2><?php _e('My Content Management &raquo; Manage Custom Fields','my-content-management'); ?></h2>
	<div class="postbox-container" style="width: 70%">
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="postbox" id="mcm-settings">
		<h3><?php _e('Custom Fields Assigned to this post type','my-content-management'); ?></h3>
			<div class="inside">
			<p><?php _e('Select the sets of custom fields enabled for this post type','my-content-management'); ?></p>
			<?php $current_post_type = $_GET['page']; $page = ( isset($_GET['post_type']) )?$_GET['post_type']:'post'; ?>
			<form method='post' action='<?php echo esc_url( admin_url("edit.php?post_type=$page&page=$current_post_type") ); ?>'>
				<div><input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('my-content-management-nonce'); ?>' /></div>
				<div>
				<?php mcm_fields( 'assign', $page ); ?>
				<p>
					<input type='submit' value='<?php _e('Update Assignments','my-content-manager'); ?>' name='mcm_custom_fields' class='button-primary' /> <a href="<?php echo admin_url("options-general.php?page=mcm_custom_fields&mcm_fields_add=new"); ?>"><?php _e('Add new custom field set','my-content-manager'); ?></a>
				</p>
				</div>
			</form>
			</div>			
		</div>
		</div>
		</div>
	</div>
	<div class="postbox-container" style="width: 20%">
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="postbox" id="mcm-settings">
		<h3><?php _e('Support My Content Management','my-content-management'); ?></h3>
			<div class="inside">	
				<?php mcm_show_support_box(); ?>
			</div>
		</div>
		</div>
		</div>
	</div>
	
</div>
	<?php
}

function mcm_add_custom_field_support( $fieldset, $page ) {
	$option = get_option('mcm_options');
	$array = $option['extras'][$fieldset][0];
	if ( is_string($array) ) { $array = array( $array ); }
	if ( !in_array( $page, $array ) ) { array_push( $array, $page ); }
	$option['extras'][$fieldset][0] = $array;
	update_option( 'mcm_options', $option );
}

function mcm_delete_custom_field_support( $fieldset, $page ) {
	$option = get_option('mcm_options');
	$array = $option['extras'][$fieldset][0];
	if ( is_string($array) ) { $array = array(); }
	if ( in_array( $page, $array ) ) { $key = array_search( $page, $array ); unset( $array[$key] ); }
	$option['extras'][$fieldset][0] = $array;
	update_option( 'mcm_options', $option );
}

function mcm_fields( $show='assign',$post_type=false ) {
	if ( isset($_POST['mcm_custom_fields']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'my-content-management-nonce') ) die("Security check failed");		
		$extras = $_POST['mcm_field_extras'];
		$page = $post_type;
		foreach ( $extras as $key => $value ) {
			if ( $value == 'on' ) {
				mcm_add_custom_field_support( $key, $page );
			} else {
				mcm_delete_custom_field_support( $key, $page );
			}
		}		
		echo "<div class='updated fade'><p>".__('Custom fields for this post type updated','my-content-management')."</p></div>";
	}
	$option = get_option('mcm_options');
	$extras = $option['extras'];
	$checked = '';
	$return = '';
	if ( is_array($extras) ) {
		foreach ( $extras as $key=>$value ) {
			$page = $post_type;
			$checked_off = ' checked="checked"';
			if ( is_string( $value[0] ) ) {
				$checked_on = ( $value[0] == $page )?' checked="checked"':'';
			} else if ( in_array( $page, $value[0] ) ) {
				$checked_on = ( in_array( $page, $value[0] ) )?' checked="checked"':'';
			} else {
				$checked_off = ' checked="checked"';
				$checked_on = ''; 
			}
			$k = urlencode($key);
			if ( $show == 'assign' ) {
				$return .= "<li><fieldset><legend>$key</legend>
				<input type='radio' value='off' name='mcm_field_extras[$key]' id='mcm_off_$page'$checked_off /> <label for='mcm_off_$page'>".__('Off','my-content-management')."</label> 
				<input type='radio' value='on' name='mcm_field_extras[$key]' id='mcm_off_$page'$checked_on /> <label for='mcm_off_$page'>".__('On','my-content-management')." <small><a href='".admin_url("options-general.php?page=mcm_custom_fields&mcm_fields_edit=$k")."'>".__('Edit','my-content-management')."</a></small></label>
				</fieldset></li>\n";
			} else {
				$return .= "<li><a href='".admin_url("options-general.php?page=mcm_custom_fields&mcm_fields_edit=$k")."'>".__('Edit','my-content-management')." $key</a></li>";
			}
		}
	}
	echo "<ul class='mcm_customfields'>".$return."</ul>";
}

function mcm_fields_updater() {
	// This is what I do to edit custom field sets or add new sets.
	if ( !isset( $_GET['mcm_fields_edit'] ) ) {
		mcm_get_fieldset();
	}
	if ( isset($_POST['mcm_custom_field_sets']) ) {
		$message = mcm_update_custom_fieldset( $_POST );
	}	
	if ( isset( $_GET['mcm_fields_edit'] ) ) {
		mcm_get_fieldset( $_GET['mcm_fields_edit'] );
	}
}

function mcm_get_fieldset( $fieldset=false ) {
	$option = get_option( 'mcm_options' );
	if ( !$fieldset ) {
		$posts = get_post_types(array( 'public'=>'true' ) ,'object');
		$post_types = '';
		foreach ( $posts as $value ) {
			$name = $value->name;
			$label = $value->labels->name;
			$post_types .= "<option value='$name'>$label</option>\n";
		}
		$fieldset_title = "<p><label for='mcm_new_fieldset'><strong>".__('New Fieldset Title','my-content-management')."</strong></label> <input type='text' id='mcm_new_fieldset' name='mcm_new_fieldset' /></p><p><label for='mcm_assign_to'><strong>".__('Attach to','my-content-management')."</strong></label><br /><select name='mcm_assign_to' id='mcm_assign_to' multiple='multiple'>$post_types</select></p>";
	} else {
		$fieldset_title = '';
	}
	
	$form = $fieldset_title.'<table class="widefat"><thead><tr><th scope="col">'.__('Field Label','my-content-management').'</th><th scope="col">'.__('Input Type','my-content-management').'</th><th scope="col">Description/Options</th><th  scope="col">'.__('Delete','my-content-management').'</th></tr></thead><tbody>';
	$odd = 'odd'; 
	if ( isset( $option['fields'][$fieldset] ) ) {
		$fields = ( $fieldset )?$option['fields'][urldecode($fieldset)]:'';
	}
		$field_types = array(
			'text'=>__('Single line of text','my-content-management'),
			'textarea'=>__('Multiple lines of text','my-content-management'),
			'select'=>__('Select dropdown','my-content-management'),
			'upload'=>__('File upload','my-content-management'),
			'color'=>__('Color input / HTML5','my-content-management'),
			'date'=>__('Date input / HTML5','my-content-management'),
			'tel'=>__('Telephone / HTML5','my-content-management'),
			'time'=>__('Time / HTML5','my-content-management'),
			'url'=>__('URL / HTML5','my-content-management'),
			'email'=>__('Email / HTML5','my-content-management')
			);	
	if ( $fieldset && isset( $option['fields'][$fieldset] ) ) {
		foreach ( $fields as $key=>$value ) {
			if ( is_array( $value[2] ) ) {
				$choices = esc_attr( stripslashes( implode( ', ', $value[2] ) ) );
			} else {
				$choices = esc_attr(stripslashes($value[2]));
			}
			$field_type_select = '';
			foreach ( $field_types as $k => $v ) {
				$selected = ( $value[3] == $k  || ( $k == 'text' && $value[3] == 'mcm_text_field' ) )?' selected="selected"':'';
				$field_type_select .= "<option value='$k'$selected>$v</option>\n";
			}
			if ( $value[3] == 'select' ) { $labeled = __("Options",'my-content-management'); } else { $labeled = __("Additional Text",'my-content-management'); }
		$form .= "
		<tr class='mcm_custom_fields_form $odd'>
			<td>
				<input type='hidden' name='mcm_field_key[$key]'  value='$value[0]' />
				<label for='mcm_field_label$key'>".__('Label','my-content-management')."</label> <input type='text' name='mcm_field_label[$key]' id='mcm_field_label$key' value='".esc_attr(stripslashes($value[1]))."' /><br /><small>{<code>$value[0]</code>}</small>
			</td>
			<td>
				<label for='mcm_field_type$key'>".__('Type','my-content-management')."</label> 
					<select name='mcm_field_type[$key]' id='mcm_field_type$key'>
					$field_type_select
					</select>
			</td>
			<td>
				<label for='mcm_field_options$key'>$labeled</label> <input type='text' name='mcm_field_options[$key]' id='mcm_field_options$key' value='$choices' />
			</td>
			<td>
				<label for='mcm_field_delete$key'>".__('Delete','my-content-management')."</label> <input type='checkbox' name='mcm_field_delete[$key]' id='mcm_field_delete$key' value='delete' />
			</td>
		</tr>";
		$odd = ( $odd == 'odd' ) ? 'even' : 'odd';
		}
	} else if ( $fieldset && !isset( $option['fields'][$fieldset] ) ) {
		echo "<div class='updated error'><p>".__('There is no field set by that name','my-content-management')."</p></div>";
	}
		$field_type_select = '';
		foreach ( $field_types as $k => $v ) {
			$field_type_select .= "<option value='$k'>$v</option>";
		}	
	$form .= "
	<tr class='mcm_custom_fields_form clonedInput' id='field1'>
		<td>
			<input type='hidden' name='mcm_field_key[]'  value='' />
			<label for='mcm_field_label'>".__('Label','my-content-management')."</label> <input type='text' name='mcm_field_label[]' id='mcm_field_label' value='' />
		</td>
		<td>
			<label for='mcm_field_type'>".__('Type','my-content-management')."</label> 
				<select name='mcm_field_type[]' id='mcm_field_type'>
				$field_type_select
				</select>
		</td>
		<td>
			<label for='mcm_field_options'>".__('Options/Additional Text','my-content-management')."</label> <input type='text' name='mcm_field_options[]' id='mcm_field_options' value='' />
		</td>
		<td>
		</td>
	</tr>";	
	$form .= '</tbody></table>';
		$add_field =__('Add another field','my-content-management');
		$del_field = __('Remove last field','my-content-management');
	$form .=  '
			<p>
				<input type="button" class="add_field" value="'.$add_field.'" class="button" />
				<input type="button" class="del_field" value="'.$del_field.'" class="button" />
			</p>';

	echo $form;
}

function mcm_update_custom_fieldset( $post ) {
	$option = get_option('mcm_options');
	$array = array();
	if ( !isset($post['mcm_field_delete']) ) { $post['mcm_field_delete'] = array(); }
	$delete = @array_keys( $post['mcm_field_delete'] );
	$keys = $post['mcm_field_key'];
	$labels = $post['mcm_field_label'];
	$types = $post['mcm_field_type'];
	$options = $post['mcm_field_options'];
	$count = count( $labels );
	$delete_count = count( $delete );
		for ( $i=0;$i<$count;$i++ ) {
			if ( in_array( $i, $delete ) ) { } else {
				if ( $keys[$i] != '' ) {
				if ( $types[$i] == 'select' ) { $opt = explode( ',', $options[$i] ); } else { $opt = $options[$i]; }
				$array[$i] = array( 
					$keys[$i], 
					$labels[$i], 
					$opt, 
					$types[$i] 
				);
				} else if ( $labels[$i] != '' ) {
				if ( $types[$i] == 'select' ) { $opt = explode( ',', $options[$i] ); } else { $opt = $options[$i]; }		
				$k = '_'.sanitize_title($labels[$i]);
				$array[$i] = array( 
					$k, 
					$labels[$i], 
					$opt, 
					$types[$i] 
				);	
				} else {
					continue;
				}
			}
		}
	$fieldset = ( isset($_GET['mcm_fields_edit']) )?$_GET['mcm_fields_edit']:false;
	if ( isset( $post['mcm_new_fieldset'] ) ) { $fieldset = $post['mcm_new_fieldset']; $added = __('added','my-content-management'); } else { $added = __('updated','my-content-management'); }
	if ( !empty( $option['extras'][$fieldset] ) && isset( $post['mcm_new_fieldset'] ) ) { $fieldset = $fieldset.' (2)'; }
	if ( !$fieldset ) { return __("No custom field set was defined.",'my-content-management'); } else { $fieldset = urldecode( $fieldset ); }
	if ( isset( $post['mcm_new_fieldset'] ) ) { $mcm_assign_to = $post['mcm_assign_to']; $option['extras'][$fieldset] = array( $mcm_assign_to, 'side' ); }
	$option['fields'][$fieldset] = $array;
	if ( $count == $delete_count || $delete_count > $count || ( $count == 1 && !isset( $post['mcm_new_fieldset'] ) ) ) { unset( $option['fields'][$fieldset] ); unset( $option['extras'][$fieldset] ); } // if all fields are deleted, remove set.
	update_option('mcm_options',$option);
	return sprintf( __('You have %1$s the %2$s group of custom fields.', 'my-content-management' ), $added, $fieldset );
}

// Add the administrative settings to the "Settings" menu.
function mcm_add_support_page() {
    if ( function_exists( 'add_options_page' ) ) {
		 $plugin_page = add_options_page( 'My Content Management', 'My Content Management', 'manage_options', __FILE__, 'mcm_settings_page' );
		 add_action( 'admin_head-'. $plugin_page, 'mcm_styles' );
		 add_action('admin_print_styles-'. $plugin_page, 'mcm_add_scripts');
		 $plugin_page = add_options_page( 'My Custom Fields', 'My Custom Fields', 'manage_options', 'mcm_custom_fields', 'mcm_configure_custom_fields' );	 
		 add_action( 'admin_head-'. $plugin_page, 'mcm_styles' );
		 add_action('admin_print_styles-'. $plugin_page, 'mcm_add_scripts');
		 
	}
}


function mcm_configure_custom_fields() {
?>
<div class="wrap">
<div id="icon-index" class="icon32"><br /></div>
<h2><?php _e('My Content Management &raquo; Manage Custom Fields','my-content-management'); ?></h2>

	<div class="postbox-container" style="width: 70%">
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="postbox" id="mcm-settings">
		<h3><?php _e('Manage Custom Fieldsets','my-content-management'); ?></h3>
			<div class="inside">
<?php
	if ( isset($_POST['mcm_custom_fieldsets']) ) {
		$message = mcm_update_custom_fieldset( $_POST );
	} else {
		$message = false;
	}
	if ( $message ) { echo "<div class='updated notice'><p>$message</p></div>"; }
?>	
			<?php mcm_fields( 'edit' ); ?>
			<p><?php _e('If the input type is a Select box, enter the selectable options as a comma-separated list in the Description/Options field.','my-content-management'); ?></p>
			<?php if ( isset($_GET['mcm_fields_edit'] ) ) { $append = "&mcm_fields_edit=".urlencode($_GET['mcm_fields_edit']); } else { $append = ''; } ?>
			<form method='post' action='<?php echo esc_url( admin_url("options-general.php?page=mcm_custom_fields$append") ); ?>'>
				<div><input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('my-content-management-nonce'); ?>' /></div>
				<div>
				<?php mcm_fields_updater(); ?>
				<p>
					<input type='submit' value='<?php _e('Update Custom Fieldsets','my-content-manager'); ?>' name='mcm_custom_fieldsets' class='button-primary' /> <a href="<?php echo admin_url("options-general.php?page=mcm_custom_fields&mcm_fields_add=new"); ?>"><?php _e('Add new custom field set','my-content-manager'); ?></a>
				</p>
				</div>
			</form>
			<p><?php printf(__('HTML5 Input types have limited browser support. <a href="%s">Check whether your browser supports these fields</a>','my-content-management'),'http://miketaylr.com/code/input-type-attr.html'); ?></p>					
			</div>	
		</div>
		</div>
		</div>
	</div>
	
	<div class="postbox-container" style="width: 20%">
		<div class="metabox-holder">
		<div class="mcm-settings ui-sortable meta-box-sortables">   
		<div class="mcm-template-guide postbox" id="get-support">
		<h3><?php _e('Support My Content Management','my-content-management'); ?></h3>
			<div class="inside">
				<?php mcm_show_support_box(); ?>		
			</div>
		</div>
		</div>
		</div>
	</div>
</div>
	<?php
}

function mcm_styles() {
	//if ( $_GET['page'] == "my-content-management/my-content-management.php" || $_GET['page'] == 'mcm_custom_fields' || ) {
		echo '<link type="text/css" rel="stylesheet" href="'.plugins_url('mcm-styles.css', __FILE__ ).'" />';
	//}
}
add_action( 'admin_menu', 'mcm_add_support_page' );

function mcm_plugin_action($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__).'/my-content-management.php')) {
		$links[] = "<a href='options-general.php?page=my-content-management/my-content-management.php'>" . __('Settings', 'my-content-management', 'my-content-management') . "</a>";
		$links[] = "<a href='http://www.joedolson.com/donate.php'>" . __('Donate', 'my-content-management', 'my-content-management') . "</a>";
	}
	return $links;
}
//Add Plugin Actions to WordPress

function mcm_add_styles() {
	if ( file_exists( get_stylesheet_directory() . '/my-content-management.css' ) ) {
		$stylesheet = get_stylesheet_directory_uri() . '/my-content-management.css';
		echo "<link rel=\"stylesheet\" href=\"$stylesheet\" type=\"text/css\" media=\"all\" />";
	} 
}

function mcm_add_js() {
	if ( file_exists( get_stylesheet_directory() . '/my-content-management.js' ) ) {
		$scripts = get_stylesheet_directory_uri() . '/my-content-management.js';
		echo "<script type='text/javascript' src=\"$scripts\"></script>";
	} 
}

add_action( 'wp_footer','mcm_add_js' );
add_action( 'wp_head','mcm_add_styles' );

add_filter('plugin_action_links', 'mcm_plugin_action', -10, 2);