

var initUI = function() {

	function checkUpload(object) {
		
		(jQuery(object)[0].checked) ? jQuery(".upload-dir").fadeIn() : jQuery(".upload-dir").fadeOut();
	}
	
	var upload_element = "input[name='nsync_options[duplicate_files]']";
	checkUpload(upload_element);

	jQuery(".display-name").click( function() {
		jQuery(this).nextAll(".user-blogs-list").fadeToggle();
	});

	jQuery("#nsync-nav div").click( function() {
		var id = jQuery(this).attr("id");
		jQuery(".general.nsync, .add.nsync, .current.nsync").css({ "display" : "none" });
		jQuery("#general, #add, #current").css({ "background-color" : "#333" });
		jQuery("#" + id).css({"background-color" : "#777"});
		jQuery("." + id + ".nsync").fadeIn();
	});

	jQuery(upload_element).change(function() {
		checkUpload(this);
 	});

}

jQuery( document ).ready( initUI );