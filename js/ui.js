
/**
 * Initiate User Interface Javascript 
 */
var initUI = function() {
	
	// user list toggle
	jQuery(".display-name").click( function() {
		jQuery(this).nextAll(".user-blogs-list").fadeToggle();
	});
	
	// navigation javascript
	jQuery("#nsync-nav div").click( function() {
		var id = jQuery(this).attr("id");
		jQuery(".general.nsync, .add.nsync, .current.nsync").css({ "display" : "none" });
		jQuery("#general, #add, #current").css({ "background-color" : "#333" });
		jQuery("#" + id).css({"background-color" : "#777"});
		jQuery("." + id + ".nsync").fadeIn();
	});
}

jQuery( document ).ready( initUI );