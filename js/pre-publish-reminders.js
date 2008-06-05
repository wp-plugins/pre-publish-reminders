jQuery(document).ready(
	function() {
		jQuery( '.reminder_cb' ).click(
			function() {
				if( this.checked ) {
					jQuery( this ).parent().fadeTo( 1000, .3 );
				} else {
					jQuery( this ).parent().fadeTo( 1000, 1 );
				}
			}
		);
	}
);