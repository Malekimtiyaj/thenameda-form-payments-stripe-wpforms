# Thenameda Form Payments with Stripe for WPForms

Collect **Stripe payments with WPForms**. Redirect users to Stripe Checkout on form submission, record transactions, issue refunds, and export payment data.

## Description

**Thenameda Form Payments with Stripe for WPForms** is an add-on for the [WPForms](https://wordpress.org/plugins/wpforms-lite/) plugin. It allows you to accept credit card and Stripe payments on any WPForms form.

Configure Stripe separately for each form directly in the WPForms Form Builder under **Settings → Stripe**.

When a visitor submits a Stripe-enabled form, they are securely redirected to **Stripe Checkout** to complete the payment. Once the payment succeeds, the transaction is stored in your WordPress dashboard for review.

The plugin works with both **WPForms Lite** and **WPForms Pro**.

## Features

- Per-form Stripe settings inside the WPForms Form Builder
  - **Settings → Stripe**
- Secure redirect checkout using **Stripe Checkout Sessions**
- Test and Live modes with separate API keys
- Support for **25 currencies**
- Map any WPForms field as the payment amount
- Use the WPForms payment total
- Optional quantity multiplier
- Optional customer email pre-fill
- Configurable success return URL
- Configurable cancel return URL
- Optional payment success message for the thank-you page
- `[thenfopa_stripe_details]` shortcode for displaying payment information
- Transaction listing with:
  - Customer
  - Total
  - Payment status
- Transaction detail view with the complete Stripe response
- One-click refunds from the transaction detail screen
- Optional Stripe webhooks for reliable payment status updates
- REST webhook endpoint available per form
- Webhook signing secret verification
- CSV export of payment data
- Free version supports up to **10 recent transaction records**
- Compatible with **WPForms Lite and WPForms Pro**

## How It Works

1. Enable Stripe on a WPForms form under **Settings → Stripe**.
2. Enter your Stripe Test or Live API keys.
3. Configure the payment amount and optional quantity/customer email fields.
4. Configure your success and cancel return URLs.
5. Publish the form.
6. When a visitor submits the form, the plugin creates a Stripe Checkout Session.
7. The visitor is redirected to Stripe Checkout to securely complete payment.
8. After payment, the customer returns to your configured success URL.
9. The plugin verifies the payment status and stores the transaction.
10. Transactions are available under **Stripe Transactions** in the WordPress admin.

For reliable payment status updates, you can also configure a Stripe webhook.

## Requirements

- WordPress **6.2 or higher**
- PHP **7.2 or higher**
- [WPForms Lite](https://wordpress.org/plugins/wpforms-lite/) or WPForms Pro
- A [Stripe](https://stripe.com/) account

## Installation

1. Make sure [WPForms Lite](https://wordpress.org/plugins/wpforms-lite/) or **WPForms Pro** is installed and active.
2. Upload the plugin folder to `/wp-content/plugins/` or install it through the WordPress Plugins screen.
3. Activate **Thenameda Form Payments with Stripe for WPForms** through the **Plugins** menu.
4. Edit a WPForms form.
5. Open **Settings → Stripe**.
6. Enable Stripe payments.
7. Enter your Stripe API keys.
8. Configure the payment amount and optional field mappings.
9. Save and publish the form.

### Configure Stripe Webhooks

For reliable payment status updates, configuring a Stripe webhook is recommended.

1. Open your Stripe Dashboard.
2. Go to **Developers → Webhooks**.
3. Add the Webhook URL displayed in the plugin's form settings.
4. Configure the required Stripe events.
5. Copy the webhook signing secret.
6. Paste the signing secret into the corresponding form's Stripe settings.
7. Save the form settings.

## Shortcode

Use the following shortcode on your success or thank-you page:

```text id="p3m8x1"
[thenfopa_stripe_details]
```

The shortcode displays the transaction information after a successful payment, including:

- Transaction ID
- Payment amount
- Payment status

The payment details are displayed once after the successful payment.

## Frequently Asked Questions

### Where do I get Stripe API keys?

Open the **Stripe Dashboard API Keys** page and copy your Publishable and Secret keys for the required environment.

You can use separate credentials for:

- Test mode
- Live mode

### How is the payment amount determined?

Select an **Amount Field** in the Stripe settings.

Alternatively, leave the setting at its default to use the **WPForms payment total**.

An optional quantity field can multiply the payment amount.

You can also configure a fixed payment amount as a fallback.

### Where can I see payments?

Stripe transactions are available under:

**Stripe Transactions**

in the WordPress admin menu.

### How do I show payment details on a thank-you page?

Add the following shortcode to your success return page:

```text id="h6v2qa"
[thenfopa_stripe_details]
```

The shortcode displays the payment details once after a successful payment.

### Does this work with WPForms Lite?

Yes.

This add-on works with both **WPForms Lite** and **WPForms Pro**.

## External Services

This plugin connects to the **Stripe API** to process payments and refunds.

### What Stripe Is and Why It Is Required

Stripe is a third-party payment processing service that securely accepts credit and debit card payments online.

This plugin requires Stripe because payment card data is collected and processed through **Stripe Checkout**, rather than being stored or processed directly on your WordPress website.

Without Stripe, the plugin cannot:

- Create Checkout Sessions
- Process or confirm payments
- Retrieve payment status
- Issue refunds

### What Data Is Sent and When

When a visitor submits a WPForms form with Stripe enabled, the plugin sends payment-related information to Stripe over HTTPS to create a Checkout Session.

Depending on your form configuration, this may include:

- Payment amount
- Currency
- Payment description or product name
- Customer email address, if mapped from a form field
- Form ID as metadata
- Entry ID as metadata
- Internal reference token as metadata

### Payment Verification

After payment, the plugin may retrieve the Checkout Session from Stripe to verify the payment status.

Site administrators can also request refunds through the Stripe API from the WordPress admin transaction screen.

### Stripe Webhooks

If a Stripe webhook is configured and a signing secret is provided, Stripe can send payment status events to your website.

These events may include:

- Completed Checkout Sessions
- Expired Checkout Sessions
- Refund events

Webhook requests are rejected unless they contain a valid **Stripe-Signature** that matches the configured signing secret.

### Card Data

Card details are entered by the customer directly on Stripe's hosted Checkout page and are handled by Stripe.

This plugin does **not** receive or store raw card numbers.

## Stripe Policies

- [Stripe Privacy Policy](https://stripe.com/privacy)
- [Stripe Terms of Service](https://stripe.com/legal/ssa)

## Changelog

### 1.0.0

- Initial release
- Added WPForms Stripe integration
- Added Stripe Checkout Sessions
- Added Test and Live modes
- Added support for 25 currencies
- Added configurable payment amounts
- Added WPForms payment total support
- Added quantity multiplier
- Added customer email support
- Added success and cancel return URLs
- Added payment success message
- Added Stripe transaction management
- Added transaction detail view
- Added refund functionality
- Added optional Stripe webhooks
- Added webhook signature verification
- Added CSV export
- Added `[thenfopa_stripe_details]` shortcode

## Upgrade Notice

### 1.0.0

Initial release.

## License

This plugin is licensed under the **GPLv3 or later**.

[GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html)