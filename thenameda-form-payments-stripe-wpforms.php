<?php
/**
 * Plugin Name:       Thenameda Form Payments with Stripe for WPForms
 * Plugin URI:        https://wordpress.org/plugins/thenameda-form-payments-stripe-wpforms/
 * Description:       Collect Stripe payments with WPForms. Add per-form Stripe settings, redirect customers to Stripe Checkout on submit, record transactions, issue refunds, and export payment data to CSV. Works with WPForms Lite and Pro.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.2
 * Requires Plugins:  wpforms-lite
 * Author:            thenameda123
 * Author URI:        http://phpcodeinformation.com/
 * License:           GPL-3.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       thenameda-form-payments-stripe-wpforms
 * Domain Path:       /languages
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'THENFOPA_VERSION' ) ) {
	define( 'THENFOPA_VERSION', '1.0.0' );
}

if ( ! defined( 'THENFOPA_FILE' ) ) {
	define( 'THENFOPA_FILE', __FILE__ );
}

if ( ! defined( 'THENFOPA_DIR' ) ) {
	define( 'THENFOPA_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'THENFOPA_URL' ) ) {
	define( 'THENFOPA_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'THENFOPA_PLUGIN_BASENAME' ) ) {
	define( 'THENFOPA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'THENFOPA_META_PREFIX' ) ) {
	define( 'THENFOPA_META_PREFIX', 'thenfopa_' );
}

if ( ! defined( 'THENFOPA_PREFIX' ) ) {
	define( 'THENFOPA_PREFIX', 'thenfopa' );
}

if ( ! defined( 'THENFOPA_POST_TYPE' ) ) {
	define( 'THENFOPA_POST_TYPE', 'thenfopa_data' );
}

if ( ! defined( 'THENFOPA_ADMIN_STYLE_HANDLE' ) ) {
	define( 'THENFOPA_ADMIN_STYLE_HANDLE', 'thenfopa-admin-style' );
}

if ( ! defined( 'THENFOPA_ADMIN_SCRIPT_HANDLE' ) ) {
	define( 'THENFOPA_ADMIN_SCRIPT_HANDLE', 'thenfopa-admin' );
}

if ( ! defined( 'THENFOPA_FRONT_SCRIPT_HANDLE' ) ) {
	define( 'THENFOPA_FRONT_SCRIPT_HANDLE', 'thenfopa-front' );
}

/**
 * Activation: register CPT and flush rewrite rules.
 */
function thenfopa_activate() {
	if ( ! thenfopa_is_wpforms_active() ) {
		return;
	}

	// Ensure the post type is available for rewrite flush.
	require_once THENFOPA_DIR . '/inc/lib/class.' . THENFOPA_PREFIX . '.helpers.php';
	require_once THENFOPA_DIR . '/inc/class.' . THENFOPA_PREFIX . '.php';

	$instance = THENFOPA::instance();
	$instance->action__plugin_activation();
}
register_activation_hook( THENFOPA_FILE, 'thenfopa_activate' );

/**
 * Whether WPForms Lite or Pro is active.
 *
 * @return bool
 */
function thenfopa_is_wpforms_active() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 'wpforms-lite/wpforms.php' ) || is_plugin_active( 'wpforms/wpforms.php' );
}

/**
 * Admin notice shown when WPForms is not available.
 */
function thenfopa_missing_wpforms_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$install_url = admin_url( 'plugin-install.php?s=wpforms&tab=search&type=term' );
	$message     = sprintf(
		/* translators: %s: WPForms Lite install URL. */
		__( '<strong>Thenameda Form Payments with Stripe for WPForms</strong> requires the WPForms plugin. Please install and activate <a href="%s">WPForms Lite</a>.', 'thenameda-form-payments-stripe-wpforms' ),
		esc_url( $install_url )
	);

	echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
}

/**
 * Bootstrap the plugin once WordPress plugins are loaded.
 */
function thenfopa_bootstrap() {
	if ( ! thenfopa_is_wpforms_active() ) {
		if ( is_admin() ) {
			add_action( 'admin_notices', 'thenfopa_missing_wpforms_notice' );
		}
		return;
	}

	if ( did_action( 'wpforms_loaded' ) ) {
		thenfopa_init();
		return;
	}

	add_action( 'wpforms_loaded', 'thenfopa_init' );
}
add_action( 'plugins_loaded', 'thenfopa_bootstrap', 20 );

/**
 * Initialize plugin objects after WPForms has loaded.
 */
function thenfopa_init() {
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}

	require_once THENFOPA_DIR . '/inc/lib/class.' . THENFOPA_PREFIX . '.helpers.php';
	require_once THENFOPA_DIR . '/inc/class.' . THENFOPA_PREFIX . '.php';
	require_once THENFOPA_DIR . '/inc/lib/class.' . THENFOPA_PREFIX . '.lib.php';

	thenfopa();
	thenfopa()->lib = new THENFOPA_Lib();

	if ( is_admin() ) {
		require_once THENFOPA_DIR . '/inc/admin/class.' . THENFOPA_PREFIX . '.admin.php';
		require_once THENFOPA_DIR . '/inc/admin/class.' . THENFOPA_PREFIX . '.admin.action.php';
		require_once THENFOPA_DIR . '/inc/admin/class.' . THENFOPA_PREFIX . '.admin.filter.php';

		thenfopa()->admin         = new THENFOPA_Admin();
		thenfopa()->admin->action = new THENFOPA_Admin_Action();
		thenfopa()->admin->filter = new THENFOPA_Admin_Filter();
	} else {
		require_once THENFOPA_DIR . '/inc/front/class.' . THENFOPA_PREFIX . '.front.php';
		require_once THENFOPA_DIR . '/inc/front/class.' . THENFOPA_PREFIX . '.front.action.php';
		require_once THENFOPA_DIR . '/inc/front/class.' . THENFOPA_PREFIX . '.front.filter.php';

		thenfopa()->front         = new THENFOPA_Front();
		thenfopa()->front->action = new THENFOPA_Front_Action();
		thenfopa()->front->filter = new THENFOPA_Front_Filter();
	}
}
