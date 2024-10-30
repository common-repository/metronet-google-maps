<?php
/**
 * Metronet Google Maps
 * 
 * @package    WordPress
 * @subpackage Metronet Google Maps plugin
 */


/**
 * Google Maps
 * Gives the ability to create multiple google maps with multiple markers anywhere on your WP site
 * 
 * 
 * 
 * @copyright Copyright (c), Metronet
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Owen Davey <owen@metronet.no>
 * @since 1.0
 */
class Maps {
	/**
	 * @var $heading 
	 * @desc Admin page heading
	 * @access private
	 */
	private $heading;

	/**
	 * @var $initial 
	 * @desc HTML outputted at end of admin page
	 * @access private
	 */
	private $initial;

	/**
	 * @var $final 
	 * @desc HTML outputted at end of admin page
	 * @access private
	 */
	private $final;

	/**
	 * @var $menu_label 
	 * @desc Admin page menu label
	 * @access private
	 */
	private $menu_label;
	
	/**
	 * @var $icon
	 * @desc Admin page icon
	 * @access private
	 */
	private $icon;
	
	/**
	 * @var $icon_small
	 * @desc Admin page icon
	 * @access private
	 */
	private $icon_small;

	/**
	 * Class constructor
	 * 
	 * Sets definitions
	 * Adds methods to appropriate hooks
	 * 
	 * @author Owen Davey <owen@metronet.no>
	 * @since Maps 1.0
	 * @access public
	 * @param array $args    If not set, then uses $defaults instead
	 */
	public function __construct( $args = array() ) {

		// Parse arguments
		$defaults = array(
			'heading'     => __( 'Google Maps', 'Google Maps' ), // Default text for heading
			'initial'     => '',                         // Initial text displayed before sorting code
			'final'       => '',                         // Initial text displayed before sorting code
		);
		extract( wp_parse_args( $args, $defaults ) );

		// Set variables
		$this->heading     = $heading;
		$this->initial     = $initial;
		$this->final       = $final;
		$this->menu_label  = $menu_label;
		$this->icon        = $icon;
		$this->icon_small        = $icon_small;
		
		// Add actions
		add_action( 'wp_ajax_save_map',   array( $this, 'ajax_save_map'  ) );
		add_action( 'wp_ajax_get_map',   array( $this, 'ajax_get_map'  ) );
		add_action( 'wp_ajax_delete_map',   array( $this, 'ajax_delete_map'  ) );
		add_action( 'admin_menu',          array( $this, 'enable_maps' ), 10, 'page' );
	}


	/**
	 * 
	 *
	 * @author Owen Davey <owen@metronet.no> 
	 * @since Maps 1.0
	 * @access public
	 */
	public function ajax_save_map() {
		// Verify nonce value, for security purposes
		if ( !wp_verify_nonce( $_POST['nonce'], 'sortnonce' ) ) die( '' );

		$mapID = $_POST["postID"];
		$theMap = $_POST["mapValues"];
		// If this is a new map
		if($mapID == 0) {
			$map = array(
				"post_type" => "map",
				"post_status" => "publish",
				"post_title" => $theMap["mapTitle"]
			);

			$mapID = wp_insert_post($map);
		}
		else {
			wp_update_post(array("ID" => $mapID, "post_title" => $theMap["mapTitle"]));
		}
		update_post_meta($mapID, "map_width", $theMap["width"]);
		update_post_meta($mapID, "map_height", $theMap["height"]);
		
		if($theMap["markers"]) {
			foreach($theMap["markers"] as $key=>$marker) {

				$markerID = $marker["markerID"];
				if(empty($markerID)) {
					$saveMarker = array(
						"post_type" => "map-marker",
						"post_status" => "publish",
						"post_title" => $marker["markerTitle"],
						"post_content" => $marker["markerContent"],
						"post_parent" => $mapID,
						"menu_order" => $marker["menuOrder"]
					);

					$markerID = wp_insert_post($saveMarker);
				}
				else if(!empty($marker["delete"])) {
					wp_delete_post($markerID);
				}
				else {
					wp_update_post(array(
						"ID" => $markerID, 
						"post_title" => $marker["markerTitle"], 
						"post_content" => $marker["markerContent"],
						"menu_order" => $marker["menuOrder"])
					);
				}

				if($markerID != -1) {
					update_post_meta($markerID, "marker_latitude", $marker["latitude"]);
					update_post_meta($markerID, "marker_longitude", $marker["longitude"]);
					update_post_meta($markerID, "marker_icon", $marker["uploadedImage"]);
				}
			}
		}
		
		$theHTML = "";
		
		ob_start();
		
		$this->show_maps();
		
		$theHTML = ob_get_clean();
		die( json_encode( array( "theHTML" => $theHTML ) ) );
	} //end ajax_save_map
	
	/**
	 * 
	 *
	 * @author Owen Davey <owen@metronet.no> 
	 * @since Maps 1.0
	 * @access public
	 */
	public function ajax_get_map() {
		// Verify nonce value, for security purposes
		if ( !wp_verify_nonce( $_GET['nonce'], 'sortnonce' ) ) die( '' );
		
		$mapID = $_GET["mapID"];
		
		$map = get_post($mapID);
		$width = get_post_meta($mapID, "map_width", true);
		$height = get_post_meta($mapID, "map_height", true);
		
		$markersResponse = array();
		
		$markersWP = get_posts(array(
				"post_type" => "map-marker", 
				"post_parent" => $mapID,
				"posts_per_page" => -1,
				"orderby" => "menu_order",
				"order" => "ASC"
				));

		foreach($markersWP as $aMarker) {
			$latitude = get_post_meta($aMarker->ID, "marker_latitude", true);
			$longitude = get_post_meta($aMarker->ID, "marker_longitude", true);
			$icon = get_post_meta($aMarker->ID, "marker_icon", true);
			
			$markersResponse[] = array(
				"markerID" => $aMarker->ID,
				"markerTitle" => $aMarker->post_title,
				"markerContent" => $aMarker->post_content,
				"latitude" => $latitude,
				"longitude" => $longitude,
				"uploadedImage" => $icon
			);
		}
		
		die( json_encode( array( "mapTitle" => $map->post_title, "width" => $width, "height" => $height, "markers" => $markersResponse ) ) );
	} //end ajax_get_map
	
	/**
	 * 
	 *
	 * @author Owen Davey <owen@metronet.no> 
	 * @since Maps 1.0
	 * @access public
	 */
	public function ajax_delete_map() {
		// Verify nonce value, for security purposes
		if ( !wp_verify_nonce( $_POST['nonce'], 'sortnonce' ) ) die( '' );
		
		$mapID = $_POST["mapID"];
		
		$post = wp_delete_post($mapID);
		$deleted = false;
		if($post !== false) {
			$deleted = true;
		}
		
		die( json_encode( array( "success" => $deleted ) ) );
	} //end ajax_delete_map

	/**
	 * Print styles to admin page
	 *
	 * @author Owen Davey <owen@metronet.no>
	 * @since Maps 1.0
	 * @access public
	 * @global string $pagenow Used internally by WordPress to designate what the current page is in the admin panel
	 */
	public function print_styles() {
		global $pagenow;

		$pages = array( 'admin.php' );

		if ( in_array( $pagenow, $pages ) ) {
			wp_enqueue_style( 'maps_style', MAPS_URL . '/admin.css' );
			wp_enqueue_style('thickbox');
		}

	}

	/**
	 * Print scripts to admin page
	 *
	 * @author Owen Davey <owen@metronet.no>
	 * @since Maps 1.0
	 * @access public
	 * @global string $pagenow Used internally by WordPress to designate what the current page is in the admin panel
	 */
	public function print_scripts() {
		global $pagenow, $hook_suffix;
		$pages = array( 'admin.php' );

		if ( in_array( $pagenow, $pages ) ) {
			wp_enqueue_script('google-api', "http://maps.google.com/maps/api/js?sensor=true&language=en");
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_script( 'maps', MAPS_URL . '/scripts/maps.js', array("jquery", "google-api", "media-upload", "thickbox"));
			wp_localize_script( 'maps', 'maps_object', array(
				'sortnonce' =>  wp_create_nonce( 'sortnonce' ),
			) );
		}
	}

	/**
	 * Add submenu
	 *
	 * @author Owen Davey <owen@metronet.no>
	 * @since Maps 1.0
	 * @access public
	 */
	public function enable_maps() {
		$hook = add_menu_page(
			$this->heading,                     // Page title (unneeded since specified directly)
			$this->menu_label,                  // Menu title
			'publish_posts',                       // Capability
			'google-maps',                    // Menu slug
			array( $this, 'display_maps_page' ),        // Callback function
			$this->icon_small
		);
		
		add_action( 'admin_print_styles-' . $hook,  array( $this, 'print_styles'     ) );
		add_action( 'admin_print_scripts-' . $hook, array( $this, 'print_scripts'    ) );
	}
	
	/**
	 * HTML output
	 *
	 * @author Owen Davey <owen@metronet.no>
	 * @since Maps 1.0
	 * @access public
	 * @global string $post_type
	 */
	public function display_maps_page() {
		?>
		<style type="text/css">
			#icon-maps {
				background:url(<?php echo $this->icon; ?>) no-repeat;
			}
			</style>
			<div class="maps-page-wrap">
				<?php screen_icon( 'maps' ); ?>
				<h2>
					<?php echo $this->heading; ?>
				</h2>
				<div id="edit-maps-wrapper">
					<div id="current-maps" class="postbox">
						<h3><span>Current Maps</span></h3>
						<div class="inner-content">
						<?php
						$this->show_maps();
						?>
						</div>
					</div>
					<button class='map-button' id="add-new-map">Add New Map</button>
					<div id="new-map" class="postbox">
						<h3><span>Map</span></h3>
						<div class="inner-content">
							<div class="row">
								<label for="title">Title <span class="required">*</span></label>
								<input type="text" name="mapTitle" id="mapTitle" class="is-required" />
								<div class="clr"></div>
							</div>
							<div class="row alternate">
								<label for="width">Width <span class="desc">(e.g. 520px, 100%)</span></label>
								<input type="text" name="width" id="width" />
								<div class="clr"></div>
							</div>
							<div class="row">
								<label for="height">Height <span class="desc">(e.g. 250px)</span></label>
								<input type="text" name="height" id="height" />
								<div class="clr"></div>
							</div>
							<div class="row alternate">
								<label>Markers</label>
								<div id="markers-wrap">
									<button class='map-button' id="add-new-marker">Add New Marker</button>
									<div id="the-markers">
									</div>
								</div>
								<div class="clr"></div>
							</div>
							<div class="row">
								<button class='map-button' id="save-map">Save Map</button>
							</div>
						</div>
					</div>
				</div>
				<div id="edit-marker-wrapper">
					<div id="new-marker" class="postbox">
						<h3><span>Marker</span></h3>
						<div class="inner-content">
							<div id="find-coordinates-help">
								<div class="row">
									Need help finding the latitude and longitude? <a href="javascript: void(0)" id="coordinates-help-link">Click here.</a>
								</div>
							</div>
							<div id="find-coordinates-wrapper">
								<div class="row">
									<label for="street-address">Street Address</label>
									<input type="text" name="street-address" id="street-address" value="" />
									<div class="clr"></div>
								</div>
								<div class="row alternate">
									<label for="city">City</label>
									<input type="text" name="city" id="city" />
									<div class="clr"></div>
								</div>
								<div class="row">
									<label for="state">State</label>
									<input type="text" name="state" id="state" />
									<div class="clr"></div>
								</div>
								<div class="row alternate">
									<label for="postcode">Postcode</label>
									<input type="text" name="postcode" id="postcode" />
									<div class="clr"></div>
								</div>
								<div class="row">
									<label for="country">Country</label>
									<input type="text" name="country" id="country" />
									<div class="clr"></div>
								</div>
								<div class="row alternate">
									<label>Get coordinates</label>
									<button class='map-button' id="populate-coordinates">Go!</button>
									<div class='loading'></div>
									<div class="clr"></div>
								</div>
							</div>
							<div class="row">
								<label for="title">Title <span class="required">*</span></label>
								<input class="is-required" type="text" name="markerTitle" id="markerTitle" />
								<div class="clr"></div>
							</div>
							<div class="row alternate">
								<label for="latitude">Latitude <span class="required">*</span></label>
								<input class="is-required is-float" type="text" name="latitude" id="latitude" />
								<div class="clr"></div>
							</div>
							<div class="row">
								<label for="longitude">Longitude <span class="required">*</span></label>
								<input class="is-required is-float" type="text" name="longitude" id="longitude" />
								<div class="clr"></div>
							</div>
							<div class="row alternate">
								<label for="marker-image">Marker Image</label>
								<button class="thickbox map-button" id="add-image">Upload Marker Image</button>
								<div id="uploaded-image-wrapper"></div>
								<div class="clr"></div>
							</div>
							<div class="row">
								<label for="marker-content">Marker Content</label>
								<?php
								wp_editor("", "marker_content", array("teeny" => true, "textarea_name" => "marker_content", "textarea_rows" => 25, 'tinymce' => array("height" => 300)));
								?>
							</div>
							<div class="row">
								<button class='map-button' id="save-marker">Save Marker</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
	} //end display_maps_page

	function show_maps() {
		$currentMaps = get_posts(array("post_type" => "map", "posts_per_page" => -1));
						
		if($currentMaps) {
			foreach($currentMaps as $key=>$map) {
				$class = "";
				if(($key % 2) == 1) {
					$class = "alternate";
				}

				$title = $map->post_title;
				$id = $map->ID;
			?>
			<div class="row <?php echo $class ?>"><span><?php echo $title ?></span><div class='delete-map' rel="<?php echo $id ?>">X</div><button class='map-button get-shortcode' rel="<?php echo $id ?>">Get Shortcode</button> <button class='map-button edit-map' rel="<?php echo $id ?>">Edit Map</button><div class="clr"></div></div>
			<?php
			}
		}
		else {
			?>
			<div class="row">No current maps</div>
			<?php
		}
	}
}
