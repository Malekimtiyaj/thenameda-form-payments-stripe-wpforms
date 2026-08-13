<?php
/**
 * Stripe settings panel rendered inside the WPForms form builder.
 *
 * Expects: $form_data, $form_id, $amount_fields, $quantity_fields, $email_fields, $currencies, $webhook_url.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$thenfopa_amount_options   = array( '' => esc_html__( 'Use WPForms payment total', 'thenameda-form-payments-stripe-wpforms' ) );
$thenfopa_quantity_options = array( '' => esc_html__( 'None', 'thenameda-form-payments-stripe-wpforms' ) );
$thenfopa_email_options    = array( '' => esc_html__( 'None', 'thenameda-form-payments-stripe-wpforms' ) );

foreach ( $amount_fields as $thenfopa_field_id => $thenfopa_field ) {
	$thenfopa_amount_options[ (string) $thenfopa_field_id ] = THENFOPA_Helpers::get_field_label( $form_id, $thenfopa_field_id );
}

foreach ( $quantity_fields as $thenfopa_field_id => $thenfopa_field ) {
	$thenfopa_quantity_options[ (string) $thenfopa_field_id ] = THENFOPA_Helpers::get_field_label( $form_id, $thenfopa_field_id );
}

foreach ( $email_fields as $thenfopa_field_id => $thenfopa_field ) {
	$thenfopa_email_options[ (string) $thenfopa_field_id ] = THENFOPA_Helpers::get_field_label( $form_id, $thenfopa_field_id );
}
?>
<div class="wpforms-panel-content-section wpforms-panel-content-section-thenfopa_stripe" data-panel="thenfopa_stripe">
	<div class="wpforms-panel-content-section-title">
		<?php esc_html_e( 'Stripe', 'thenameda-form-payments-stripe-wpforms' ); ?>
	</div>

	<div class="thenfopa-settings">
		<?php
		wpforms_panel_field(
			'toggle',
			'settings',
			THENFOPA_Helpers::SETTING_ENABLE,
			$form_data,
			esc_html__( 'Enable Stripe payments', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Redirect users to Stripe Checkout to complete payment after they submit this form.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'toggle',
			'settings',
			THENFOPA_Helpers::SETTING_MODE_TEST,
			$form_data,
			esc_html__( 'Enable Test mode', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Use Stripe test API keys to process test payments without charging real cards.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_TEST_PUBLISHABLE,
			$form_data,
			esc_html__( 'Test Publishable Key', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Your Stripe test publishable key (pk_test_…). Optional for Checkout redirect.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_TEST_SECRET,
			$form_data,
			esc_html__( 'Test Secret Key', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Your Stripe test secret key (sk_test_…).', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_LIVE_PUBLISHABLE,
			$form_data,
			esc_html__( 'Live Publishable Key', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Your Stripe live publishable key (pk_live_…). Optional for Checkout redirect.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_LIVE_SECRET,
			$form_data,
			esc_html__( 'Live Secret Key', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Your Stripe live secret key (sk_live_…).', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_WEBHOOK_SECRET,
			$form_data,
			esc_html__( 'Webhook Signing Secret', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Required to use webhooks. Signing secret from your Stripe webhook endpoint (whsec_…). Requests without a valid Stripe-Signature are rejected.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'select',
			'settings',
			THENFOPA_Helpers::SETTING_CURRENCY,
			$form_data,
			esc_html__( 'Currency', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'default' => 'USD',
				'options' => $currencies,
				'tooltip' => esc_html__( 'Currency used for Stripe transactions on this form.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_FIXED_AMOUNT,
			$form_data,
			esc_html__( 'Fixed payment amount', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'type'        => 'number',
				'placeholder' => '0.00',
				'tooltip'     => esc_html__( 'Charged when the form has no payment/amount field value. Leave at 0 to require an amount from the form fields below.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'select',
			'settings',
			THENFOPA_Helpers::SETTING_AMOUNT_FIELD,
			$form_data,
			esc_html__( 'Amount field', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'options' => $thenfopa_amount_options,
				'tooltip' => esc_html__( 'Select the field that holds the payment amount. Leave as default to use the WPForms payment total. If this yields 0, the fixed amount above is used.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'select',
			'settings',
			THENFOPA_Helpers::SETTING_QUANTITY_FIELD,
			$form_data,
			esc_html__( 'Quantity field (optional)', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'options' => $thenfopa_quantity_options,
				'tooltip' => esc_html__( 'If set, the amount is multiplied by the value of this field.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'select',
			'settings',
			THENFOPA_Helpers::SETTING_EMAIL_FIELD,
			$form_data,
			esc_html__( 'Customer email field (optional)', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'options' => $thenfopa_email_options,
				'tooltip' => esc_html__( 'Pre-fill the customer email on Stripe Checkout using this field.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_DESCRIPTION,
			$form_data,
			esc_html__( 'Payment description (optional)', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Product name shown on Stripe Checkout. Defaults to your site and form name.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_SUCCESS_MESSAGE,
			$form_data,
			esc_html__( 'Payment success message (optional)', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Shown by the [thenfopa_stripe_details] shortcode on your thank-you page after a successful payment.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_SUCCESS_URL,
			$form_data,
			esc_html__( 'Success return URL (optional)', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Where to send the user after a successful payment. Defaults to the site home. Add [thenfopa_stripe_details] on that page to show transaction info.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			THENFOPA_Helpers::SETTING_CANCEL_URL,
			$form_data,
			esc_html__( 'Cancel return URL (optional)', 'thenameda-form-payments-stripe-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Where to send the user if they cancel the payment. Defaults to the site home.', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);
		?>

		<?php if ( $webhook_url ) : ?>
			<p class="thenfopa-settings__note description">
				<strong><?php esc_html_e( 'Webhook URL:', 'thenameda-form-payments-stripe-wpforms' ); ?></strong>
				<code><?php echo esc_html( $webhook_url ); ?></code><br>
				<?php esc_html_e( 'Add this URL in Stripe Dashboard → Developers → Webhooks, then paste the signing secret (whsec_…) above. Webhook requests are rejected without a valid signature. Recommended events: checkout.session.completed, checkout.session.expired, charge.refunded.', 'thenameda-form-payments-stripe-wpforms' ); ?>
			</p>
		<?php endif; ?>

		<p class="thenfopa-settings__note description">
			<?php
			printf(
				wp_kses(
					/* translators: %s: Stripe dashboard API keys URL. */
					__( 'Get your API keys from the <a href="%s" target="_blank" rel="noopener noreferrer">Stripe Dashboard</a>.', 'thenameda-form-payments-stripe-wpforms' ),
					array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
				),
				'https://dashboard.stripe.com/apikeys'
			);
			?>
		</p>
	</div>
</div>
