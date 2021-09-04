<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Document\Shortcodes;
use Vendidero\StoreaBill\Interfaces\Compatibility;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\WooCommerce\Automation;
use Vendidero\StoreaBill\WooCommerce\Helper;
use Vendidero\StoreaBill\WooCommerce\Order;

defined( 'ABSPATH' ) || exit;

class Subscriptions implements Compatibility {

	public static function is_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	public static function init() {
		/**
		 * Hide the invoice meta box from main subscription order
		 */
		add_filter( 'storeabill_woo_order_type_shop_subscription_add_invoice_meta_box', '__return_false', 10 );

		/**
		 * Sync the date of service with the invoice
		 */
		add_action( 'storeabill_woo_order_synced_invoice', array( __CLASS__, 'sync_invoice_date_of_service' ), 50, 2 );

		/**
		 * Add order related shortcodes.
		 */
		add_filter( 'storeabill_shortcode_get_document_reference_data', array( __CLASS__, 'shortcode_result' ), 10, 4 );

		/**
		 * Register editor shortcodes.
		 */
		add_filter( 'storeabill_document_template_editor_available_shortcodes', array( __CLASS__, 'register_editor_shortcodes' ), 10, 2 );

		/**
		 * On renewals
		 */
		add_filter( 'wcs_renewal_order_created', array( __CLASS__, 'maybe_trigger_auto' ), 5000, 2 );
	}

	public static function maybe_trigger_auto( $renewal_order, $subscription ) {
		/**
		 * In case the after checkout automation option has been chosen
		 * lets create invoices for renewals right after they have been created
		 *
		 * In case other timing exists, lets check whether the default order status has already been set and maybe sync immediately.
		 */
		if ( Automation::create_invoices() ) {
			if ( Automation::has_invoice_timing( 'checkout' ) ) {
				Automation::sync_invoices( $renewal_order->get_id() );
			} elseif ( Automation::has_invoice_timing( 'paid' ) || Automation::has_invoice_timing( 'status' ) || Automation::has_invoice_timing( 'status_payment_method' ) ) {
				$statuses       = wc_get_is_paid_statuses();
				$payment_method = $renewal_order->get_payment_method();

				if ( Automation::has_invoice_timing( 'status' ) ) {
					$statuses = Automation::get_invoice_order_statuses();
				} elseif ( Automation::has_invoice_timing( 'status_payment_method' ) ) {
					// Somehow the renewal order seems to miss the payment method - use subscription payment method as fallback
					$payment_method = empty( $payment_method ) ? $subscription->get_payment_method() : $payment_method;
					$statuses       = empty( $payment_method ) ? array() : Automation::get_invoice_payment_method_statuses( $payment_method );
				}

				$statuses = array_map( array( '\Vendidero\StoreaBill\WooCommerce\Helper', 'clean_order_status' ), $statuses );
				$sync     = false;

				if ( $order = Helper::get_order( $renewal_order ) ) {
					if ( in_array( $order->get_status(), $statuses ) ) {
						$sync = true;
					} elseif( empty( $statuses ) && ( Automation::has_invoice_timing( 'status' ) || Automation::has_invoice_timing( 'status_payment_method' ) ) ) {
						// If no status was selected within the status settings - sync right away
						$sync = true;
					}
				}

				if ( $sync ) {
					Automation::sync_invoices( $renewal_order->get_id() );
				}
			}
		}

		return $renewal_order;
	}

	public static function register_editor_shortcodes( $shortcodes, $document_type ) {
		if ( in_array( $document_type, array( 'invoice', 'invoice_cancellation' ) ) ) {
			$shortcodes['document'][] = array(
				'shortcode' => 'document_reference?data=subscription_numbers',
				'title'     => _x( 'Subscription order number(s)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			);
		}

		return $shortcodes;
	}

	/**
	 * @param $result
	 * @param $atts
	 * @param Order $order
	 * @param Shortcodes $shortcodes
	 */
	public static function shortcode_result( $result, $atts, $order, $shortcodes ) {
		if ( $order ) {
			if ( is_a( $order, '\Vendidero\StoreaBill\WooCommerce\Order' ) && 'subscription_numbers' === $atts['data'] ) {
				$result = array();

				if ( function_exists( 'wcs_order_contains_subscription' ) &&
				     function_exists( 'wcs_get_subscriptions_for_order' ) &&
				     wcs_order_contains_subscription( $order->get_id() )
				) {
					$subscriptions = wcs_get_subscriptions_for_order( $order->get_id() );

					if ( ! empty( $subscriptions ) ) {
						foreach( $subscriptions as $subscription ) {
							$result[] = $subscription->get_order_number();
						}
					}
				/**
				 * Check if it is a renewal
				 */
				} elseif ( function_exists( 'wcs_order_contains_renewal' ) &&
				     function_exists( 'wcs_get_subscriptions_for_renewal_order' ) &&
				     wcs_order_contains_renewal( $order->get_id() )
				) {
					$subscriptions = wcs_get_subscriptions_for_renewal_order( $order->get_id() );

					if ( ! empty( $subscriptions ) ) {
						foreach ( $subscriptions as $subscription ) {
							$result[] = $subscription->get_order_number();
						}
					}
				}
			}
		} elseif( $document = $shortcodes->get_document() ) {
			if ( 'subscription_numbers' === $atts['data'] && is_a( $document, 'Vendidero\StoreaBill\Interfaces\Previewable' )  ) {
				$result = array( '1234' );
			}
		}

		return $result;
	}

	/**
	 * @param Simple $invoice
	 * @param Order $order
	 */
	public static function sync_invoice_date_of_service( $invoice, $order ) {
		$woo_order = $order->get_order();

		if ( function_exists( 'wcs_order_contains_subscription' ) &&
		     function_exists( 'wcs_add_time' ) &&
		     function_exists( 'wcs_get_subscriptions_for_order' ) &&
		     wcs_order_contains_subscription( $woo_order, 'any' ) )
		{
			$subscriptions = wcs_get_subscriptions_for_order( $woo_order, array( 'order_type' => 'any' ) );
			$start_date    = $invoice->get_date_of_service();

			foreach( $subscriptions as $subscription ) {
				$end_date = wcs_add_time( $subscription->get_billing_interval(), $subscription->get_billing_period(), $start_date->getTimestamp() );

				if ( $end_date ) {
					$invoice->set_date_of_service_end( $end_date );
				}
			}
		}
	}
}
