<?php
/**
 * Payment processing: Checkout Session creation, redirect, capture, refunds, webhooks.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the Stripe payment lifecycle for WPForms submissions.
 */
class THENFOPA_Lib {

	/**
	 * Checkout URLs created during the current request, keyed by "form_id:entry_id".
	 *
	 * @var array
	 */
	private $checkout_urls = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wpforms_process_complete', array( $this, 'action__process_complete' ), 10, 4 );
		add_filter( 'wpforms_process_entry_confirmation_redirect_confirmations', array( $this, 'filter__confirmations' ), 20, 4 );

		add_action( 'wp_ajax_thenfopa_refund_payment', array( $this, 'action__refund_payment' ) );
		add_shortcode( 'thenfopa_stripe_details', array( $this, 'shortcode__stripe_details' ) );
	}

	/**
	 * Create a Stripe Checkout Session right after the entry is processed.
	 *
	 * @param array $fields    Processed fields.
	 * @param array $entry     Raw entry.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 */
	public function action__process_complete( $fields, $entry, $form_data, $entry_id ) {
		unset( $entry );

		if ( empty( $form_data['id'] ) ) {
			return;
		}

		$form_id  = absint( $form_data['id'] );
		$settings = THENFOPA_Helpers::get_form_settings( $form_id );

		if ( empty( $settings['enable'] ) ) {
			return;
		}

		if ( empty( $settings['secret_key'] ) ) {
			$this->log( 'Stripe secret key missing for form #' . $form_id, $form_id );
			return;
		}

		$currency = strtoupper( $settings['currency'] );
		$amount   = THENFOPA_Helpers::calculate_amount( $settings, $fields, $currency );

		if ( $amount <= 0 ) {
			$this->log( 'Calculated Stripe amount is zero for form #' . $form_id, $form_id );
			return;
		}

		$ref         = wp_generate_password( 20, false, false );
		$success_url = '' !== $settings['success_url'] ? $settings['success_url'] : home_url( '/' );
		$cancel_url  = '' !== $settings['cancel_url'] ? $settings['cancel_url'] : home_url( '/' );

		$description = '' !== $settings['description']
			? $settings['description']
			: sprintf( '%s - %s', get_bloginfo( 'name' ), get_the_title( $form_id ) );
		$description = wp_strip_all_tags( $description );
		$description = mb_substr( $description, 0, 120 );

		$listener_success = add_query_arg(
			array( 'session_id' => '{CHECKOUT_SESSION_ID}' ),
			THENFOPA_Helpers::get_listener_url( 'return', $ref )
		);

		$payload = array(
			'mode'                => 'payment',
			'success_url'         => $listener_success,
			'cancel_url'          => THENFOPA_Helpers::get_listener_url( 'cancel', $ref ),
			'client_reference_id' => $ref,
			'line_items[0][price_data][currency]'     => strtolower( $currency ),
			'line_items[0][price_data][product_data][name]' => $description,
			'line_items[0][price_data][unit_amount]'  => THENFOPA_Helpers::to_stripe_amount( $amount, $currency ),
			'line_items[0][quantity]'                 => 1,
			'metadata[form_id]'   => $form_id,
			'metadata[entry_id]'  => absint( $entry_id ),
			'metadata[thenfopa_ref]'  => $ref,
		);

		$payer_email = $this->get_payer_email_from_fields( $settings, $fields );

		if ( '' !== $payer_email ) {
			$payload['customer_email'] = $payer_email;
		}

		$session = THENFOPA_Helpers::create_checkout_session( $settings, $payload );

		if ( is_wp_error( $session ) || empty( $session['id'] ) || empty( $session['url'] ) ) {
			$message = is_wp_error( $session ) ? $session->get_error_message() : __( 'Unknown error creating Stripe Checkout Session.', 'thenameda-form-payments-stripe-wpforms' );
			$this->log( 'Stripe Checkout Session creation failed: ' . $message, $form_id );
			return;
		}

		$quantity = 1;

		if ( '' !== $settings['quantity_field'] && isset( $fields[ $settings['quantity_field'] ] ) ) {
			$quantity = (float) THENFOPA_Helpers::extract_numeric_field_value( $fields[ $settings['quantity_field'] ] );
			$quantity = $quantity > 0 ? $quantity : 1;
		}

		$post_id = $this->create_pending_transaction(
			array(
				'form_id'     => $form_id,
				'entry_id'    => $entry_id,
				'session_id'  => (string) $session['id'],
				'ref'         => $ref,
				'amount'      => $amount,
				'quantity'    => $quantity,
				'currency'    => $currency,
				'payer_email' => $payer_email,
				'success_url' => $success_url,
				'cancel_url'  => $cancel_url,
				'form_data'   => $this->flatten_fields( $fields ),
			)
		);

		if ( ! $post_id ) {
			$this->log( 'Failed to store pending Stripe transaction for form #' . $form_id, $form_id );
			return;
		}

		$this->checkout_urls[ $form_id . ':' . $entry_id ] = esc_url_raw( $session['url'] );
	}

	/**
	 * Replace confirmations with a redirect to Stripe Checkout when a payment is pending.
	 *
	 * @param array $confirmations Confirmations.
	 * @param array $form_data     Form data.
	 * @param array $fields        Fields.
	 * @param int   $entry_id      Entry ID.
	 * @return array
	 */
	public function filter__confirmations( $confirmations, $form_data, $fields, $entry_id ) {
		unset( $fields );

		if ( empty( $form_data['id'] ) ) {
			return $confirmations;
		}

		$key = absint( $form_data['id'] ) . ':' . absint( $entry_id );

		if ( empty( $this->checkout_urls[ $key ] ) ) {
			return $confirmations;
		}

		return array(
			1 => array(
				'type'             => 'redirect',
				'redirect'         => $this->checkout_urls[ $key ],
				'redirect_new_tab' => '0',
			),
		);
	}

	/**
	 * Handle Stripe return/cancel listener requests.
	 *
	 * @param string $action Listener action.
	 */
	public function handle_listener( $action ) {
		$ref = isset( $_GET[ THENFOPA_Helpers::REF_QUERY_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ THENFOPA_Helpers::REF_QUERY_ARG ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $ref ) {
			return;
		}

		$post_id = $this->find_transaction_by_ref( $ref );

		if ( ! $post_id ) {
			return;
		}

		$form_id  = absint( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_id', true ) );
		$settings = THENFOPA_Helpers::get_form_settings( $form_id );

		if ( 'cancel' === $action ) {
			update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', 'cancelled' );
			$this->redirect( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'cancel_url', true ) );
		}

		$session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$stored     = (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'session_id', true );

		if ( '' === $session_id ) {
			$session_id = $stored;
		}

		if ( $session_id !== $stored ) {
			return;
		}

		$status = strtolower( (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', true ) );

		if ( in_array( $status, array( 'succeeded', 'paid', 'complete' ), true ) ) {
			$this->store_details_cookie( $post_id );
			$this->redirect( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'success_url', true ) );
		}

		$session = THENFOPA_Helpers::retrieve_checkout_session( $settings, $session_id );

		if ( is_wp_error( $session ) ) {
			$this->log( 'Stripe session retrieve failed: ' . $session->get_error_message(), $form_id );
			update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', 'failed' );
			$this->redirect( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'cancel_url', true ) );
		}

		$this->finalize_from_session( $post_id, $session, $settings );

		$final_status = strtolower( (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', true ) );

		if ( in_array( $final_status, array( 'succeeded', 'paid', 'complete' ), true ) ) {
			$this->store_details_cookie( $post_id );
			$this->redirect( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'success_url', true ) );
		}

		$this->redirect( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'cancel_url', true ) );
	}

	/**
	 * Handle Stripe webhook REST requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_webhook( $request ) {
		$form_id  = absint( $request['form_id'] );
		$settings = THENFOPA_Helpers::get_form_settings( $form_id );

		if ( empty( $settings['enable'] ) || empty( $settings['secret_key'] ) ) {
			return new WP_Error( 'thenfopa_disabled', __( 'Stripe is not enabled for this form.', 'thenameda-form-payments-stripe-wpforms' ), array( 'status' => 400 ) );
		}

		$payload = $request->get_body();
		$event   = json_decode( $payload, true );

		if ( ! is_array( $event ) || empty( $event['type'] ) ) {
			return new WP_Error( 'thenfopa_invalid_payload', __( 'Invalid webhook payload.', 'thenameda-form-payments-stripe-wpforms' ), array( 'status' => 400 ) );
		}

		// Webhooks are intentionally public (Stripe cannot authenticate as a WP user).
		// Authorization is enforced via mandatory Stripe-Signature verification.
		if ( empty( $settings['webhook_secret'] ) ) {
			return new WP_Error( 'thenfopa_webhook_secret_required', __( 'Webhook signing secret is required.', 'thenameda-form-payments-stripe-wpforms' ), array( 'status' => 403 ) );
		}

		$signature = $request->get_header( 'stripe-signature' );

		if ( ! $this->verify_webhook_signature( $payload, $signature, $settings['webhook_secret'] ) ) {
			return new WP_Error( 'thenfopa_bad_signature', __( 'Invalid Stripe webhook signature.', 'thenameda-form-payments-stripe-wpforms' ), array( 'status' => 401 ) );
		}

		$object = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();

		switch ( $event['type'] ) {
			case 'checkout.session.completed':
			case 'checkout.session.async_payment_succeeded':
				$ref = ! empty( $object['client_reference_id'] ) ? (string) $object['client_reference_id'] : '';
				if ( '' === $ref && ! empty( $object['metadata']['thenfopa_ref'] ) ) {
					$ref = (string) $object['metadata']['thenfopa_ref'];
				}
				$post_id = $ref ? $this->find_transaction_by_ref( $ref ) : 0;
				if ( $post_id ) {
					$this->finalize_from_session( $post_id, $object, $settings );
				}
				break;

			case 'checkout.session.expired':
			case 'checkout.session.async_payment_failed':
				$ref     = ! empty( $object['client_reference_id'] ) ? (string) $object['client_reference_id'] : '';
				$post_id = $ref ? $this->find_transaction_by_ref( $ref ) : 0;
				if ( $post_id ) {
					$status = ( 'checkout.session.expired' === $event['type'] ) ? 'expired' : 'failed';
					update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', $status );
					update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_response', wp_json_encode( $object ) );
				}
				break;

			case 'charge.refunded':
				$payment_intent = ! empty( $object['payment_intent'] ) ? (string) $object['payment_intent'] : '';
				if ( $payment_intent ) {
					$post_id = $this->find_transaction_by_transaction_id( $payment_intent );
					if ( $post_id ) {
						update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', 'refunded' );
						update_post_meta( $post_id, THENFOPA_META_PREFIX . 'refund_response', wp_json_encode( $object ) );
					}
				}
				break;
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}

	/**
	 * Update a transaction from a Checkout Session payload.
	 *
	 * @param int   $post_id  Transaction post ID.
	 * @param array $session  Session data.
	 * @param array $settings Form settings.
	 */
	private function finalize_from_session( $post_id, $session, $settings ) {
		unset( $settings );

		$payment_status = isset( $session['payment_status'] ) ? strtolower( (string) $session['payment_status'] ) : '';
		$session_status = isset( $session['status'] ) ? strtolower( (string) $session['status'] ) : '';

		$payment_intent_id = '';

		if ( ! empty( $session['payment_intent'] ) && is_string( $session['payment_intent'] ) ) {
			$payment_intent_id = $session['payment_intent'];
		} elseif ( ! empty( $session['payment_intent']['id'] ) ) {
			$payment_intent_id = $session['payment_intent']['id'];
		}

		$currency = ! empty( $session['currency'] )
			? strtoupper( (string) $session['currency'] )
			: (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'currency', true );

		$total = get_post_meta( $post_id, THENFOPA_META_PREFIX . 'amount', true );

		if ( isset( $session['amount_total'] ) ) {
			$total = THENFOPA_Helpers::from_stripe_amount( (int) $session['amount_total'], $currency );
		}

		$email = '';

		if ( ! empty( $session['customer_details']['email'] ) ) {
			$email = sanitize_email( $session['customer_details']['email'] );
		} elseif ( ! empty( $session['customer_email'] ) ) {
			$email = sanitize_email( $session['customer_email'] );
		}

		$is_paid = in_array( $payment_status, array( 'paid', 'no_payment_required' ), true )
			|| ( 'complete' === $session_status && 'unpaid' !== $payment_status );

		$status = $is_paid ? 'succeeded' : ( $payment_status ? $payment_status : $session_status );

		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', $status );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_response', wp_json_encode( $session ) );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'total', $total );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'currency', $currency );

		if ( '' !== $payment_intent_id ) {
			update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_id', $payment_intent_id );

			$transaction_post = get_post( $post_id );
			if ( $transaction_post && 0 === strpos( $transaction_post->post_title, 'THENFOPA-' ) ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $payment_intent_id,
					)
				);
			}
		}

		if ( '' !== $email ) {
			update_post_meta( $post_id, THENFOPA_META_PREFIX . 'payer_email', $email );
		}
	}

	/**
	 * Verify Stripe webhook signature (simplified HMAC check).
	 *
	 * @param string $payload   Raw body.
	 * @param string $header    Stripe-Signature header.
	 * @param string $secret    Webhook signing secret.
	 * @return bool
	 */
	private function verify_webhook_signature( $payload, $header, $secret ) {
		if ( '' === (string) $header || '' === (string) $secret ) {
			return false;
		}

		$parts = array();

		foreach ( explode( ',', $header ) as $item ) {
			$kv = explode( '=', trim( $item ), 2 );
			if ( 2 === count( $kv ) ) {
				$parts[ $kv[0] ][] = $kv[1];
			}
		}

		if ( empty( $parts['t'][0] ) || empty( $parts['v1'][0] ) ) {
			return false;
		}

		$timestamp = $parts['t'][0];
		$signed    = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );

		foreach ( $parts['v1'] as $signature ) {
			if ( hash_equals( $signed, $signature ) ) {
				// Reject timestamps older than 5 minutes.
				if ( abs( time() - (int) $timestamp ) > 300 ) {
					return false;
				}
				return true;
			}
		}

		return false;
	}

	/**
	 * Refund a payment (admin AJAX).
	 */
	public function action__refund_payment() {
		check_ajax_referer( THENFOPA_Helpers::NONCE_REFUND, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'thenameda-form-payments-stripe-wpforms' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( ! $post_id || ! THENFOPA_Helpers::is_transaction_post( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid transaction.', 'thenameda-form-payments-stripe-wpforms' ) ) );
		}

		$status = strtolower( (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', true ) );

		if ( 'refunded' === $status ) {
			wp_send_json_error( array( 'message' => __( 'This payment has already been refunded.', 'thenameda-form-payments-stripe-wpforms' ) ) );
		}

		$payment_intent_id = (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_id', true );

		if ( '' === $payment_intent_id ) {
			wp_send_json_error( array( 'message' => __( 'No payment intent to refund.', 'thenameda-form-payments-stripe-wpforms' ) ) );
		}

		$form_id  = absint( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_id', true ) );
		$settings = THENFOPA_Helpers::get_form_settings( $form_id );
		$refund   = THENFOPA_Helpers::create_refund( $settings, $payment_intent_id );

		if ( is_wp_error( $refund ) ) {
			wp_send_json_error( array( 'message' => $refund->get_error_message() ) );
		}

		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', 'refunded' );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'refund_response', wp_json_encode( $refund ) );

		wp_send_json_success( array( 'message' => __( 'Payment refunded successfully.', 'thenameda-form-payments-stripe-wpforms' ) ) );
	}

	/**
	 * Shortcode: [thenfopa_stripe_details] — show last successful payment details once.
	 *
	 * @return string
	 */
	public function shortcode__stripe_details() {
		$token = isset( $_COOKIE[ THENFOPA_Helpers::DETAILS_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ THENFOPA_Helpers::DETAILS_COOKIE ] ) ) : '';

		if ( '' === $token ) {
			return '';
		}

		$data = get_transient( 'thenfopa_details_' . $token );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return '';
		}

		// One-time display.
		delete_transient( 'thenfopa_details_' . $token );
		if ( ! headers_sent() ) {
			setcookie( THENFOPA_Helpers::DETAILS_COOKIE, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}

		$lines = array();

		if ( ! empty( $data['transaction_id'] ) ) {
			$lines[] = '<li><strong>' . esc_html__( 'Transaction ID:', 'thenameda-form-payments-stripe-wpforms' ) . '</strong> ' . esc_html( $data['transaction_id'] ) . '</li>';
		}
		if ( isset( $data['total'] ) && '' !== $data['total'] ) {
			$lines[] = '<li><strong>' . esc_html__( 'Amount:', 'thenameda-form-payments-stripe-wpforms' ) . '</strong> ' . esc_html( $data['total'] . ' ' . strtoupper( (string) $data['currency'] ) ) . '</li>';
		}
		if ( ! empty( $data['status'] ) ) {
			$lines[] = '<li><strong>' . esc_html__( 'Status:', 'thenameda-form-payments-stripe-wpforms' ) . '</strong> ' . esc_html( ucfirst( (string) $data['status'] ) ) . '</li>';
		}
		if ( ! empty( $data['message'] ) ) {
			$lines[] = '<li>' . esc_html( $data['message'] ) . '</li>';
		}

		if ( empty( $lines ) ) {
			return '';
		}

		return '<div class="thenfopa-stripe-details"><ul>' . implode( '', $lines ) . '</ul></div>';
	}

	/**
	 * Store a short-lived cookie + transient for the thank-you shortcode.
	 *
	 * @param int $post_id Transaction post ID.
	 */
	private function store_details_cookie( $post_id ) {
		$form_id  = absint( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_id', true ) );
		$settings = THENFOPA_Helpers::get_form_settings( $form_id );
		$token    = wp_generate_password( 16, false, false );

		$data = array(
			'transaction_id' => (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_id', true ),
			'total'          => get_post_meta( $post_id, THENFOPA_META_PREFIX . 'total', true ),
			'currency'       => (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'currency', true ),
			'status'         => (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', true ),
			'message'        => '' !== $settings['success_message']
				? $settings['success_message']
				: __( 'Payment successful. Thank you!', 'thenameda-form-payments-stripe-wpforms' ),
		);

		set_transient( 'thenfopa_details_' . $token, $data, HOUR_IN_SECONDS );

		if ( ! headers_sent() ) {
			setcookie( THENFOPA_Helpers::DETAILS_COOKIE, $token, time() + HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Create a pending transaction record.
	 *
	 * @param array $data Transaction data.
	 * @return int
	 */
	private function create_pending_transaction( $data ) {
		$post_id = wp_insert_post(
			array(
				'post_type'      => THENFOPA_POST_TYPE,
				'post_title'     => 'THENFOPA-' . $data['session_id'],
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_id', absint( $data['form_id'] ) );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'entry_id', absint( $data['entry_id'] ) );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'session_id', $data['session_id'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'ref', $data['ref'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'amount', $data['amount'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'quantity', $data['quantity'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'total', $data['amount'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'currency', $data['currency'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'payer_email', $data['payer_email'] );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'request_ip', THENFOPA_Helpers::get_client_ip() );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'payment_type', 'checkout' );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', 'pending' );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'success_url', esc_url_raw( $data['success_url'] ) );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'cancel_url', esc_url_raw( $data['cancel_url'] ) );
		update_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_data', $data['form_data'] );

		return $post_id;
	}

	/**
	 * Find a transaction post by its reference token.
	 *
	 * @param string $ref Reference token.
	 * @return int
	 */
	private function find_transaction_by_ref( $ref ) {
		$posts = get_posts(
			array(
				'post_type'      => THENFOPA_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => THENFOPA_META_PREFIX . 'ref', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $ref, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return ! empty( $posts ) ? absint( $posts[0] ) : 0;
	}

	/**
	 * Find a transaction by PaymentIntent ID.
	 *
	 * @param string $transaction_id PaymentIntent ID.
	 * @return int
	 */
	private function find_transaction_by_transaction_id( $transaction_id ) {
		$posts = get_posts(
			array(
				'post_type'      => THENFOPA_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => THENFOPA_META_PREFIX . 'transaction_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $transaction_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return ! empty( $posts ) ? absint( $posts[0] ) : 0;
	}

	/**
	 * Extract the payer email from submitted fields.
	 *
	 * @param array $settings Resolved settings.
	 * @param array $fields   Submitted fields.
	 * @return string
	 */
	private function get_payer_email_from_fields( $settings, $fields ) {
		if ( '' === $settings['email_field'] || ! isset( $fields[ $settings['email_field'] ] ) ) {
			return '';
		}

		$value = isset( $fields[ $settings['email_field'] ]['value'] ) ? $fields[ $settings['email_field'] ]['value'] : '';

		return is_email( $value ) ? sanitize_email( $value ) : '';
	}

	/**
	 * Reduce processed fields to a simple label => value map for storage.
	 *
	 * @param array $fields Processed fields.
	 * @return array
	 */
	private function flatten_fields( $fields ) {
		$flat = array();

		foreach ( (array) $fields as $field_id => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$label          = isset( $field['name'] ) && '' !== $field['name'] ? $field['name'] : (string) $field_id;
			$value          = isset( $field['value'] ) ? $field['value'] : '';
			$flat[ $label ] = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		}

		return $flat;
	}

	/**
	 * Redirect and exit (falls back to home URL).
	 *
	 * @param string $url Target URL.
	 */
	private function redirect( $url ) {
		$url = '' !== (string) $url ? $url : home_url( '/' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Log a message via WPForms logger when available.
	 *
	 * @param string $message Message.
	 * @param int    $form_id Form ID.
	 */
	private function log( $message, $form_id = 0 ) {
		if ( function_exists( 'wpforms_log' ) ) {
			wpforms_log(
				'THENFOPA Stripe',
				$message,
				array(
					'type'    => array( 'payment', 'error' ),
					'form_id' => absint( $form_id ),
				)
			);
		}
	}
}

