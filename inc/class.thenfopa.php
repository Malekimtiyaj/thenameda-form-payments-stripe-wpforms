<?php
/**
 * Main plugin class.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin bootstrap and shared object container.
 */
class THENFOPA {

	/**
	 * Singleton instance.
	 *
	 * @var THENFOPA|null
	 */
	private static $_instance = null;

	/**
	 * Admin handler.
	 *
	 * @var THENFOPA_Admin|null
	 */
	public $admin = null;

	/**
	 * Front handler.
	 *
	 * @var THENFOPA_Front|null
	 */
	public $front = null;

	/**
	 * Library / payment handler.
	 *
	 * @var THENFOPA_Lib|null
	 */
	public $lib = null;

	/**
	 * Get instance.
	 *
	 * @return THENFOPA
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'action__init' ) );
		add_action( 'rest_api_init', array( $this, 'action__rest_api_init' ) );

		// Allow front-end submissions to create transaction records for the protected CPT.
		add_filter( 'map_meta_cap', array( $this, 'filter__map_meta_cap' ), 10, 4 );
	}

	/**
	 * Register the transactions post type and handle Stripe return/cancel listeners.
	 */
	public function action__init() {
		$this->register_post_type();

		if ( isset( $_GET[ THENFOPA_Helpers::LISTENER_QUERY_ARG ] ) && $this->lib instanceof THENFOPA_Lib ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->lib->handle_listener( sanitize_key( wp_unslash( $_GET[ THENFOPA_Helpers::LISTENER_QUERY_ARG ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	/**
	 * Register REST webhook route.
	 *
	 * Intentionally public: Stripe servers cannot authenticate as WordPress users.
	 * Authorization is enforced in the callback via mandatory Stripe-Signature verification.
	 */
	public function action__rest_api_init() {
		register_rest_route(
			'thenfopa/v1',
			'/webhook/(?P<form_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest__webhook' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'form_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	/**
	 * Handle Stripe webhook REST requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest__webhook( $request ) {
		if ( ! $this->lib instanceof THENFOPA_Lib ) {
			return new WP_Error( 'thenfopa_unavailable', __( 'Payment handler unavailable.', 'thenameda-form-payments-stripe-wpforms' ), array( 'status' => 503 ) );
		}

		return $this->lib->handle_webhook( $request );
	}

	/**
	 * Register the transactions custom post type.
	 */
	private function register_post_type() {
		$labels = array(
			'name'          => __( 'Stripe Transactions', 'thenameda-form-payments-stripe-wpforms' ),
			'singular_name' => __( 'Stripe Transaction', 'thenameda-form-payments-stripe-wpforms' ),
			'menu_name'     => __( 'Stripe Transactions', 'thenameda-form-payments-stripe-wpforms' ),
			'all_items'     => __( 'All Transactions', 'thenameda-form-payments-stripe-wpforms' ),
			'edit_item'     => __( 'Transaction Detail', 'thenameda-form-payments-stripe-wpforms' ),
			'view_item'     => __( 'View Transaction', 'thenameda-form-payments-stripe-wpforms' ),
			'search_items'  => __( 'Search Transactions', 'thenameda-form-payments-stripe-wpforms' ),
			'not_found'     => __( 'No transactions found.', 'thenameda-form-payments-stripe-wpforms' ),
		);

		register_post_type(
			THENFOPA_POST_TYPE,
			array(
				'label'               => __( 'Stripe Transactions', 'thenameda-form-payments-stripe-wpforms' ),
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'delete_with_user'    => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'show_in_menu'        => true,
				'menu_position'       => 56,
				'show_in_nav_menus'   => false,
				'menu_icon'           => 'dashicons-money-alt',
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts'  => false,
					'publish_posts' => false,
				),
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
			)
		);
	}

	/**
	 * Activation callback (also callable from the main plugin file).
	 */
	public function action__plugin_activation() {
		$this->register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Permit programmatic transaction inserts during a front-end payment flow.
	 *
	 * @param array  $caps    Required capabilities.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Extra arguments.
	 * @return array
	 */
	public function filter__map_meta_cap( $caps, $cap, $user_id, $args ) {
		unset( $user_id );

		if ( 'create_posts' === $cap && ! empty( $args[0] ) && THENFOPA_POST_TYPE === $args[0] ) {
			return array( 'exist' );
		}

		return $caps;
	}
}

/**
 * Main accessor.
 *
 * @return THENFOPA
 */
function thenfopa() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return THENFOPA::instance();
}

