<?php
/**
 * Admin filters: WPForms builder settings panel and list table columns.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builder integration and list customizations.
 */
class THENFOPA_Admin_Filter {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wpforms_builder_settings_sections', array( $this, 'add_settings_section' ), 20, 2 );
		add_action( 'wpforms_form_settings_panel_content', array( $this, 'render_settings_panel' ), 20, 1 );
		add_action( 'wpforms_builder_enqueues', array( $this, 'enqueue_builder_assets' ) );

		add_filter( 'manage_' . THENFOPA_POST_TYPE . '_posts_columns', array( $this, 'posts_columns' ) );
		add_filter( 'manage_edit-' . THENFOPA_POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 1 );
		add_filter( 'plugin_action_links_' . THENFOPA_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Enqueue builder assets.
	 */
	public function enqueue_builder_assets() {
		wp_enqueue_style( THENFOPA_ADMIN_STYLE_HANDLE, THENFOPA_URL . 'assets/css/admin.css', array(), THENFOPA_VERSION );
		wp_enqueue_script( THENFOPA_ADMIN_SCRIPT_HANDLE, THENFOPA_URL . 'assets/js/admin.js', array( 'jquery', 'wpforms-builder' ), THENFOPA_VERSION, true );
	}

	/**
	 * Add the Stripe section to the form settings panel.
	 *
	 * @param array $sections  Sections.
	 * @param array $form_data Form data.
	 * @return array
	 */
	public function add_settings_section( $sections, $form_data ) {
		unset( $form_data );

		$sections['thenfopa_stripe'] = esc_html__( 'Stripe', 'thenameda-form-payments-stripe-wpforms' );

		return $sections;
	}

	/**
	 * Render the Stripe settings panel.
	 *
	 * @param WPForms_Builder_Panel_Settings $instance Settings panel instance.
	 */
	public function render_settings_panel( $instance ) {
		if ( empty( $instance->form_data ) ) {
			return;
		}

		$form_data = $instance->form_data;
		$form_id   = ! empty( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;

		$amount_fields   = THENFOPA_Helpers::get_amount_fields( $form_id );
		$quantity_fields = THENFOPA_Helpers::get_quantity_fields( $form_id );
		$email_fields    = THENFOPA_Helpers::get_email_fields( $form_id );
		$currencies      = THENFOPA_Helpers::get_currencies();
		$webhook_url     = $form_id ? THENFOPA_Helpers::get_webhook_url( $form_id ) : '';

		require THENFOPA_DIR . '/inc/admin/template/' . THENFOPA_PREFIX . '.template.php';
	}

	/**
	 * Register list columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function posts_columns( $columns ) {
		unset( $columns['date'] );

		$columns['thenfopa_form']   = __( 'Form', 'thenameda-form-payments-stripe-wpforms' );
		$columns['thenfopa_payer']  = __( 'Customer Email', 'thenameda-form-payments-stripe-wpforms' );
		$columns['thenfopa_total']  = __( 'Total', 'thenameda-form-payments-stripe-wpforms' );
		$columns['thenfopa_status'] = __( 'Status', 'thenameda-form-payments-stripe-wpforms' );
		$columns['date']        = __( 'Date', 'thenameda-form-payments-stripe-wpforms' );

		return $columns;
	}

	/**
	 * Register sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['thenfopa_total'] = 'thenfopa_total';

		return $columns;
	}

	/**
	 * Remove inapplicable row actions.
	 *
	 * @param array $actions Row actions.
	 * @return array
	 */
	public function post_row_actions( $actions ) {
		if ( get_post_type() === THENFOPA_POST_TYPE ) {
			unset( $actions['view'], $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$transactions = '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . THENFOPA_POST_TYPE ) ) . '">' . esc_html__( 'Transactions', 'thenameda-form-payments-stripe-wpforms' ) . '</a>';

		array_unshift( $links, $transactions );

		return $links;
	}
}

