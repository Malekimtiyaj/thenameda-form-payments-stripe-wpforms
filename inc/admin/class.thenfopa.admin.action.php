<?php
/**
 * Admin actions: menus, assets, transaction detail, CSV export.
 *
 * @package Thenameda_Stripe_Payment_for_WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin action handlers.
 */
class THENFOPA_Admin_Action {

	/**
	 * Number of transactions available in the free version.
	 */
	const FREE_ROW_LIMIT = 10;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'init', array( $this, 'maybe_export_csv' ), 99 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'manage_' . THENFOPA_POST_TYPE . '_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_action( 'parse_query', array( $this, 'parse_query' ) );
	}

	/**
	 * Enqueue admin assets on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		unset( $hook_suffix );

		wp_register_style( THENFOPA_ADMIN_STYLE_HANDLE, THENFOPA_URL . 'assets/css/admin.css', array(), THENFOPA_VERSION );
		wp_register_script( THENFOPA_ADMIN_SCRIPT_HANDLE, THENFOPA_URL . 'assets/js/admin.js', array( 'jquery' ), THENFOPA_VERSION, true );

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return;
		}

		$is_plugin_screen = (
			THENFOPA_POST_TYPE === $screen->post_type
			|| 'wpforms_page_wpforms-builder' === $screen->id
		);

		if ( ! $is_plugin_screen ) {
			return;
		}

		wp_enqueue_style( THENFOPA_ADMIN_STYLE_HANDLE );
		wp_enqueue_script( THENFOPA_ADMIN_SCRIPT_HANDLE );
		wp_localize_script(
			THENFOPA_ADMIN_SCRIPT_HANDLE,
			'thenfopa_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( THENFOPA_Helpers::NONCE_REFUND ),
				'confirm'  => __( 'Are you sure you want to refund this payment?', 'thenameda-form-payments-stripe-wpforms' ),
			)
		);
	}

	/**
	 * Register the transaction detail metabox.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'thenfopa-transaction-data',
			__( 'Transaction Details', 'thenameda-form-payments-stripe-wpforms' ),
			array( $this, 'render_detail_metabox' ),
			THENFOPA_POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'thenfopa-help',
			__( 'Need help?', 'thenameda-form-payments-stripe-wpforms' ),
			array( $this, 'render_help_metabox' ),
			THENFOPA_POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the transaction detail metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_detail_metabox( $post ) {
		$fields  = THENFOPA_Helpers::get_data_fields();
		$form_id = absint( get_post_meta( $post->ID, THENFOPA_META_PREFIX . 'form_id', true ) );
		$status  = (string) get_post_meta( $post->ID, THENFOPA_META_PREFIX . 'transaction_status', true );

		echo '<table class="thenfopa-box-data form-table">';

		foreach ( $fields as $key => $label ) {
			$meta_key = THENFOPA_META_PREFIX . $key;

			if ( 'form_data' === $key ) {
				$this->render_form_data_row( $post->ID, $label );
				continue;
			}

			if ( 'transaction_response' === $key ) {
				$response = get_post_meta( $post->ID, $meta_key, true );
				if ( '' === $response ) {
					continue;
				}
				echo '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td><code class="thenfopa-code">' . esc_html( $response ) . '</code></td></tr>';
				continue;
			}

			if ( 'refund_payment' === $key ) {
				$this->render_refund_row( $post->ID, $label, $status );
				continue;
			}

			if ( 'form_id' === $key ) {
				$value = $form_id ? get_the_title( $form_id ) : '';
				if ( '' === $value ) {
					continue;
				}
				echo '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
				continue;
			}

			if ( 'transaction_status' === $key ) {
				echo '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $this->format_status( $status ) ) . '</td></tr>';
				continue;
			}

			$value = get_post_meta( $post->ID, $meta_key, true );

			if ( '' === $value ) {
				continue;
			}

			if ( in_array( $key, array( 'amount', 'total' ), true ) ) {
				$currency = strtoupper( (string) get_post_meta( $post->ID, THENFOPA_META_PREFIX . 'currency', true ) );
				$value    = $value . ' ' . $currency;
			}

			echo '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}

		echo '</table>';
	}

	/**
	 * Render the stored form-data row.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $label   Row label.
	 */
	private function render_form_data_row( $post_id, $label ) {
		$data = get_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_data', true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		echo '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td><table class="thenfopa-subtable"><tbody>';

		foreach ( $data as $field_label => $field_value ) {
			echo '<tr><th scope="row">' . esc_html( $field_label ) . '</th><td>' . esc_html( is_array( $field_value ) ? implode( ', ', $field_value ) : (string) $field_value ) . '</td></tr>';
		}

		echo '</tbody></table></td></tr>';
	}

	/**
	 * Render the refund action row.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $label   Row label.
	 * @param string $status  Transaction status.
	 */
	private function render_refund_row( $post_id, $label, $status ) {
		$payment_intent_id = (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_id', true );
		$status            = strtolower( $status );

		if ( '' === $payment_intent_id || in_array( $status, array( 'pending', 'cancelled', 'failed', 'expired' ), true ) ) {
			return;
		}

		echo '<tr class="form-field"><th scope="row">' . esc_html( $label ) . '</th><td>';

		if ( 'refunded' === $status ) {
			echo '<span class="thenfopa-refunded">' . esc_html__( 'Already refunded', 'thenameda-form-payments-stripe-wpforms' ) . '</span>';
		} else {
			echo '<button type="button" class="button button-primary thenfopa-refund-payment" data-post-id="' . esc_attr( $post_id ) . '">' . esc_html__( 'Refund Payment', 'thenameda-form-payments-stripe-wpforms' ) . '</button>';
			echo '<span class="thenfopa-refund-status"></span>';
		}

		echo '</td></tr>';
	}

	/**
	 * Render the help metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_help_metabox( $post ) {
		$form_id = absint( get_post_meta( $post->ID, THENFOPA_META_PREFIX . 'form_id', true ) );

		echo '<div class="thenfopa-help">';
		echo '<p>' . esc_html__( 'Configure Stripe per form under Settings → Stripe in the WPForms form builder.', 'thenameda-form-payments-stripe-wpforms' ) . '</p>';

		if ( $form_id ) {
			echo '<p><strong>' . esc_html__( 'Webhook URL:', 'thenameda-form-payments-stripe-wpforms' ) . '</strong><br><code style="word-break:break-all;">' . esc_html( THENFOPA_Helpers::get_webhook_url( $form_id ) ) . '</code></p>';
		}

		echo '<ul>';
		echo '<li><a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Get Stripe API keys', 'thenameda-form-payments-stripe-wpforms' ) . '</a></li>';
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Render list-table columns.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'thenfopa_form':
				$form_id = absint( get_post_meta( $post_id, THENFOPA_META_PREFIX . 'form_id', true ) );
				echo esc_html( $form_id ? get_the_title( $form_id ) : '' );
				break;

			case 'thenfopa_payer':
				echo esc_html( (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'payer_email', true ) );
				break;

			case 'thenfopa_total':
				$total    = get_post_meta( $post_id, THENFOPA_META_PREFIX . 'total', true );
				$currency = strtoupper( (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'currency', true ) );
				echo esc_html( ( '' !== $total ? $total : '' ) . ' ' . $currency );
				break;

			case 'thenfopa_status':
				echo esc_html( $this->format_status( (string) get_post_meta( $post_id, THENFOPA_META_PREFIX . 'transaction_status', true ) ) );
				break;
		}
	}

	/**
	 * Human-readable transaction status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private function format_status( $status ) {
		$status = trim( (string) $status );

		if ( '' === $status ) {
			return '';
		}

		if ( in_array( strtolower( $status ), array( 'succeeded', 'paid', 'complete' ), true ) ) {
			return __( 'Succeeded', 'thenameda-form-payments-stripe-wpforms' );
		}

		return ucfirst( strtolower( $status ) );
	}

	/**
	 * Filter dropdown and export button on the list screen.
	 *
	 * @param string $post_type Current post type.
	 */
	public function restrict_manage_posts( $post_type ) {
		if ( THENFOPA_POST_TYPE !== $post_type || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$forms = get_posts(
			array(
				'post_type'      => 'wpforms',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$selected = $this->get_filter_form_id();

		wp_nonce_field( 'thenfopa_filter_posts', 'thenfopa_filter_nonce' );
		wp_nonce_field( 'thenfopa_export_csv', 'thenfopa_export_nonce' );

		echo '<label class="screen-reader-text" for="thenfopa-form-id">' . esc_html__( 'Filter by form', 'thenameda-form-payments-stripe-wpforms' ) . '</label>';
		echo '<select name="thenfopa_form_id" id="thenfopa-form-id">';
		echo '<option value="all">' . esc_html__( 'Select Form', 'thenameda-form-payments-stripe-wpforms' ) . '</option>';

		foreach ( $forms as $form ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $form->ID ),
				selected( $selected, (string) $form->ID, false ),
				esc_html( $form->post_title )
			);
		}

		echo '</select>';
		echo '<button type="submit" name="thenfopa_export_csv" value="1" class="button">' . esc_html__( 'Export CSV', 'thenameda-form-payments-stripe-wpforms' ) . '</button>';
	}

	/**
	 * Filter the list query by selected form.
	 *
	 * @param WP_Query $query Query.
	 */
	public function parse_query( $query ) {
		if ( ! is_admin() || THENFOPA_POST_TYPE !== $query->get( 'post_type' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$form_id = $this->get_filter_form_id();

		if ( '' === $form_id || 'all' === $form_id ) {
			return;
		}

		$query->set( 'meta_key', THENFOPA_META_PREFIX . 'form_id' );
		$query->set( 'meta_value', absint( $form_id ) );
	}

	/**
	 * Validated form filter value.
	 *
	 * @return string
	 */
	private function get_filter_form_id() {
		if ( ! isset( $_GET['thenfopa_form_id'], $_GET['thenfopa_filter_nonce'] ) || '' === $_GET['thenfopa_form_id'] ) {
			return '';
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['thenfopa_filter_nonce'] ) ), 'thenfopa_filter_posts' ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_GET['thenfopa_form_id'] ) );
	}

	/**
	 * CSV export handler (limited to the most recent records in the free version).
	 */
	public function maybe_export_csv() {
		if ( ! isset( $_REQUEST['thenfopa_export_csv'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if (
			! isset( $_REQUEST['thenfopa_export_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['thenfopa_export_nonce'] ) ), 'thenfopa_export_csv' )
		) {
			return;
		}

		$form_id = isset( $_REQUEST['thenfopa_form_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['thenfopa_form_id'] ) ) : '';

		if ( '' === $form_id || 'all' === $form_id ) {
			add_action( 'admin_notices', array( $this, 'notice_select_form' ) );
			return;
		}

		$entries = get_posts(
			array(
				'post_type'      => THENFOPA_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => self::FREE_ROW_LIMIT,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => THENFOPA_META_PREFIX . 'form_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => absint( $form_id ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( empty( $entries ) ) {
			add_action( 'admin_notices', array( $this, 'notice_no_data' ) );
			return;
		}

		$headers = array(
			'form'           => __( 'Form', 'thenameda-form-payments-stripe-wpforms' ),
			'transaction_id' => __( 'Transaction ID', 'thenameda-form-payments-stripe-wpforms' ),
			'session_id'     => __( 'Session ID', 'thenameda-form-payments-stripe-wpforms' ),
			'payer_email'    => __( 'Customer Email', 'thenameda-form-payments-stripe-wpforms' ),
			'total'          => __( 'Total', 'thenameda-form-payments-stripe-wpforms' ),
			'currency'       => __( 'Currency', 'thenameda-form-payments-stripe-wpforms' ),
			'status'         => __( 'Status', 'thenameda-form-payments-stripe-wpforms' ),
			'request_ip'     => __( 'Request IP', 'thenameda-form-payments-stripe-wpforms' ),
			'date'           => __( 'Date', 'thenameda-form-payments-stripe-wpforms' ),
		);

		$csv  = chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF );
		$csv .= $this->format_csv_row( array_values( $headers ) );

		foreach ( $entries as $entry ) {
			$row = array(
				get_the_title( absint( get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'form_id', true ) ) ),
				get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'transaction_id', true ),
				get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'session_id', true ),
				get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'payer_email', true ),
				get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'total', true ),
				get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'currency', true ),
				$this->format_status( (string) get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'transaction_status', true ) ),
				get_post_meta( $entry->ID, THENFOPA_META_PREFIX . 'request_ip', true ),
				get_the_date( 'd, M Y H:i:s', $entry->ID ),
			);

			$csv .= $this->format_csv_row( $row );
		}

		$filename = 'thenfopa-' . $form_id . '-' . time() . '.csv';

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_check_invalid_utf8( $csv, true );
		exit;
	}

	/**
	 * Format one CSV row.
	 *
	 * @param array $fields Values.
	 * @return string
	 */
	private function format_csv_row( $fields ) {
		$escaped = array();

		foreach ( $fields as $field ) {
			$field = (string) $field;

			if ( preg_match( '/[",\r\n]/', $field ) ) {
				$field = '"' . str_replace( '"', '""', $field ) . '"';
			}

			$escaped[] = $field;
		}

		return implode( ',', $escaped ) . "\r\n";
	}

	/**
	 * "Select a form" export notice.
	 */
	public function notice_select_form() {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please select a form to export.', 'thenameda-form-payments-stripe-wpforms' ) . '</p></div>';
	}

	/**
	 * "No data" export notice.
	 */
	public function notice_no_data() {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No transactions found for the selected form.', 'thenameda-form-payments-stripe-wpforms' ) . '</p></div>';
	}
}

