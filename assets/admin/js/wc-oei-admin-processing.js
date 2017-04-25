
	function WC_OEI_Import_Processing( import_length ) {
		var log_html = jQuery('#log-viewer');
		for( var i = 0; i < import_length; i++ ) {
			jQuery.ajax( ajaxurl, {
				'type' : 'POST',
				'async' : true,
				'cache' : false,
				'data' : {
					'action'  : 'wc_oei_import_processing',
					'current' : i,
					'file_id' : jQuery('input[name=wc-oei_file_id]').val()
				},
				'complete' : function( response ) {
					jQuery('.wc-oei-importer .media-progress-bar > div').css( 'width', ( i / import_length ) * 100 + '%' );
					
					log_html.append( '<p>' + response.responseText + '</p>' );
				}
			});
		}
	}
