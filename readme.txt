=== Metronet Google Maps ===
Contributors: ojdavey, metronet
Author URI: http://metronet.no/
Plugin URL: http://metronet.no/
Requires at Least: 3.3
Tested up to: 3.5.1
Tags: maps, google
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily create Google Maps for your Wordpress site.

== Description ==

Metronet Google Maps allows you to create multiple Google Maps with multiple markers to insert anywhere
within your Wordpress site.

It has the following features:

- A very simple interface for adding maps and markers.
- A location finder which queries Google to get the latitude/longitude for your address.
- The ability to upload marker icons for each location.
- A WYSIWYG editor for adding rich content for each location.


== Installation ==

1. Upload 'metronet-google-maps' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click on "Google Maps" in the left hand menu.
4. Once you have created and saved your map, click "Get shortcode" and paste the value (e.g. [map id=70])
into your content area in the relevant page.

== Frequently Asked Questions ==

= Why does my map look a bit funky? =

Be careful that you don't have styles that are targeting any elements inside the map (such as img or div tags).
I've put in a few styles that fix common issues within the generic Wordpress themes, however you could have 
your own custom styles that are messing with HTML elements within the map.

= Why aren't my markers showing on the map? =

Make sure that once you've added your marker(s) that you remember to click "Save Map" to finish the process.
In the future I'm looking to add the ability to have the map saving automatically as you add new markers.

= Why is my map zoomed in so far? =

If you only add one location then the plugin bounds to that location, therefore zooming in as close as possible.
I'll be looking to add a zoom override in future updates. 

== Screenshots == 

1. Once you've saved your map, click "Get shortcode" to get the code that needs to be pasted where you want
the map to show within your content.
2. Select a title, width, height for your map and create as many markers as you like.
3. You can use the helpful latitude and longitude finder to get the appropriate values for your address. You can
also upload a custom marker image per location if you wish.
4. Voila! After pasting the shortcode into your content, you have a Google map that is bounded to your markers.

== Changelog ==

= 1.0.0 =
* Metronet Google Maps.
