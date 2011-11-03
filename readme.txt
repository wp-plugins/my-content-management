=== My Content Management ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate.php
Tags: custom post types, faq, testimonials, staff, glossary, sidebars, content management
Requires at least: 3.2.1
Tested up to: 3.3 beta
Stable tag: trunk

Creates a set of common custom post types for advanced content management: FAQ, Testimonials, people (staff, contributors, etc.), and others!
 
== Description ==

My Content Management creates a suite of custom post types, each with an appropriate custom taxonomy and a set of commonly needed custom fields. The purpose of the plug-in is to provide a single common interface to create commonly needed extra content tools. 

Almost every web site I work on requires some kind of special content: testimonials, frequently asked questions, lists of staff -- you name it. There are plug-ins available for almost all of these - but they're all different. Different interfaces, different ways to display information, different default styling for how they're shown on the page. 

I wrote this plug-in so that I have all of these features available to me in a single installation: every one with the same interface, with common methods for displaying on a site. Each custom post type also includes a few commonly used custom fields available in templates. (e.g., a phone number field for listings of staff members.)

There's no default styling outside of whatever your theme offers for the elements used. There is default HTML, but it can be 100% replaced through the included templating system, or by creating your own theme template documents to display these specific content types. 

All content can be displayed using the shortcode [my_content type='custom_post_type']. Other supported attributes include:

* display (full, excerpt, or list)
* taxonomy (name of associated taxonomy: required to get list of terms associated with post; include a term to limit by term)
* term (term within named taxonomy)
* count (number of items to display - default shows all)
* order (order to show items in - default order is "menu_order" )
* meta_key ( custom field to sort by if 'order' is "meta_value" or "meta_value_num" )
* id ( comma separated list of IDs to show a set of posts; single ID to show a single post.)

A search form for any custom post type is accessible using the shortcode [custom_search type='custom_post_type']

Translations are always welcome! The translation files are included in the download.

== Changelog ==

= 1.0.0 =

* Initial release

== Installation ==

1. Upload the `my-content-management` folder to your `/wp-content/plugins/` directory
2. Activate the plugin using the `Plugins` menu in WordPress
3. Visit the settings page at Settings > My Content Management to enable your needed content types.
4. Visit the appropriate custom post types sections to edit and create new content.
5. Use built-in widgets or shortcodes to display content. (Advanced users can create custom theme templates for displays.)

== Frequently Asked Questions ==

= Why don't you have any questions here? =

Hey. This was just launched. Got one to ask?

== Screenshots ==

1. Settings Page

== Upgrade Notice ==

* No notes yet!