
var Nsync_Add_Site = {
	
	onReady : function() {
		
		var cache = {},
			lastXhr;
		jQuery( "#nsync-add-site" ).autocomplete({
			minLength: 2,
			source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
					response( cache[ term ] );
					return;
				}
				
				lastXhr = jQuery.getJSON( ajaxurl+"?action=nsync_lookup_site", request, function( data, status, xhr ) {
					
					cache[ term ] = data;
					if ( xhr === lastXhr ) {
						response( data );
					}
				});
			},
			select: function(event, ui) { 
				
				var html = '<label><input name="nsync_options[active][]" type="checkbox" checked="checked" value="'+ui.item.value+'" /> '+ui.item.label+'</label><br />';
				
				jQuery('#select-site').append(html);
				jQuery( "#nsync-add-site" ).val( '' );
			 }
		});
	}


}

jQuery( document ).ready( Nsync_Add_Site.onReady );