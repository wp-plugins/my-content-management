=== My Content Management ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate.php
Tags: custom post types, faq, testimonials, staff, glossary, sidebars, content management
Requires at least: 3.2.1
Tested up to: 3.3.1
Stable tag: trunk

Creates a set of common custom post types for advanced content management: FAQ, Testimonials, people (staff, contributors, etc.), and others!
 
== Description ==

My Content Management creates a suite of custom post types, each with an appropriate custom taxonomy and a set of commonly needed custom fields. The purpose of the plug-in is to provide a single common interface to create commonly needed extra content tools. 

Almost every web site I work on requires some kind of special content: testimonials, frequently asked questions, lists of staff -- you name it. There are plug-ins available for almost all of these - but they're all different. Different interfaces, different ways to display information, different default styling for how they're shown on the page. 

I wrote this plug-in so that I have all of these features available to me in a single installation: every one with the same interface, with common methods for displaying on a site. Each custom post type also includes a few commonly used custom fields available in templates. (e.g., a phone number field for listings of staff members.)

There's no default styling outside of whatever your theme offers for the elements used. There is default HTML, but it can be 100% replaced through the included templating system, or by creating your own theme template documents to display these specific content types. 

All content can be displayed using the shortcode [my_content type='custom_post_type']. Other supported attributes include:

* display (custom, full, excerpt, or list)
* taxonomy (name of associated taxonomy: required to get list of terms associated with post; include a term to limit by term)
* term (term within named taxonomy)
* count (number of items to display - default shows all)
* order (order to show items in - default order is "menu_order" )
* direction (whether sort is ascending, "ASC", or descending, "DESC" (default))
* meta_key ( custom field to sort by if 'order' is "meta_value" or "meta_value_num" )
* template ( set to a post type to use a template set by that post type. If "display" equals "custom", write a custom template. )
* offset (integer: skip a number of posts before display.)
* id ( comma separated list of IDs to show a set of posts; single ID to show a single post.)

A search form for any custom post type is accessible using the shortcode [custom_search type='custom_post_type']

You can create a site map for a specific post type and taxonomy using the [my_archive type='custom_post_type' taxonomy='taxonomy'] shortcode. Other supported attributes include all those above, plus:

* exclude (list of comma-separated taxonomy terms to exclude from the site map)

The "id" attribute is not supported in the [my_archive] shortcode. Because that would be silly.

Translations are always welcome! The translation files are included in the download.

== Changelog ==

= 1.1.0 =

* Added supplemental plug-in to provide a glossary filter for content and an alphabet anchor list for glossaries.
* Glossary post type has option to include headings to correspond to alphabet anchor list.
* Added shortcode to display archive of entire custom taxonomy organized by term. 
* Added option to use My Content Management shortcodes with any post type, not just those created by My Content Management
* Added generic additional post type called 'Resources'
* Added ability to use a custom template with a given shortcode. 
* Bug fix: Template manager didn't appear immediately when enabling first custom post type
* Bug fix: Errors if disabling all custom post types
* Bug fix: Template manager sometimes showed custom fields not related to the current custom post type.
* Bug fix: Upgrade routine could delete customized templates.
* Bug fix: Support/donate/plug-in links weren't clickable.

= 1.0.6 =

* Whoops! All apologies for 1.0.5. I made it worse. Too much of a hurry.

= 1.0.5 =

* Variable naming error in 1.0.4 caused problem in list wrapper output.

= 1.0.4 =

* Would you believe that I left out the ability to change the sort direction? Ridiculous.
* List wrapper was wrapped around items instead of lists.
* Setting list or item wrappers to 'none' left empty brackets
* Setting list or item wrappers to 'none' was not remembered in settings.
* fixed fopen error on servers with allow_url_fopen disabled

= 1.0.3 =

* Fixes two bugs with custom taxonomy limits, courtesy @nickd32

= 1.0.2 = 

* Defined custom fields for testimonials and quotes were not appearing.
* Added 'title' as a custom field for testimonials and quotes.
* Corrected a too-generically named constant.

= 1.0.1 =

* Removed a stray variable which was triggering a warning.
* Added an array check before running a foreach loop on a sometimes-absent value

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

 * 1.1.0 Adds the first supplemental plug-in, some new shortcode attributes, fixes bugs.