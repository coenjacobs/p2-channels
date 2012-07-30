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

		var args = {action: 'p2_add_channels', _ajax_post:nonce, channels: checkboxVals };
		var errorMessage = '';

		$.ajax({
			type: "POST",
			url: ajaxUrl,
			data: args,
			success: function(result) {
				if ("0" == result)
					errorMessage = p2txt.not_posted_error;
			},
			timeout: 60000
		});
	}
});