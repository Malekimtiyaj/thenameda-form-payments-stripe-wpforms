<?php
/**
 * Front-end container.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds front-end sub-handlers.
 */
class THENFOPA_Front {

	/**
	 * Front action handler.
	 *
	 * @var THENFOPA_Front_Action|null
	 */
	public $action = null;

	/**
	 * Front filter handler.
	 *
	 * @var THENFOPA_Front_Filter|null
	 */
	public $filter = null;
}

