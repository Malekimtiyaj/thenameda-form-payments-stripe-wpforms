=== Thenameda Form Payments with Stripe for WPForms ===

Contributors: thenameda123
Tags: WPForms, Stripe, payments, donation, online payment
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Collect Stripe payments with WPForms. Redirect users to Stripe Checkout on submit, record transactions, issue refunds, and export payment data.

== Description ==

**Thenameda Form Payments with Stripe for WPForms** is an add-on for the [WPForms](https://wordpress.org/plugins/wpforms-lite/) plugin. It lets you accept credit card and Stripe payments on any WPForms form.

Configure Stripe per form directly in the WPForms form builder (**Settings → Stripe**). When a visitor submits the form they are redirected to **Stripe Checkout** to complete the payment securely. Once the payment succeeds, the transaction is stored in your WordPress dashboard for review.

Works with **WPForms Lite** and **WPForms Pro**.

= Features =

* Per-form Stripe settings inside the WPForms form builder (Settings → Stripe).
* Secure redirect checkout using Stripe Checkout Sessions.
* Test and Live modes with separate API keys.
* 25 supported currencies.
* Map any field as the payment amount, or use the WPForms payment total.
* Optional quantity multiplier and customer email pre-fill.
* Configurable success and cancel return URLs.
* Optional payment success message for the thank-you page.
* `[thenfopa_stripe_details]` shortcode to show transaction ID, amount, and status once after payment.
* Transaction listing with customer, total, and status columns.
* Transaction detail view with the full Stripe response.
* One-click refunds from the transaction detail screen.
* Optional Stripe webhooks for reliable status updates (REST endpoint per form; signing secret required).
* CSV export of payment data (up to 10 recent records).

= Requirements =

* WordPress 6.2 or higher
* PHP 7.2 or higher
* [WPForms Lite](https://wordpress.org/plugins/wpforms-lite/) or WPForms Pro
* A [Stripe](https://stripe.com/) account

== Installation ==

1. Make sure [WPForms Lite](https://wordpress.org/plugins/wpforms-lite/) (or WPForms Pro) is installed and active.
2. Upload this plugin folder to `/wp-content/plugins/` or install it from the Plugins screen.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Edit a form, open **Settings → Stripe**, enable Stripe, and enter your Stripe API keys.
5. (Recommended) Add the shown Webhook URL in your Stripe Dashboard → Developers → Webhooks, and paste the webhook signing secret into the form settings.

== Frequently Asked Questions ==

= Where do I get Stripe API keys? =

Open the [Stripe Dashboard API keys page](https://dashboard.stripe.com/apikeys) and copy your Publishable and Secret keys for Test and/or Live mode.

= How is the payment amount determined? =

Select an **Amount field** in the Stripe settings, or leave it on the default to use the WPForms payment total. An optional quantity field multiplies the amount. You can also set a fixed payment amount as a fallback.

= Where can I see payments? =

Under **Stripe Transactions** in the WordPress admin menu.

= How do I show payment details on a thank-you page? =

Add the shortcode `[thenfopa_stripe_details]` to your success return page. It displays once after a successful payment.

= Does this work with WPForms Lite? =

Yes. This add-on works with both WPForms Lite and WPForms Pro.

== External Services ==

This plugin connects to the [Stripe](https://stripe.com/) API to process payments and refunds.

**What Stripe is and why it is required**

Stripe is a third-party payment processing service that securely accepts credit and debit card payments online. This plugin requires Stripe because payment card data is collected and charged through Stripe Checkout rather than being stored or processed on your WordPress site. Without Stripe, the plugin cannot create Checkout Sessions, confirm payments, or issue refunds.

**What data is sent and when**

When a visitor submits a WPForms form with Stripe enabled, the plugin sends payment-related data to Stripe over HTTPS to create a Checkout Session. Depending on your form configuration, this may include:

* Payment amount and currency
* Payment description / product name
* Customer email address (if mapped from a form field)
* Form and entry identifiers as metadata (form ID, entry ID, and an internal reference token)

After payment, the plugin may retrieve the Checkout Session from Stripe to verify payment status, and site administrators may request refunds through the Stripe API. If you configure a Stripe webhook and provide the signing secret, Stripe also sends payment status events (such as completed checkout sessions, expired sessions, or refunds) to your site. Webhook requests are rejected unless they include a valid Stripe-Signature for that secret.

Card details are entered by the customer on Stripe’s hosted Checkout page and are handled by Stripe; this plugin does not receive or store raw card numbers.

**Stripe policies**

* Privacy Policy: https://stripe.com/privacy
* Terms of Service: https://stripe.com/legal/ssa

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
* Initial release.