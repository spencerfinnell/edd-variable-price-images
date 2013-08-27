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
				state     : 'gallery-edit',
				frame     : 'post',
				title     : wp.media.eddVPI.button.data( 'price' ),
				button    :  {
					text : wp.media.eddVPI.button.data( 'text' )
				},
				library   : {
                    type : 'image'
                },
				multiple  : true,
				selection : wp.media.eddVPI.selection()
			});

			return this._frame;
		},

		selection : function() {
			var shortcode = wp.shortcode.next( 'gallery', wp.media.eddVPI.field.val() ),
				defaultPostId = wp.media.gallery.defaults.id,
				attachments, selection;
		 
			// Bail if we didn't match the shortcode or all of the content.
			if ( ! shortcode )
				return;
		 
			// Ignore the rest of the match object.
			shortcode = shortcode.shortcode;
		 
			if ( _.isUndefined( shortcode.get('id') ) && ! _.isUndefined( defaultPostId ) )
				shortcode.set( 'id', defaultPostId );
		 
			attachments = wp.media.gallery.attachments( shortcode );
			selection = new wp.media.model.Selection( attachments.models, {
				props:    attachments.props.toJSON(),
				multiple: true
			});
			 
			selection.gallery = attachments.gallery;
		 
			// Fetch the query's attachments, and then break ties from the
			// query to allow for sorting.
			selection.more().done( function() {
				// Break ties with the query.
				selection.props.set({ query: false });
				selection.unmirror();
				selection.props.unset('orderby');
			});
		 
			return selection;
		},
	 
		init : function() {
			$( '#edd_price_fields' ).on( 'click', '.edd_vpi_assign', function(e) {
				e.preventDefault();

				wp.media.eddVPI.button = $(this);
				wp.media.eddVPI.key    = wp.media.eddVPI.button.parents( 'tr' ).index();
				wp.media.eddVPI.field  = $( 'input[name="edd_variable_prices[' + wp.media.eddVPI.key + '][image]"]' );

				wp.media.eddVPI.frame().open();

				wp.media.eddVPI.frame().on( 'update', function(selection) {
					wp.media.eddVPI.field.val( wp.media.gallery.shortcode( selection ).string() );
				});
			});
		}
	};

	$( wp.media.eddVPI.init );
});