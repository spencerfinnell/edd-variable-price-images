/**
 * Media Window
 */

jQuery(document).ready(function ($) {
	var button = null,
	    key    = null,
	    field  = null;

	wp.media.eddVPI = {
		frame : function() {
			if ( this._frame )
				return this._frame;

			this._frame = wp.media({
				id        : 'eddVPI',                
				title     : wp.media.eddVPI.button.data( 'price' ),
				button    :  {
					text : wp.media.eddVPI.button.data( 'text' )
				},
				library   : {
                    type : 'image'
                },
				multiple  : false
			}).on( 'select', function() {
				var attachment = wp.media.eddVPI._frame.state().get('selection').first().toJSON();

				wp.media.eddVPI.field.val( attachment.id );
			}).on( 'open', function() {
				var selection = wp.media.eddVPI._frame.state().get('selection');

				ids = [ wp.media.eddVPI.field.val() ];
				
				ids.forEach(function(id) {
					attachment = wp.media.attachment(id);
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				});
	        });

			return this._frame;
		},
	 
		init : function() {
			$( '#edd_price_fields' ).on( 'click', '.edd_vpi_assign', function(e) {
				e.preventDefault();

				wp.media.eddVPI.button = $(this);
				wp.media.eddVPI.key    = wp.media.eddVPI.button.parents( 'tr' ).index();
				wp.media.eddVPI.field  = $( 'input[name="edd_variable_prices[' + wp.media.eddVPI.key + '][image]"]' );

				wp.media.eddVPI.frame().open();
			});
		}
	};

	$( wp.media.eddVPI.init );
});