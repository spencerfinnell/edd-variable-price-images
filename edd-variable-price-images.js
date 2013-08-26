/**
 * Media Window
 */

jQuery(document).ready(function ($) {
	var button = null,
	    key    = null;

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
				multiple  : false
			}).on( 'select', function() {
				var attachment = wp.media.eddVPI._frame.state().get('selection').first().toJSON(),
				    field      = $( 'input[name="edd_variable_prices[' + wp.media.eddVPI.key + '][image]"]' );

				console.log( attachment );

				field.val( attachment.id );
				field.prev( 'img' ).attr( 'src', attachment.url );
			});

			return this._frame;
		},
	 
		init : function() {
			$( '#edd_price_fields' ).on( 'click', '.edd_vpi_assign', function(e) {
				e.preventDefault();

				wp.media.eddVPI.button = $(this);
				wp.media.eddVPI.key    = wp.media.eddVPI.button.parents( 'tr' ).index();

				wp.media.eddVPI.frame().open();
			});
		}
	};

	$( wp.media.eddVPI.init );
});