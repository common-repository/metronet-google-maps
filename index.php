<?php
/*
Plugin Name: Metronet Google Maps
Plugin URI: http://metronet.no/
Description: Gives the ability to create multiple Google Maps with multiple markers anywhere on your WP site
Version: 1.0.0
Author: Owen Davey / Metronet
Author URI: http://metronet.no/

------------------------------------------------------------------------
Copyright Metronet AS

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/


/**
 * Do not continue processing since file was called directly
 * 
 * @since 1.0
 * @author Owen Davey <owen@metronet.no>
 */
if ( !defined( 'ABSPATH' ) )
	die( 'Eh! What you doin in here?' );

/**
 * Load classes
 * 
 * @since 1.0
 * @author Owen Davey <owen@metronet.no>
 */
require( 'class-maps.php' );

/**
 * Define constants
 * 
 * @since 1.0
 * @author Owen Davey <owen@metronet.no>
 */

define( 'MAPS_DIR', rtrim( plugin_dir_path(__FILE__), '/' ) ); // Plugin folder DIR
define( 'MAPS_URL', rtrim( plugin_dir_url(__FILE__), '/' ) ); // Plugin folder URL

/**
 * Instantiate admin panel
 * 
 * @since 1.0
 * @author Owen Davey <owen@metronet.no>
 */
add_action( 'wp_loaded', 'mn_google_maps_init', 100 ); //Load low priority in init for other plugins to generate their post types
function mn_google_maps_init() {
	// Instantiate new reordering
	new Maps(
		array(
			'heading'     => __( 'Google Maps', 'maps' ),
			'final'       => '',
			'initial'     => '',
			'menu_label'  => __( 'Google Maps', 'maps' ),
			'icon'        => MAPS_URL . '/metronet-icon.png',
			'icon_small'        => MAPS_URL . '/metronet-icon-small.png',
		)
	);
	
	register_post_type( 'map', array(
		'public' => true,
		'show_ui' => false,
		'capability_type' => 'post',
		'hierarchical' => false,
		'has_archive' => false,
		'taxonomies' => array(''),
		'query_var' => true,
		'menu_position' => 7,
		'supports' => array(
			'title',
			'content'
		),
	));
	
	register_post_type( 'map-marker', array(
		'public' => false,
		'show_ui' => false,
		'capability_type' => 'post',
		'hierarchical' => false,
		'has_archive' => false,
		'taxonomies' => array(''),
		'query_var' => true,
		'menu_position' => 7,
		'supports' => array(
			'title',
			'content'
		),
	));
} //end mn_google_maps_init

function maps_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'id' => '-1',
	), $atts ) );
	if($id == -1) {
		return "";
	}
	$theMap = get_post($id);
	
	if($theMap->post_type != 'map') {
		return "";
	}
	
	$theMarkers = get_posts(array(
		"post_parent" => $id, 
		"post_type" => "map-marker", 
		"posts_per_page" => -1,
	));

	$width = get_post_meta($id, "map_width", true);
	if(!$width) {
		$width = "100%";
	}
	else if(strpos($width, "px") === false && strpos($width, "%") === false) {
		$width .= "px";
	}
	$height = get_post_meta($id, "map_height", true);
	if(!$height) {
		$height = "250px";
	}
	else if(strpos($height, "px") === false) {
		$height .= "px";
	}
	$html = "";
	ob_start();
	?>
	<script src="http://maps.google.com/maps/api/js?sensor=true&amp;language=en" type="text/javascript"></script>
	<div id="map" style="width: <?php echo $width ?>; height: <?php echo $height ?>"></div>
	<style>
		/* Overriding some default WP styles */
		#map img {
			max-width:none !important;
			margin: 0px !important;
		}
		/* Without this, Google Maps adds scrollbars in infowindow */
		#map p {
			margin: 0px !important;
		}
	</style>
	<script type="text/javascript">
		var infowindow = new google.maps.InfoWindow();
		var map;
		var point;
		var latlngs = new Array();

		function init() {
			var mapOptions = {
				  center: new google.maps.LatLng(0, 0),
				  mapTypeId: google.maps.MapTypeId.ROADMAP
			};

			map = new google.maps.Map(document.getElementById("map"), mapOptions);

			<?php
				foreach($theMarkers as $theMarker) {
					$theContent = $theMarker->post_content;
					$theTitle = $theMarker->post_title;
					$latitude = get_post_meta($theMarker->ID, "marker_latitude", true);
					$longitude = get_post_meta($theMarker->ID, "marker_longitude", true);
					$icon = get_post_meta($theMarker->ID, "marker_icon", true);

					?>
						point = new google.maps.LatLng(<?php echo $latitude ?>, <?php echo $longitude ?>);
						latlngs.push(point);
						createMarker(point, <?php echo json_encode($theContent) ?>, <?php echo json_encode($theTitle) ?>, "<?php echo $icon ?>");	
					<?php
				}
			?>
			var latlngbounds = new google.maps.LatLngBounds();
			for(var i=0; i<latlngs.length; i++) {
				latlngbounds.extend(latlngs[i]);
			}

			map.setCenter(latlngbounds.getCenter());
			map.fitBounds(latlngbounds); 
		}

		
		function createMarker(point, text, title, icon) {
			  var marker = new google.maps.Marker({
				position: point, 
				map: map,
				title: title,
				icon: icon
			  });
			  
			  google.maps.event.addListener(marker, 'click', function() {
				infowindow.close()
				infowindow.setContent(text);
				infowindow.open(map, marker);
			  });
		}
		window.onload = init;
	</script>
	<?php
	$html = ob_get_clean();
	return $html;
}
add_shortcode( 'map', 'maps_shortcode' );
