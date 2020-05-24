<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce WC_AJAX
 *
 * AJAX Event Handler
 *
 * @class 		WC_AJAX
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_GZDP_AJAX {

	public static function init() {

		$ajax_events = array(
			'checkout_validate_vat' => true,
			'confirm_order'         => false,
			'refresh_packing_slip'  => false,
			'create_packing_slip'   => false,
			'remove_packing_slip'   => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function create_packing_slip() {
		check_ajax_referer( 'wc-gzdp-create-packing-slip', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_GET['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$shipment_id = absint( $_GET['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_die( -1 );
		}

		if ( $packing_slip = self::maybe_create_packing_slip( $shipment ) ) {
			$response = array(
				'success'      => true,
				'packing_slip' => $packing_slip->get_id(),
				'fragments'    => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment, $packing_slip ),
				),
			);

			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-shipments' ) );
			exit;
		}
	}

	protected static function maybe_create_packing_slip( $shipment ) {
		try {
			$packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment );

			if ( empty( $packing_slip ) ) {

				// Generate packing slips
				$args         = apply_filters( 'woocommerce_gzdp_packing_slips_defaults', array( 'invoice_status' => 'wc-gzdp-pending' ) );
				$packing_slip = wc_gzdp_get_invoice( false, 'packing_slip' );

				$packing_slip->refresh( $args, $shipment );
			} else {
				// Generate packing slips
				$args         = apply_filters( 'woocommerce_gzdp_packing_slips_defaults', array( 'invoice_status' => 'wc-gzdp-pending' ) );

				$packing_slip->refresh( $args, $shipment );
			}

			return $packing_slip;

		} catch( Exception $e ) {}

		return false;
	}

	public static function refresh_packing_slip() {
		check_ajax_referer( 'wc-gzdp-refresh-packing-slip', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the packing slip.', 'woocommerce-germanized-pro' )
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( $packing_slip = self::maybe_create_packing_slip( $shipment ) ) {
			$response = array(
				'success'      => true,
				'packing_slip' => $packing_slip->get_id(),
				'fragments'    => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment, $packing_slip ),
				),
			);

			wp_send_json( $response );
		}

		wp_send_json( $response_error );
	}

	public static function remove_packing_slip() {
		check_ajax_referer( 'wc-gzdp-remove-packing-slip', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['packing_slip'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the packing slip.', 'woocommerce-germanized-pro' )
			),
		);

		$packing_slip_id = absint( $_POST['packing_slip'] );

		if ( ! $packing_slip = wc_gzdp_get_packing_slip( $packing_slip_id ) ) {
			wp_send_json( $response_error );
		}

		try {
			$shipment    = $packing_slip->get_shipment();
			$shipment_id = $shipment->get_id();

			$packing_slip->delete( true );

			$response = array(
				'success'      => true,
				'fragments'    => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment ),
				),
			);

			wp_send_json( $response );
		} catch( Exception $e ) {}

		wp_send_json( $response_error );
	}

	protected static function refresh_packing_slip_html( $p_shipment, $p_packing_slip = false ) {
		$shipment = $p_shipment;

		if ( $p_packing_slip ) {
			$packing_slip = $p_packing_slip;
		}

		ob_start();
		include_once( WC_Germanized_pro()->plugin_path() . '/includes/admin/meta-boxes/views/html-shipment-packing-slip.php' );
		$html = ob_get_clean();

		return $html;
	}

	public static function confirm_order() {

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'woocommerce-gzdp-confirm-order' ) ) {
			$order_id = absint( $_GET['order_id'] );

			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				WC_germanized_pro()->contract_helper->confirm_order( $order->get_id() );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		die();
	}

	public static function checkout_validate_vat() {

		check_ajax_referer( 'update-order-review', 'security' );

		if ( ! isset( $_POST[ 'vat_id' ] ) || ! isset( $_POST[ 'country' ] ) )
			die();

		$country = sanitize_text_field( $_POST[ 'country' ] );
		
		$vat_id = trim( preg_replace("/[^a-z0-9.]+/i", "", sanitize_text_field( $_POST[ 'vat_id' ] ) ) );
		// Strip away country code
		if ( substr( $vat_id, 0, 2 ) == $country )
			$vat_id = substr( $vat_id, 2 );

		if ( WC_GZDP_VAT_Helper::instance()->validate( $country, $vat_id ) ) {
			// Add price vat filters..
			add_filter( 'woocommerce_cart_get_taxes', array( __CLASS__, "remove_taxes" ), 0, 2 );
			echo json_encode( array( 'valid' => true, 'vat_id' => $country . '-' . $vat_id ) );
		} else {
			wc_add_notice( __( 'VAT ID seems to be invalid.', 'woocommerce-germanized-pro' ), 'error' );
			ob_start();
			wc_print_notices();
			$messages = ob_get_clean();
			echo json_encode( array( 'valid' => false, 'error' => $messages ) );
		}

		die();

	}

	public static function remove_taxes( $taxes, $cart ) {
		return array();
	}

}

WC_GZDP_AJAX::init();
