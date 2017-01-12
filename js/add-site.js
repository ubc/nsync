
/**
 * Initiate search functionality
 */

var Nsync_Add_Site = {
	
	onReady : function() {
		
		// trigger ajax search call
		jQuery( "#nsync-add-site" ).click( function() {
		
			var search = jQuery( "#nsync-site-search" ).val();
			var html = "";
			
			var data = {
					'action': 'nsync_lookup_site',
					'term': search 
				};
				
			if (search != "") {
				
				jQuery.ajax({ 
					     type : "post",
					     dataType : "json",
					     url : "admin-ajax.php",
					     data : data,
					     success: function(response) {
					    	if (response != null) {
							     jQuery.each(response, function( i, site ) {
							    	 html += '<div class="search-result-item"><label><input name="nsync_options[active][]" type="checkbox" checked="checked" value="' + site.value + '" /> ' + site.label + '</label></div>';
								     jQuery('#search-site-results').html(html).fadeIn();
								  });
							     
							     jQuery(".check").fadeIn();
					    	} else {
					    		 jQuery(".check").fadeOut();
								 jQuery('#search-site-results').html("<p> No Results </p>").fadeIn();
					    	}
						 },
						 error: function(){
	
							 jQuery(".check").fadeOut();
							 jQuery('#search-site-results').html("<p> Error Occured </p>").fadeIn();
					     } 
					});  
			}
						
		});
		
	   // select all sites or unselect
	   jQuery('.check').click( function() {
	
		   if (jQuery(this).text() == "uncheck all") {
			   
			   jQuery('.search-result-item input:checkbox').removeAttr('checked');
			   jQuery(this).text('check all');  
		   
		   } else {
			   jQuery('.search-result-item input:checkbox').attr('checked','checked');
			   jQuery(this).text('uncheck all');
			   
		   }
			
		});

	}
}

jQuery( document ).ready( Nsync_Add_Site.onReady );