<?php
/**
 * Front-end actions.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end action handlers.
 */
class THENFOPA_Front_Action {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register front-end assets.
	 */
	public function enqueue_scripts() {
		if ( is_admin() ) {
			return;
		}

		wp_register_script(
			THENFOPA_FRONT_SCRIPT_HANDLE,
			THENFOPA_URL . 'assets/js/front.js',
			array( 'jquery' ),
			THENFOPA_VERSION,
			true
		);

		wp_register_style(
			'thenfopa-front-style',
			THENFOPA_URL . 'assets/css/front.css',
			array(),
			THENFOPA_VERSION
		);

		wp_enqueue_style( 'thenfopa-front-style' );
	}
}

