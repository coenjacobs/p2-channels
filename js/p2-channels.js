jQuery( document ).ready( function( $ ) {
	// Catch new posts submit
	$( '#new_post' ).submit( function(trigger) {
		p2_add_channels(trigger);
	});

	function p2_add_channels(trigger) {
		var checkboxVals = [];
		$( '.p2_channels_term:checked' ).each( function() {
			checkboxVals.push( $(this).val() );
		} );

		if ( $('#tags').val() == 'Tag it' ) {
			$('#tags').val( checkboxVals );
		} else {
			$('#tags').val( $('#tags').val() + ',' + checkboxVals );
		}
	}
});