<?php
/**
 * Admin container.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds admin sub-handlers.
 */
class THENFOPA_Admin {

	/**
	 * Admin action handler.
	 *
	 * @var THENFOPA_Admin_Action|null
	 */
	public $action = null;

	/**
	 * Admin filter handler.
	 *
	 * @var THENFOPA_Admin_Filter|null
	 */
	public $filter = null;
}

