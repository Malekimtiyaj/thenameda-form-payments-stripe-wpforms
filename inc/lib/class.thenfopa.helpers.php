<?php
/**
 * Shared helpers for Thenameda Form Payments with Stripe for WPForms.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static helper methods shared across the plugin.
 */
class THENFOPA_Helpers {

	const SETTING_ENABLE              = 'thenfopa_enable';
	const SETTING_MODE_TEST           = 'thenfopa_mode_test';
	const SETTING_TEST_PUBLISHABLE    = 'thenfopa_test_publishable_key';
	const SETTING_TEST_SECRET         = 'thenfopa_test_secret_key';
	const SETTING_LIVE_PUBLISHABLE    = 'thenfopa_live_publishable_key';
	const SETTING_LIVE_SECRET         = 'thenfopa_live_secret_key';
	const SETTING_WEBHOOK_SECRET      = 'thenfopa_webhook_secret';
	const SETTING_CURRENCY            = 'thenfopa_currency';
	const SETTING_FIXED_AMOUNT        = 'thenfopa_fixed_amount';
	const SETTING_AMOUNT_FIELD        = 'thenfopa_amount_field';
	const SETTING_QUANTITY_FIELD      = 'thenfopa_quantity_field';
	const SETTING_EMAIL_FIELD         = 'thenfopa_email_field';
	const SETTING_DESCRIPTION         = 'thenfopa_description';
	const SETTING_SUCCESS_URL         = 'thenfopa_success_url';
	const SETTING_CANCEL_URL          = 'thenfopa_cancel_url';
	const SETTING_SUCCESS_MESSAGE     = 'thenfopa_success_message';

	const LISTENER_QUERY_ARG = 'thenfopa_listener';
	const REF_QUERY_ARG      = 'thenfopa_ref';
	const NONCE_REFUND       = 'thenfopa_refund_payment';
	const DETAILS_COOKIE     = 'thenfopa_stripe_details';

	/**
	 * Transaction detail fields (meta key => label).
	 *
	 * @return array
	 */
	public static function get_data_fields() {
		return array(
			'form_id'              => __( 'Form', 'thenameda-form-payments-stripe-wpforms' ),
			'entry_id'             => __( 'Entry ID', 'thenameda-form-payments-stripe-wpforms' ),
			'session_id'           => __( 'Checkout Session ID', 'thenameda-form-payments-stripe-wpforms' ),
			'transaction_id'       => __( 'Payment Intent / Charge ID', 'thenameda-form-payments-stripe-wpforms' ),
			'payer_email'          => __( 'Customer Email', 'thenameda-form-payments-stripe-wpforms' ),
			'amount'               => __( 'Amount', 'thenameda-form-payments-stripe-wpforms' ),
			'quantity'             => __( 'Quantity', 'thenameda-form-payments-stripe-wpforms' ),
			'total'                => __( 'Total', 'thenameda-form-payments-stripe-wpforms' ),
			'currency'             => __( 'Currency', 'thenameda-form-payments-stripe-wpforms' ),
			'request_ip'           => __( 'Request IP', 'thenameda-form-payments-stripe-wpforms' ),
			'payment_type'         => __( 'Payment Type', 'thenameda-form-payments-stripe-wpforms' ),
			'transaction_status'   => __( 'Transaction Status', 'thenameda-form-payments-stripe-wpforms' ),
			'form_data'            => __( 'Form Data', 'thenameda-form-payments-stripe-wpforms' ),
			'transaction_response' => __( 'Transaction Response', 'thenameda-form-payments-stripe-wpforms' ),
			'refund_payment'       => __( 'Refund Payment', 'thenameda-form-payments-stripe-wpforms' ),
		);
	}

	/**
	 * Stripe-supported currencies shown in the settings UI.
	 *
	 * @return array
	 */
	public static function get_currencies() {
		return array(
			'AUD' => __( 'Australian Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'BRL' => __( 'Brazilian Real', 'thenameda-form-payments-stripe-wpforms' ),
			'CAD' => __( 'Canadian Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'CZK' => __( 'Czech Koruna', 'thenameda-form-payments-stripe-wpforms' ),
			'DKK' => __( 'Danish Krone', 'thenameda-form-payments-stripe-wpforms' ),
			'EUR' => __( 'Euro', 'thenameda-form-payments-stripe-wpforms' ),
			'HKD' => __( 'Hong Kong Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'HUF' => __( 'Hungarian Forint', 'thenameda-form-payments-stripe-wpforms' ),
			'ILS' => __( 'Israeli New Shekel', 'thenameda-form-payments-stripe-wpforms' ),
			'JPY' => __( 'Japanese Yen', 'thenameda-form-payments-stripe-wpforms' ),
			'MYR' => __( 'Malaysian Ringgit', 'thenameda-form-payments-stripe-wpforms' ),
			'MXN' => __( 'Mexican Peso', 'thenameda-form-payments-stripe-wpforms' ),
			'TWD' => __( 'New Taiwan Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'NZD' => __( 'New Zealand Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'NOK' => __( 'Norwegian Krone', 'thenameda-form-payments-stripe-wpforms' ),
			'PHP' => __( 'Philippine Peso', 'thenameda-form-payments-stripe-wpforms' ),
			'PLN' => __( 'Polish Zloty', 'thenameda-form-payments-stripe-wpforms' ),
			'GBP' => __( 'Pound Sterling', 'thenameda-form-payments-stripe-wpforms' ),
			'RUB' => __( 'Russian Ruble', 'thenameda-form-payments-stripe-wpforms' ),
			'SGD' => __( 'Singapore Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'SEK' => __( 'Swedish Krona', 'thenameda-form-payments-stripe-wpforms' ),
			'CHF' => __( 'Swiss Franc', 'thenameda-form-payments-stripe-wpforms' ),
			'THB' => __( 'Thai Baht', 'thenameda-form-payments-stripe-wpforms' ),
			'USD' => __( 'United States Dollar', 'thenameda-form-payments-stripe-wpforms' ),
			'INR' => __( 'Indian Rupee', 'thenameda-form-payments-stripe-wpforms' ),
		);
	}

	/**
	 * Zero-decimal Stripe currencies.
	 *
	 * @return array
	 */
	public static function get_zero_decimal_currencies() {
		return array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' );
	}

	/**
	 * Whether WPForms is active.
	 *
	 * @return bool
	 */
	public static function is_wpforms_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'wpforms-lite/wpforms.php' ) || is_plugin_active( 'wpforms/wpforms.php' );
	}

	/**
	 * Raw form data for a form ID.
	 *
	 * @param int $form_id WPForms form ID.
	 * @return array
	 */
	public static function get_form_data( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id || ! function_exists( 'wpforms' ) ) {
			return array();
		}

		$form_data = wpforms()->obj( 'form' )->get( $form_id, array( 'content_only' => true ) );

		return is_array( $form_data ) ? $form_data : array();
	}

	/**
	 * Per-form Stripe settings resolved from form JSON.
	 *
	 * @param int $form_id WPForms form ID.
	 * @return array
	 */
	public static function get_form_settings( $form_id ) {
		$defaults = array(
			'enable'           => false,
			'mode_test'        => false,
			'publishable_key'  => '',
			'secret_key'       => '',
			'webhook_secret'   => '',
			'currency'         => 'USD',
			'fixed_amount'     => 0.0,
			'amount_field'     => '',
			'quantity_field'   => '',
			'email_field'      => '',
			'description'      => '',
			'success_url'      => '',
			'cancel_url'       => '',
			'success_message'  => '',
		);

		$form_data = self::get_form_data( $form_id );

		if ( empty( $form_data['settings'] ) || ! is_array( $form_data['settings'] ) ) {
			return $defaults;
		}

		$s         = $form_data['settings'];
		$mode_test = ! empty( $s[ self::SETTING_MODE_TEST ] );

		return array(
			'enable'          => ! empty( $s[ self::SETTING_ENABLE ] ),
			'mode_test'       => $mode_test,
			'publishable_key' => $mode_test
				? ( isset( $s[ self::SETTING_TEST_PUBLISHABLE ] ) ? trim( (string) $s[ self::SETTING_TEST_PUBLISHABLE ] ) : '' )
				: ( isset( $s[ self::SETTING_LIVE_PUBLISHABLE ] ) ? trim( (string) $s[ self::SETTING_LIVE_PUBLISHABLE ] ) : '' ),
			'secret_key'      => $mode_test
				? ( isset( $s[ self::SETTING_TEST_SECRET ] ) ? trim( (string) $s[ self::SETTING_TEST_SECRET ] ) : '' )
				: ( isset( $s[ self::SETTING_LIVE_SECRET ] ) ? trim( (string) $s[ self::SETTING_LIVE_SECRET ] ) : '' ),
			'webhook_secret'  => isset( $s[ self::SETTING_WEBHOOK_SECRET ] ) ? trim( (string) $s[ self::SETTING_WEBHOOK_SECRET ] ) : '',
			'currency'        => isset( $s[ self::SETTING_CURRENCY ] ) && '' !== $s[ self::SETTING_CURRENCY ] ? sanitize_text_field( $s[ self::SETTING_CURRENCY ] ) : 'USD',
			'fixed_amount'    => isset( $s[ self::SETTING_FIXED_AMOUNT ] ) ? self::sanitize_amount( (string) $s[ self::SETTING_FIXED_AMOUNT ] ) : 0.0,
			'amount_field'    => isset( $s[ self::SETTING_AMOUNT_FIELD ] ) ? (string) $s[ self::SETTING_AMOUNT_FIELD ] : '',
			'quantity_field'  => isset( $s[ self::SETTING_QUANTITY_FIELD ] ) ? (string) $s[ self::SETTING_QUANTITY_FIELD ] : '',
			'email_field'     => isset( $s[ self::SETTING_EMAIL_FIELD ] ) ? (string) $s[ self::SETTING_EMAIL_FIELD ] : '',
			'description'     => isset( $s[ self::SETTING_DESCRIPTION ] ) ? sanitize_text_field( $s[ self::SETTING_DESCRIPTION ] ) : '',
			'success_url'     => isset( $s[ self::SETTING_SUCCESS_URL ] ) ? esc_url_raw( $s[ self::SETTING_SUCCESS_URL ] ) : '',
			'cancel_url'      => isset( $s[ self::SETTING_CANCEL_URL ] ) ? esc_url_raw( $s[ self::SETTING_CANCEL_URL ] ) : '',
			'success_message' => isset( $s[ self::SETTING_SUCCESS_MESSAGE ] ) ? sanitize_text_field( $s[ self::SETTING_SUCCESS_MESSAGE ] ) : '',
		);
	}

	/**
	 * Fields usable as an amount source.
	 *
	 * @param int $form_id WPForms form ID.
	 * @return array
	 */
	public static function get_amount_fields( $form_id ) {
		$types = array(
			'number',
			'number-slider',
			'text',
			'hidden',
			'payment-single',
			'payment-select',
			'payment-multiple',
			'payment-checkbox',
			'payment-total',
		);

		return self::get_form_fields_by_type( $form_id, $types );
	}

	/**
	 * Fields usable as a quantity source.
	 *
	 * @param int $form_id WPForms form ID.
	 * @return array
	 */
	public static function get_quantity_fields( $form_id ) {
		return self::get_form_fields_by_type( $form_id, array( 'number', 'number-slider', 'select', 'radio', 'hidden', 'text' ) );
	}

	/**
	 * Email fields for a form.
	 *
	 * @param int $form_id WPForms form ID.
	 * @return array
	 */
	public static function get_email_fields( $form_id ) {
		return self::get_form_fields_by_type( $form_id, array( 'email', 'text' ) );
	}

	/**
	 * Get form fields limited to given types.
	 *
	 * @param int   $form_id WPForms form ID.
	 * @param array $types   Allowed field types.
	 * @return array
	 */
	public static function get_form_fields_by_type( $form_id, $types ) {
		$form_id = absint( $form_id );

		if ( ! $form_id || ! function_exists( 'wpforms_get_form_fields' ) ) {
			return array();
		}

		$fields = wpforms_get_form_fields( $form_id, $types );

		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * Human-readable label for a field.
	 *
	 * @param int    $form_id  Form ID.
	 * @param string $field_id Field ID.
	 * @return string
	 */
	public static function get_field_label( $form_id, $field_id ) {
		$form_data = self::get_form_data( $form_id );

		if ( ! empty( $form_data['fields'][ $field_id ]['label'] ) ) {
			return wp_strip_all_tags( $form_data['fields'][ $field_id ]['label'] );
		}

		/* translators: %s: field ID. */
		return sprintf( __( 'Field #%s', 'thenameda-form-payments-stripe-wpforms' ), $field_id );
	}

	/**
	 * Compute the payable amount from submitted fields.
	 *
	 * @param array  $settings Resolved form settings.
	 * @param array  $fields   Submitted fields from wpforms_process_complete.
	 * @param string $currency Currency code.
	 * @return float
	 */
	public static function calculate_amount( $settings, $fields, $currency = 'USD' ) {
		$amount = 0.0;

		if ( '' !== $settings['amount_field'] && isset( $fields[ $settings['amount_field'] ] ) ) {
			$amount = self::extract_numeric_field_value( $fields[ $settings['amount_field'] ] );
		} elseif ( function_exists( 'wpforms_get_total_payment' ) ) {
			$amount = (float) wpforms_get_total_payment( $fields );
		}

		if ( $amount <= 0 && ! empty( $settings['fixed_amount'] ) ) {
			$amount = (float) $settings['fixed_amount'];
		}

		$quantity = 1.0;

		if ( '' !== $settings['quantity_field'] && isset( $fields[ $settings['quantity_field'] ] ) ) {
			$quantity = self::extract_numeric_field_value( $fields[ $settings['quantity_field'] ] );

			if ( $quantity <= 0 ) {
				$quantity = 1.0;
			}
		}

		$total = $amount * $quantity;

		return round( (float) $total, self::get_currency_decimals( $currency ) );
	}

	/**
	 * Get a numeric value from a submitted field array.
	 *
	 * @param array $field Submitted field data.
	 * @return float
	 */
	public static function extract_numeric_field_value( $field ) {
		if ( ! is_array( $field ) ) {
			return (float) self::sanitize_amount( (string) $field );
		}

		if ( isset( $field['amount_raw'] ) && '' !== $field['amount_raw'] ) {
			return (float) self::sanitize_amount( (string) $field['amount_raw'] );
		}

		if ( isset( $field['amount'] ) && '' !== $field['amount'] ) {
			return (float) self::sanitize_amount( (string) $field['amount'] );
		}

		if ( isset( $field['value'] ) ) {
			return (float) self::sanitize_amount( (string) $field['value'] );
		}

		return 0.0;
	}

	/**
	 * Normalize a localized/formatted amount into a float.
	 *
	 * @param string $amount Raw amount.
	 * @return float
	 */
	public static function sanitize_amount( $amount ) {
		if ( function_exists( 'wpforms_sanitize_amount' ) ) {
			return (float) wpforms_sanitize_amount( $amount );
		}

		$amount = preg_replace( '/[^0-9.,\-]/', '', (string) $amount );
		$amount = str_replace( ',', '', $amount );

		return (float) $amount;
	}

	/**
	 * Decimal places for a currency.
	 *
	 * @param string $currency Currency code.
	 * @return int
	 */
	public static function get_currency_decimals( $currency ) {
		return in_array( strtoupper( (string) $currency ), self::get_zero_decimal_currencies(), true ) ? 0 : 2;
	}

	/**
	 * Convert a major-unit amount to Stripe's smallest currency unit.
	 *
	 * @param float  $amount   Amount in major units.
	 * @param string $currency Currency code.
	 * @return int
	 */
	public static function to_stripe_amount( $amount, $currency ) {
		$decimals = self::get_currency_decimals( $currency );

		if ( 0 === $decimals ) {
			return (int) round( (float) $amount );
		}

		return (int) round( (float) $amount * 100 );
	}

	/**
	 * Convert Stripe's smallest currency unit back to major units.
	 *
	 * @param int    $stripe_amount Amount in smallest units.
	 * @param string $currency      Currency code.
	 * @return float
	 */
	public static function from_stripe_amount( $stripe_amount, $currency ) {
		$decimals = self::get_currency_decimals( $currency );

		if ( 0 === $decimals ) {
			return (float) $stripe_amount;
		}

		return round( (float) $stripe_amount / 100, $decimals );
	}

	/**
	 * Format an amount for display.
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function format_amount( $amount, $currency ) {
		return number_format( (float) $amount, self::get_currency_decimals( $currency ), '.', '' );
	}

	/**
	 * Stripe API base URL.
	 *
	 * @return string
	 */
	public static function get_api_base() {
		return 'https://api.stripe.com/v1';
	}

	/**
	 * Perform a Stripe API request.
	 *
	 * @param string $secret_key Secret API key.
	 * @param string $method     HTTP method.
	 * @param string $path       API path (e.g. checkout/sessions).
	 * @param array  $body       Request body (form-encoded).
	 * @return array|WP_Error
	 */
	public static function stripe_request( $secret_key, $method, $path, $body = array() ) {
		if ( '' === (string) $secret_key ) {
			return new WP_Error( 'thenfopa_missing_credentials', __( 'Stripe secret key is not configured.', 'thenameda-form-payments-stripe-wpforms' ) );
		}

		$url  = trailingslashit( self::get_api_base() ) . ltrim( $path, '/' );
		$args = array(
			'timeout' => 45,
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret_key,
				'Stripe-Version' => '2023-10-16',
			),
			'method'  => strtoupper( $method ),
		);

		if ( ! empty( $body ) && in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( $url, $args );

		return self::parse_api_response( $response );
	}

	/**
	 * Create a Stripe Checkout Session.
	 *
	 * @param array $settings Resolved form settings.
	 * @param array $payload  Session parameters (form-encoded friendly).
	 * @return array|WP_Error
	 */
	public static function create_checkout_session( $settings, $payload ) {
		return self::stripe_request( $settings['secret_key'], 'POST', 'checkout/sessions', $payload );
	}

	/**
	 * Retrieve a Checkout Session.
	 *
	 * @param array  $settings   Resolved form settings.
	 * @param string $session_id Session ID.
	 * @return array|WP_Error
	 */
	public static function retrieve_checkout_session( $settings, $session_id ) {
		$path = 'checkout/sessions/' . rawurlencode( $session_id ) . '?expand[]=payment_intent';

		return self::stripe_request( $settings['secret_key'], 'GET', $path );
	}

	/**
	 * Create a refund for a PaymentIntent or Charge.
	 *
	 * @param array  $settings          Resolved form settings.
	 * @param string $payment_intent_id PaymentIntent ID.
	 * @return array|WP_Error
	 */
	public static function create_refund( $settings, $payment_intent_id ) {
		return self::stripe_request(
			$settings['secret_key'],
			'POST',
			'refunds',
			array(
				'payment_intent' => $payment_intent_id,
			)
		);
	}

	/**
	 * Parse a Stripe API response into a decoded array or WP_Error.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return array|WP_Error
	 */
	private static function parse_api_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = __( 'Unexpected Stripe API response.', 'thenameda-form-payments-stripe-wpforms' );

			if ( ! empty( $body['error']['message'] ) ) {
				$message = $body['error']['message'];
			}

			return new WP_Error( 'thenfopa_api_error', $message, $body );
		}

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Client IP address.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip        = trim( $forwarded[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Build a listener URL used as Stripe success/cancel URLs.
	 *
	 * @param string $action Listener action (return|cancel).
	 * @param string $ref    Internal reference token.
	 * @return string
	 */
	public static function get_listener_url( $action, $ref ) {
		return add_query_arg(
			array(
				self::LISTENER_QUERY_ARG => sanitize_key( $action ),
				self::REF_QUERY_ARG      => rawurlencode( $ref ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Webhook endpoint URL for a form.
	 *
	 * @param int $form_id Form ID.
	 * @return string
	 */
	public static function get_webhook_url( $form_id ) {
		return rest_url( 'thenfopa/v1/webhook/' . absint( $form_id ) );
	}

	/**
	 * Whether a post is a plugin transaction record.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_transaction_post( $post_id ) {
		$post = get_post( absint( $post_id ) );

		return $post && THENFOPA_POST_TYPE === $post->post_type;
	}
}

