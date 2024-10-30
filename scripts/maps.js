
jQuery(document).ready(function($) {	
	//Variable maps_object.sortnonce is declared globally	
	$("#add-new-map").click(function() {
		$(this).addClass("disabled");	
		$("#new-map input").val("");
		$("#new-map .markers-row").remove();
		$("#new-map").fadeIn("slow");
	});
	
	$("#add-new-marker").click(function() {
		$(this).addClass("disabled");
		$("#save-map").addClass("disabled");
		
		$("#edit-marker-wrapper input").each(function() {
			$(this).val("");
		});
		$("#uploaded-image-wrapper").html("");
		var marker_content = tinyMCE.get('marker_content').setContent("");
		
		$("#edit-marker-wrapper").fadeIn("slow");
	});
	
	$("#coordinates-help-link").click(function() {
		$("#find-coordinates-help").hide();
		$("#find-coordinates-wrapper").fadeIn("slow");
	});
	
	$("#populate-coordinates").click(function() {
		var streetAddress = $("#new-marker #street-address").val();
		var city = $("#new-marker #city").val();
		var state = $("#new-marker #state").val();
		var postcode = $("#new-marker #postcode").val();
		var country = $("#new-marker #country").val();
		$("#new-marker .loading").show();
		var geocoder = new google.maps.Geocoder();
		var addressGeo = streetAddress + " " + city + " " + state + " " + postcode + " " + country;
		
		var latitude;
		var longitude;
		
		geocoder.geocode( { 'address': addressGeo}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				/*console.log(results);
				console.log(results[0].geometry.location.lat() );
				console.log(results[0].geometry.location.lng() );*/
				latitude = results[0].geometry.location.lat();
				longitude = results[0].geometry.location.lng();
				
				$("#new-marker #latitude").val(latitude);
				$("#new-marker #longitude").val(longitude);
			} 
			else if (status == google.maps.GeocoderStatus.ZERO_RESULTS){
				alert("Latitude and longitude for '" + addressGeo + "' couldn't be found by Google, add manually");
			}
			else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT){
				alert("Too many requests at once, please refresh the page to complete geocoding");
			}
			$("#new-marker .loading").hide();
		});
	})
		
	$("#add-image, #uploaded-image-wrapper").click(function() {
		var uploadLink = $(this);
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
 
		window.original_send_to_editor = window.send_to_editor;
 
		// override function so you can have multiple uploaders pre page
		window.send_to_editor = function(html) {
			imgurl = $('img',html).attr('src');
			$("#uploaded-image").remove();
			
			$("#uploaded-image-wrapper").html("<img id='uploaded-image' src='"+imgurl+"' />");
			$("#add-image").html("Change Image");
			tb_remove();
			// Set normal uploader for editor
			window.send_to_editor = window.original_send_to_editor;
		};
		
		return false;
	});
	
	var isSaving = false;
	$("#save-map").click(function() {
		if(!isSaving) {
			$("#add-new-marker").removeClass("disabled");
			$("#edit-marker-wrapper").fadeOut("slow");
			isSaving = true;
			var isValid = true;
			$("#new-map .is-required").each(function() {
				if($(this).val().length == 0) {
					$(this).prev().addClass("error");
					isValid = false;
				}
				else {
					$(this).prev().removeClass("error");
				}
			});

			if(isValid) {
				var mapObj = new Object();

				$("#new-map input").each(function() {
					mapObj[$(this).attr("id")] = $(this).val();
				});
				mapObj["markers"] = new Array();
				var counter = 0;
				$("#the-markers .markers-row").each(function() {
					
					var individualMarker = new Object();
					$.each($(this).data(), function(key,valueObj){
						individualMarker[key] = valueObj;
						
					});
					mapObj["markers"].push(individualMarker);
					individualMarker["menuOrder"] = counter;
					counter++;
				});
				
				var thePostID = 0;
				
				if($("#new-map").hasClass("editing")) {
					thePostID = $("#new-map").attr("rel");
				}

				$.post( ajaxurl, { action: 'save_map', nonce: maps_object.sortnonce, mapValues: mapObj, postID: thePostID }, 
						function( response ) {
							if ( typeof response.error != "undefined" ) {
							}
							else {
								$("#add-new-map").removeClass("disabled");		
								$("#new-map").fadeOut("slow");
								$("#current-maps .inner-content").html(response.theHTML);
								$("#new-map").removeClass("editing");
								$("#new-map").attr("rel", "");
							}				
						}, 'json' );
			}
			isSaving = false;
		}
	});
	
	var isSavingMarker = false;
	$("#save-marker").click(function() {
		if(!isSavingMarker) {
			isSavingMarker = true;
			var markerObj = new Object();
			var isValid = true;
			$("#new-marker .is-required").each(function() {
				if($(this).val().length == 0) {
					$(this).prev().addClass("error");
					isValid = false;
				}
				else if($(this).hasClass("is-float")) {
					if(isNaN($(this).val())) {
						$(this).prev().addClass("error");
						isValid = false;
					}
					else {
						$(this).prev().removeClass("error");
					}
				}
				else {
					$(this).prev().removeClass("error");
				}
				markerObj[$(this).attr("id")] = $(this).val();
			});
			var marker_content = tinyMCE.get('marker_content').getContent();

			if(marker_content.length != 0) {
				markerObj.markerContent = marker_content;
			}

			if(isValid) {
				if($("#uploaded-image").length > 0) {
					markerObj.uploadedImage = $("#uploaded-image").attr("src");
				}
				displayMarkerRow(markerObj);

				$("#add-new-marker").removeClass("disabled");
				$("#edit-marker-wrapper").fadeOut("slow", function() {
					$("#find-coordinates-wrapper").hide();
					$("#find-coordinates-help").show();
					$("#save-map").removeClass("disabled");
				});
			}
			isSavingMarker = false;
		}
	});
	
	$(document).on("click", ".edit-marker", function() {
		$("#save-map").addClass("disabled");
		$("#edit-marker-wrapper input").each(function() {
			$(this).val("");
		});
		$("#uploaded-image-wrapper").html("");
		var marker_content = tinyMCE.get('marker_content').setContent("");
		var dataHolder = $(this).parent();
		
		$("#add-new-marker").addClass("disabled");
		
		$.each(dataHolder.data(), function(key,valueObj){
			$("#edit-marker-wrapper #" + key).val(valueObj);
		});
		if(dataHolder.data().markerContent != undefined) {
			tinyMCE.get('marker_content').setContent(dataHolder.data().markerContent);
		}
		if(dataHolder.data().uploadedImage != undefined) {
			$("#uploaded-image-wrapper").html("<img id='uploaded-image' src='"+dataHolder.data().uploadedImage+"' />");
			$("#add-image").html("Change Image");
		}
		else {
			$("#add-image").html("Upload Marker Image");
		}
		
		$("#edit-marker-wrapper").addClass("editing");
		$("#edit-marker-wrapper").attr("rel", dataHolder.index());
		$("#edit-marker-wrapper").fadeIn("slow");
	});
	
	$(document).on("click", ".delete-marker", function() {
		$(this).parent().fadeOut("slow", function() {
			$(this).data("delete", true);
		});
	});
	
	$(document).on("click", ".delete-map", function() {
		if(confirm("Are you sure you want to delete this map?")) {
			var theMap = $(this).parent();
			$.post( ajaxurl, { action: 'delete_map', nonce: maps_object.sortnonce, mapID: $(this).attr("rel") }, 
				function( response ) {
					if ( typeof response.error != "undefined" ) {

					}
					else {
						theMap.fadeOut("slow");
					}				
				}, 'json' );
		}
	});
	
	$(document).on("click", ".edit-map", function() {
		$("#add-new-marker").removeClass("disabled");
		$("#edit-marker-wrapper").fadeOut("slow");
		$("#new-map input").val("");
		$("#new-map .markers-row").remove();
		$("#add-new-map").addClass("disabled");
		
		$("#new-map").addClass("editing");
		$("#new-map").attr("rel", $(this).attr("rel"));
		
		
		$.get( ajaxurl, { action: 'get_map', nonce: maps_object.sortnonce, mapID: $(this).attr("rel") }, 
						function( response ) {
							if ( typeof response.error != "undefined" ) {
								
							}
							else {
								$.each(response, function(key, value) {
									$("#new-map input#" + key).val(value);
								});
								
								$.each(response.markers, function(key, marker) {
									$("#markers-wrap #the-markers").append("<div class='markers-row'><span>" + marker.markerTitle + "</span><div class='delete-marker'>X</div><button class='map-button edit-marker'>Edit Marker</button><div class='clr'></div></div>");
									$.each(marker, function(key,valueObj){
										$("#markers-wrap #the-markers .markers-row:last-child").data(key, valueObj);
									});
								});
								
								$("#new-map").fadeIn("slow");
							}				
						}, 'json' );
	});
	
	$(document).on("click", ".get-shortcode", function() {
		//[map id=1]
		$(this).replaceWith("<div class='the-shortcode'>[map id=" + $(this).attr("rel") + "]</div>");
	});
	
	function displayMarkerRow(markerObject) {
		if($("#edit-marker-wrapper").hasClass("editing")) {
			var theRow = $("#markers-wrap #the-markers .markers-row").eq($("#edit-marker-wrapper").attr("rel"));
			
			$.each(markerObject, function(key,valueObj){
				theRow.data(key, valueObj);
			});
			
			theRow.find("span").html(markerObject.markerTitle);
		}
		else {
			$("#markers-wrap #the-markers").prepend("<div class='markers-row' style='display: none'><span>" + markerObject.markerTitle + "</span><div class='delete-marker'>X</div><button class='map-button edit-marker'>Edit Marker</button><div class='clr'></div></div>");
			$("#markers-wrap #the-markers .markers-row:first-child").fadeIn("slow");
			$.each(markerObject, function(key,valueObj){
				$("#markers-wrap #the-markers .markers-row:first-child").data(key, valueObj);
			});
		}
		$("#edit-marker-wrapper").attr("class", "");
	}
	
});

function isFloat (value) {
	if (isNaN(value) || value.toString().indexOf(".") < 0) {
		return false;
	} else {
		if (parseFloat(value)) {
			return true;
		} else {
			return false;
		}
	}
}