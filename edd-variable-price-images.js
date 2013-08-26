/**
 * Media Window
 */

jQuery(document).ready(function ($) {
	var button = null;

	wp.media.eddVariablePriceImage = {
		frame : function() {
			if ( this._frame )
				return this._frame;
	 
			this._frame = wp.media({
				id        : 'eddVariablePriceImage',                
				frame     : 'post',
				state     : 'featured-image',
				editing   : true,
				multiple  : false
			}).on( 'menu:render:default', function(view) {
				var views = {};

				view.unset('library-separator');
				view.unset('gallery');
				view.unset('featured-image');
				view.unset('embed');

				view.set(views);
			});

			return this._frame;
		},
	 
		init : function() {
			$( '.edd_variable_prices_wrapper' ).on( 'click', '.edd_vpi_assign', function(e) {
				e.preventDefault();

				wp.media.eddVariablePriceImage.button = $(this);

				wp.media.eddVariablePriceImage.frame().open();
			});

			wp.media.eddVariablePriceImage.frame().on( 'select', function( selection ) {
				attachment = wp.media.eddVariablePriceImage._frame.state().get( 'selection' ).first().toJSON();

				console.log( wp.media.eddVariablePriceImage.button );
				console.log( attachment );
			} );
		}
	};

	$( wp.media.eddVariablePriceImage.init );
});