<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

function wc_gzdp_get_invoice_tax_share( $items, $type = 'shipping' ) {
	$cart        = $items;
	$tax_shares  = array();
	$item_totals = 0;

	// Get tax classes and tax amounts
	if ( ! empty( $cart ) ) {
		foreach ( $cart as $key => $item ) {

			$class      = $item->get_tax_class();
			$line_total = is_callable( array( $item, 'get_total' ) ) ? $item->get_total() : 0;
			$line_tax   = is_callable( array( $item, 'get_total_tax' ) ) ? $item->get_total_tax() : 0;
			$taxes      = is_callable( array( $item, 'get_taxes' ) ) ? $item->get_taxes() : array();

			if ( ! isset( $taxes['total'] ) ) {
				continue;
			}

			$tax_rate   = key( $taxes['total'] );

			// Search for the first non-empty tax rate
			foreach( $taxes['total'] as $rate_id => $tax ) {
				if ( ! empty( $tax ) ) {
					$tax_rate = $rate_id;
					break;
				}
			}

			if ( function_exists( 'wc_gzd_item_is_tax_share_exempt' ) && wc_gzd_item_is_tax_share_exempt( $item, $type, $key ) ) {
				continue;
			}

			if ( ! isset( $tax_shares[ $tax_rate ] ) ) {
				$tax_shares[ $tax_rate ]          = array();
				$tax_shares[ $tax_rate ]['total'] = 0;
				$tax_shares[ $tax_rate ]['class'] = $class;
			}

			// Does not contain pricing data in case of recurring Subscriptions
			$tax_shares[ $tax_rate ]['total'] += ( $line_total + $line_tax );
			$tax_shares[ $tax_rate ]['class']  = $class;

			$item_totals += ( $line_total + $line_tax );
		}
	}

	if ( ! empty( $tax_shares ) ) {
		$default = ( $item_totals == 0 ? 1 / sizeof( $tax_shares ) : 0 );

		foreach ( $tax_shares as $key => $class ) {
			$tax_shares[ $key ]['share'] = ( $item_totals > 0 ? $class['total'] / $item_totals : $default );
		}
	}

	return $tax_shares;
}

function wc_gzdp_get_invoice_types( $type = '' ) {
    $types = apply_filters( 'woocommerce_gzdp_invoice_types', array(
		'simple' => array(
			'class_name' => 'WC_GZDP_Invoice_Simple',
			'title'	     => _x( 'Invoice', 'invoices', 'woocommerce-germanized-pro' ),
			'title_new'  => _x( 'New invoice', 'invoices', 'woocommerce-germanized-pro' ),
			'manual'     => false,
		),
		'cancellation'   => array(
			'class_name' => 'WC_GZDP_Invoice_Cancellation',
			'title'      => _x( 'Cancellation', 'invoices', 'woocommerce-germanized-pro' ),
			'title_new'  => _x( 'New cancellation', 'invoices', 'woocommerce-germanized-pro' ),
			'manual'     => true,
		),
		'packing_slip'   => array(
			'class_name' => 'WC_GZDP_Invoice_Packing_Slip',
			'title'      => _x( 'Packing Slip', 'invoices', 'woocommerce-germanized-pro' ),
			'title_new'  => _x( 'New packing slip', 'invoices', 'woocommerce-germanized-pro' ),
			'manual'     => false,
		),
	) );

    if ( empty( $type ) ) {
		return $types;
    }

    return ( isset( $types[ $type ] ) ? $types[ $type ] : $types['simple'] );
}

/**
 * @param integer $shipment_id
 *
 * @return bool|WC_GZDP_Invoice_Packing_Slip
 */
function wc_gzdp_get_packing_slip_by_shipment( $shipment_id ) {
	$packing_slip = false;

	if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
		$packing_slip_id = $shipment->get_meta( '_packing_slip', true );

		if ( $packing_slip_id ) {
			$invoice_obj = wc_gzdp_get_invoice( $packing_slip_id );

			if ( $invoice_obj ) {
				$packing_slip = $invoice_obj;
			}
		}
	}

	return $packing_slip;
}

/**
 * @param integer $packing_slip
 *
 * @return bool|WC_GZDP_Invoice_Packing_Slip
 */
function wc_gzdp_get_packing_slip( $packing_slip ) {
	$packing_slip = wc_gzdp_get_invoice( $packing_slip, 'packing_slip' );

	return $packing_slip;
}

function wc_gzdp_get_default_invoice_status() {
	return apply_filters( 'woocommerce_gzdp_default_invoice_status', get_option( 'woocommerce_gzdp_invoice_default_status', 'wc-gzdp-pending' ) );
}

function wc_gzdp_get_invoice_statuses() {
	return array( 
		'wc-gzdp-pending' => _x( 'Pending', 'invoices', 'woocommerce-germanized-pro' ), 
		'wc-gzdp-paid' => _x( 'Paid', 'invoices', 'woocommerce-germanized-pro' ),  
		'wc-gzdp-cancelled' => _x( 'Cancelled', 'invoices', 'woocommerce-germanized-pro' ), 
	);
}

function wc_gzdp_get_next_invoice_number( $type ) {

	global $wpdb;
	
	$types = wc_gzdp_get_invoice_types();
	
	if ( ! isset( $types[ $type ] ) )
		return false;
	
	if ( $type == 'cancellation' && get_option( 'woocommerce_gzdp_invoice_cancellation_numbering' ) == 'no' )
		$type = 'simple';

	do_action( 'woocommerce_gzdp_get_next_invoice_number' );

	// Clear cache
	$wpdb->flush();

	// Udpate
	$update = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET option_value=option_value+1 WHERE option_name = %s", "wc_gzdp_invoice_" . $type ) );

	// Get next
	$next = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", "wc_gzdp_invoice_" . $type ) );

	return (int) $next;
}

function wc_gzdp_get_tax_label( $rate_id ) {
	return sprintf( __( 'VAT %s', 'woocommerce-germanized-pro' ), WC_Tax::get_rate_percent( $rate_id ) );
}

function wc_gzdp_order_has_invoice_type( $order, $type = 'simple' ) {
	
	$found = false;
	$invoices = wc_gzdp_get_invoices_by_order( $order );
	
	if ( ! empty( $invoices ) ) {
		foreach ( $invoices as $invoice ) {
			if ( $invoice->is_type( $type ) )
				$found = true;
		}
	}

	return $found;
}

function wc_gzdp_order_supports_new_invoice( $order ) {

 	$invoices = wc_gzdp_get_invoices_by_order( $order, 'simple' );
 	$supports_new = true;

	if ( ! empty( $invoices ) ) {
	
		foreach ( $invoices as $invoice ) {
			
			if ( ! $invoice->is_cancelled() )
				$supports_new = false;

		}

	}

	return $supports_new;

}

/**
 * @param WC_Order $order
 * @param bool $type
 *
 * @return array
 */
function wc_gzdp_get_invoices_by_order( $order, $type = false ) {
	$return = array();

	// Make sure we have latest meta loaded
	if ( $order && method_exists( $order, 'read_meta_data' ) ) {
        $order->read_meta_data( true );
    }

	if ( $order && ( $invoices = $order->get_meta( '_invoices' ) ) ) {
		foreach ( $invoices as $invoice ) {
			$invoice_obj = wc_gzdp_get_invoice( $invoice );

			if ( ! $invoice_obj || ! is_object( $invoice_obj ) ) {
				continue;
			}

			if ( $type && ! $invoice_obj->is_type( $type ) ) {
				continue;
			}

			$return[ $invoice ] = $invoice_obj;
		}
	}

	return $return;
}

function wc_gzdp_get_order_last_invoice( $order ) {

	$invoices = wc_gzdp_get_invoices_by_order( $order, 'simple' );
	$best_match = null;

	foreach ( $invoices as $invoice ) {

		if ( ! $invoice->is_cancelled() )
			$best_match = $invoice;

	}

	if ( is_null( $best_match ) && ! empty( $invoices ) )
		$best_match = end( $invoices );

	return $best_match;

}

function wc_gzdp_is_invoice( $invoice ) {
	return $invoice instanceof WC_GZDP_Invoice;
}

function wc_gzdp_get_invoice_download_url( $invoice_id ) {
	return wc_get_endpoint_url( 'view-bill', $invoice_id, get_permalink( wc_get_page_id( 'myaccount' ) ) );
}

function wc_gzdp_get_invoice( $invoice = false, $type = 'simple' ) {
	$factory = WC_germanized_pro()->invoice_factory;

	if ( ! $factory ) {
		return false;
	}

	return $factory->get_invoice( $invoice, $type );
}

function wc_gzdp_get_invoice_frontend_types() {
	$types = get_option( 'woocommerce_gzdp_invoice_download_frontend_types' );
	return ( empty( $types ) ? false : (array) $types );
}

function wc_gzdp_get_invoice_total_refunded_amount( $invoice ) {
    $invoice_query = new WP_Query(
        array(
            'post_type' => 'invoice',
            'posts_per_page' => -1,
            'post_status' => array( 'wc-gzdp-paid' ),
            'meta_query' => array(
                array(
                    'key'     => '_invoice_parent_id',
                    'value'   => $invoice->id,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_subtype',
                    'value'   => 'refund',
                    'compare' => '=',
                )
            ),
        )
    );

    $total = 0;

    if ( $invoice_query->posts ) {
        foreach( $invoice_query->posts as $post ) {
            $cancellation = wc_gzdp_get_invoice( $post->ID );
            if ( $cancellation ) {
                $total += $cancellation->totals[ 'total' ];
            }
        }
    }

    return wc_format_decimal( $total, 2 );
}

function wc_gzdp_invoice_fully_refunded( $invoice ) {
    $refunded_amount = wc_format_decimal( wc_gzdp_get_invoice_total_refunded_amount( $invoice ) * -1, 2 );
    $total_amount    = wc_format_decimal( $invoice->totals[ 'total' ], 2 );

    $formatted = wc_format_decimal( $total_amount - $refunded_amount );

    return ( $formatted <= 0 );
}

function wc_gzdp_get_order_meta( $product, $item ) {

	if ( version_compare( WC()->version, '2.4', '<' ) )
		$item = $item['item_meta'];

	if ( ! isset( $item['item_meta'] ) ) {
	    return (object) array( 'meta' => false );
    }

	$meta = new WC_Order_Item_Meta( $item, $product );
	return $meta;
}

/**
 * @param $product
 * @param WC_Order_Item $item
 *
 * @return mixed|void
 */
function wc_gzdp_get_order_meta_print( $product, $item ) {
	$print = '';

	if ( is_a( $item, 'WC_Order_Item' ) ) {
		// Make sure wc_display_item_meta does only get called if the order is available for better compatibility.
		if ( $item->get_order() ) {
			$print = strip_tags( wc_display_item_meta( $item, array(
				'echo' => false,
				'before' => '',
				'after' => '',
				'separator' => ', ',
				'autop' => false,
			) ) );
		}
	}

	return apply_filters( 'woocommerce_gzdp_invoice_order_meta_html', $print, $product, $item );
}

function wc_gzdp_get_order_item_tax_rate( $item, $order ) {

	if ( wc_tax_enabled() ) {
		
		$order_taxes         = $order->get_taxes();
		$taxes 				 = array();

		foreach ( $order_taxes as $tax ) {
			$class                      = wc_get_tax_class_by_tax_id( $tax['rate_id'] );
			$taxes[ $class ]            = $tax;
			$taxes[ $class ]['percent'] = wc_gzd_format_tax_rate_percentage( WC_Tax::get_rate_percent( $tax['rate_id'] ), true );
		}
	}

	if ( ! empty( $item['line_tax' ] ) && isset( $item['tax_class'] ) && isset( $taxes[ $item['tax_class'] ] ) ) {
	
		return apply_filters( 'woocommerce_gzdp_invoice_item_tax_rate_html', $taxes[ $item['tax_class'] ]['percent'], $item, $order );
	
	} else if ( ! empty( $item['line_tax' ] ) ) {
		
		$_product  = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
		
		if ( $_product ) {
			
			$rates = WC_Tax::get_rates( $_product->get_tax_class() );
			
			if ( ! empty( $rates ) ) {
				$tax_rate = reset( $rates );
				return apply_filters( 'woocommerce_gzdp_invoice_item_product_tax_rate_html', wc_gzd_format_tax_rate_percentage( $tax_rate[ 'rate' ], true ) , $item, $order );
			}
		}
	}

	return apply_filters( 'woocommerce_gzdp_invoice_item_no_tax_rate_html', wc_gzd_format_tax_rate_percentage( 0, true ), $item, $order );
}

function wc_gzdp_get_invoice_unit_price_excl( $cart_item ) {
	if ( isset( $cart_item[ 'unit_price_excl' ] ) )
		return $cart_item[ 'unit_price_excl' ];
	return false;
}

function wc_gzdp_invoice_order_price( $price, $invoice ) {
	
	if ( is_numeric( $invoice ) )
		$invoice = wc_gzdp_get_invoice( $invoice );
	
	if ( $invoice->is_cancellation() )
		$price = $price * -1;
	
	return $price;
}

function wc_gzdp_get_invoice_quantity( $item ) {
	$quantity = ( isset( $item[ 'quantity' ] ) ? $item[ 'quantity' ] : $item[ 'qty' ] );
	return ( $quantity < 0 ? $quantity * -1 : $quantity );
}

function wc_gzdp_get_invoice_default_author() {
	$user = wp_get_current_user();
	$user_id = 1;

	// If no user is logged in or current logged in user is not an administrator, look for the admin as post_author
	if ( ! $user || ( $user && ! in_array( 'administrator', $user->roles ) ) ) {

		$admin_mail = get_option( 'admin_email' );

		// Get site admin email address if it is a multiste
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$admin_mail = get_site_option( 'admin_email' );
		}

		$user = get_user_by( 'email', $admin_mail );

		if ( $user ) {
			$user_id = $user->ID;
		}

	} else {
		$user_id = $user->ID;
	}

	return $user_id;
}

function wc_gzdp_get_invoice_item_total_discount( $item, $tax_display = 'incl' ) {
	$args = array(
		'subtotal',
		'subtotal_tax',
		'total',
		'total_tax'
	);

	if ( is_array( $item ) ) {
		$args['subtotal']     = $item['line_subtotal'];
		$args['subtotal_tax'] = $item['line_subtotal_tax'];
		$args['total']        = $item['line_total'];
		$args['total_tax']    = $item['line_tax'];
	} else {
		$args['subtotal']     = $item->get_subtotal();
		$args['subtotal_tax'] = $item->get_subtotal_tax();
		$args['total']        = $item->get_total();
		$args['total_tax']    = $item->get_total_tax();
	}

	if ( 'incl' === $tax_display ) {
        $args['subtotal'] = wc_format_decimal( $args['subtotal'], '' );
        $args['total'] = wc_format_decimal( $args['total'], '' );
    }

	$total_discount = $args['subtotal'];

	if ( 'incl' === $tax_display ) {
		$total_discount += $args['subtotal_tax'];
	}

	$total_discount -= $args['total'];

	if ( 'incl' === $tax_display ) {
		$total_discount -= $args['total_tax'];
	}

	return wc_format_decimal( $total_discount, '' );
}
