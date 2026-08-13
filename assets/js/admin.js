/* global jQuery, thenfopa_admin */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.thenfopa-refund-payment', function ( e ) {
		e.preventDefault();

		if ( typeof thenfopa_admin === 'undefined' ) {
			return;
		}

		var $button = $( this );
		var postId = $button.data( 'post-id' );
		var $status = $button.siblings( '.thenfopa-refund-status' );

		if ( ! window.confirm( thenfopa_admin.confirm ) ) {
			return;
		}

		$button.prop( 'disabled', true );
		$status.removeClass( 'thenfopa-error thenfopa-success' ).text( '…' );

		$.ajax( {
			url: thenfopa_admin.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'thenfopa_refund_payment',
				nonce: thenfopa_admin.nonce,
				post_id: postId
			}
		} )
			.done( function ( response ) {
				if ( response && response.success ) {
					$status.addClass( 'thenfopa-success' ).text( response.data.message );
					setTimeout( function () {
						window.location.reload();
					}, 1500 );
				} else {
					var message = response && response.data && response.data.message
						? response.data.message
						: 'Refund failed.';
					$status.addClass( 'thenfopa-error' ).text( message );
					$button.prop( 'disabled', false );
				}
			} )
			.fail( function () {
				$status.addClass( 'thenfopa-error' ).text( 'Request failed. Please try again.' );
				$button.prop( 'disabled', false );
			} );
	} );
} )( jQuery );
