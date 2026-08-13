<?php
/**
 * Uninstall routine.
 *
 * Removes stored Stripe transaction records created by the plugin.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$thenfopa_post_type = 'thenfopa_data';

$thenfopa_transactions = get_posts(
	array(
		'post_type'      => $thenfopa_post_type,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $thenfopa_transactions as $thenfopa_post_id ) {
	wp_delete_post( $thenfopa_post_id, true );
}
