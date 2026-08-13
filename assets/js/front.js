/* global jQuery */
( function ( $ ) {
	'use strict';

	/**
	 * WPForms natively handles the redirect returned in its AJAX confirmation,
	 * so the Stripe Checkout redirect works out of the box. This file is reserved
	 * for future front-end enhancements (e.g. on-site Stripe Elements).
	 */
	$( document ).on( 'wpformsAjaxSubmitSuccess', function () {} );
} )( jQuery );
