<?php

use Vendidero\Germanized\Shipments\Shipment;

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Invoice_Packing_Slip extends WC_GZDP_Invoice {

	protected $shipment = null;

	public function __construct( $invoice ) {
		parent::__construct( $invoice );

		$this->type = 'packing_slip';
	}

	public function get_address() {

		if ( $this->shipping_address ) {
			return $this->shipping_address;
		}

		return parent::get_address();
	}

	public function get_order_number() {
		if ( $shipment = $this->get_shipment() ) {

			if ( $order = $shipment->get_order() ) {
				return $order->get_order_number();
			}

			return $shipment->get_order_id();
		}

		return $this->get_shipment_number();
	}

	public function get_shipment_number() {
		return ( $this->shipment_number ? $this->shipment_number : $this->shipment_id );
	}

	protected function get_number_placeholders() {
		$placeholders = parent::get_number_placeholders();

		$placeholders['{shipment_number}'] = $this->get_shipment_number();

		return $placeholders;
	}

	public function generate_number() {

		if ( $this->get_option( 'enable_numbering' ) === 'yes' )
			return parent::generate_number();

		global $wpdb;
		
		$number = $this->get_shipment_number();

		update_post_meta( $this->id, '_invoice_number', $number );
		update_post_meta( $this->id, '_invoice_number_formatted', $this->get_title() );
		
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_title = %s WHERE ID = %s", $this->get_title(), $this->id ) );
	}

	public function get_items() {
		if ( $shipment = $this->get_shipment() ) {
			return $shipment->get_items();
		}

		return array();
	}

	public function get_number() {

		if ( $this->get_option( 'enable_numbering' ) === 'yes' )
			return parent::get_number();
		
		if ( ! $this->number && $this->get_shipment() ) {
			$shipment = $this->get_shipment();

			return $shipment->get_shipment_number();
		}
		
		return $this->number; 
	}

	public function get_title_pdf() {

		if ( $this->get_option( 'print_number' ) === 'yes' )
			return parent::get_title_pdf();

		$type = wc_gzdp_get_invoice_types( $this->type );

		return apply_filters( 'woocommerce_gzdp_invoice_title_pdf', '<span class="invoice-desc">' . $type[ 'title' ] . '</span>', $this );
	}

	public function refresh_post_data( $data, $shipment ) {
		$data['invoice_date'] = date_i18n( 'Y-m-d H:i:s' );

		parent::refresh_post_data( $data, $shipment );
	}

	/**
	 * @return Shipment|null
	 */
	public function get_shipment() {
		if ( is_null( $this->shipment ) ) {
			$this->shipment = wc_gzd_get_shipment( $this->shipment_id );
		}

		return $this->shipment;
	}

	/**
	 * @param array $data
	 * @param Shipment $shipment
	 *
	 * @return bool|void
	 */
	public function refresh( $data = array(), $shipment = NULL ) {
		global $wpdb;

		if ( is_a( $shipment, 'WC_Order' ) ) {
			$shipments = wc_gzd_get_shipments_by_order( $shipment );

			if ( ! empty( $shipments ) ) {
				// Use first shipment as fallback
				$shipment = $shipments[0];
			} else {
				return false;
			}
		}

		$this->populate();
		$this->shipment = $shipment;

		$data = apply_filters( 'woocommerce_gzdp_invoice_refresh_data', $data, $this );

		$status = ( ! empty( $data[ 'invoice_status' ] ) ? $data[ 'invoice_status' ] : $this->get_status() );
		$this->update_status( $status );

		if ( $this->is_locked() && ! isset( $data[ 'invoice_force_rebuilt' ] ) )
			return false;

		$order = wc_get_order( $this->shipment->get_order_id() );

		do_action( 'woocommerce_gzdp_invoice_maybe_update_language', $this, $order );

		// Update Post
		$this->refresh_post_data( $data, $order );

		update_post_meta( $this->id, '_invoice_shipping_address', $this->shipment->get_formatted_address() );
		update_post_meta( $this->id, '_invoice_order', $order->get_id() );
		update_post_meta( $this->id, '_invoice_shipment_id', $this->shipment->get_id() );
		update_post_meta( $this->id, '_invoice_order_number', $order->get_order_number() );
		update_post_meta( $this->id, '_invoice_shipment_number', $this->shipment->get_shipment_number() );
		update_post_meta( $this->id, '_invoice_order_data', array( 'date' => wc_gzd_get_order_date( $order ) ) );

		// Invoice Number
		if ( $this->is_new() )
			$this->generate_number();

		do_action( 'woocommerce_gzdp_before_invoice_refresh', $this );

		$this->refresh_shipment( $shipment );
		$this->refresh_pdf();

		update_post_meta( $this->id, '_invoice_exclude', 1 );
	}

	/**
	 * @param Shipment $shipment
	 */
	protected function refresh_shipment( $shipment ) {
		$shipment->update_meta_data( '_packing_slip', $this->get_id() );
		$shipment->save();
	}

	public function filter_export_data( $data = array() ) {
		unset( $data['status'] );
		unset( $data['delivered'] );
		return $data;
	}

	public function get_submit_button_text() {
		return ( $this->is_new() ? __( 'Generate Packing Slip', 'woocommerce-germanized-pro' ) : __( 'Regenerate Packing Slip', 'woocommerce-germanized-pro' ) );
	}

	public function delete( $bypass_trash = false ) {
		if ( $shipment = $this->get_shipment() ) {

			$shipment->delete_meta_data( '_packing_slip' );
			$shipment->save();
		}

		parent::delete( $bypass_trash );
	}
}