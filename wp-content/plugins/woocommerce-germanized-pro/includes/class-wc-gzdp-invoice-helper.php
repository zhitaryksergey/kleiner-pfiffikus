<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Invoice_Helper {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}
	
	public function __construct() {
		
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_invoices' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_germanized_email_template_name', array( $this, 'invoice_email_template_name' ), 0, 2 );
		
		// Stop automation for free orders
		if ( get_option( 'woocommerce_gzdp_invoice_auto_except_free' ) == 'yes' ) {
		
			add_filter( 'woocommerce_gzdp_generate_invoice', array( $this, 'stop_automation_for_free_orders' ), 0, 2 );
			add_filter( 'woocommerce_gzdp_generate_cancellation', array( $this, 'stop_automation_for_free_orders' ), 0, 2 );
		}

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'set_order_meta_crud' ), 0, 4 );

		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'set_order_meta_hidden' ), 10 );
		add_filter( 'woocommerce_gzdp_template_name', array( $this, 'set_table_vat_filter' ), 10, 1 );

		// Differential Taxation support
		add_filter( 'woocommerce_gzdp_invoice_item_name', array( $this, 'set_differential_taxation_mark' ), 10, 2 );
		add_action( 'woocommerce_gzdp_invoice_after_item_table', array( $this, 'set_differential_taxation_notice' ), 10, 3 );

		// Cancellation negative item amounts
		add_filter( 'woocommerce_gzdp_invoice_item_subtotal', array( $this, 'set_cancellation_negative_amount' ), 10, 2 );
		add_filter( 'woocommerce_gzdp_invoice_line_subtotal', array( $this, 'set_cancellation_negative_amount' ), 10, 2 );

		// Dont copy invoice meta to renewal subscriptions (generate new invoice for every renewal)
		add_filter( 'wcs_renewal_order_meta', array( $this, 'unset_subscription_invoice_meta' ), 0 );

		add_action( 'woocommerce_gzdp_pdf_attachment_updated', array( $this, 'set_invoice_attachment_private' ), 0, 2 );
		add_action( 'woocommerce_gzdp_before_pdf_static_content', array( $this, 'set_invoice_static_text_order' ), 0, 1 );

		// Remove net total from invoices for small businesses
		add_filter( 'woocommerce_gzdp_invoice_totals', array( $this, 'remove_net_price_for_small_businesses' ), 10, 3 );

		// Do not let other plugins delete invoice attachments
		add_action( 'delete_attachment', array( $this, 'pre_attachment_delete_check' ) );
		add_action( 'wp_trash_post', array( $this, 'pre_attachment_delete_check' ) );

		add_action( 'woocommerce_gzdp_generate_pdf', array( $this, 'remove_non_printable_chars' ), 10, 1 );
		add_action( 'init', array( $this, 'set_automation' ), 15 );

		// Remove packing slip if shipment is deleted
		add_action( 'woocommerce_gzd_shipment_deleted', array( $this, 'deleted_shipment' ), 10, 2 );

		if ( is_admin() )  {
			$this->admin_hooks();
        }
	}

	public function deleted_shipment( $shipment_id, $shipment ) {
		if ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment_id ) ) {
			$packing_slip->delete( true );
		}
	}

	public function recalculate_order_net_totals( $order, $before_discount = false ) {

        $subtotals       = array();
		$tax_share_rates = wc_gzdp_get_invoice_tax_share( $order->get_items( array( 'line_item' ) ) );

        /**
         * Instantiate amounts per tax rate
         */
        foreach( $order->get_tax_totals() as $tax_total ) {
            $subtotals[ $tax_total->rate_id ] = 0;
        }

        /**
         * Line items
         */
        foreach( $order->get_items( array( 'line_item' ) ) as $item ) {
            $taxes = $item->get_taxes();
            
            foreach( $taxes['total'] as $tax_rate_id => $amount ) {
                if ( ! empty( $amount ) && isset( $subtotals[ $tax_rate_id ] ) ) {
                	$item_total = ( $before_discount && is_callable( array( $item, 'get_subtotal' ) ) ) ? $item->get_subtotal() : $item->get_total();

                    // Make sure we are losing some precision here to mock the net total amount
                    $subtotals[ $tax_rate_id ] += wc_format_decimal( $item_total, 2 );
                }
            }
        }

        /**
         * Shipping + Fee items - calculate tax shares
         */
        $items = array_merge( $order->get_items( array( 'shipping' ) ), $order->get_items( array( 'fee' ) ) );

        foreach( $items as $item ) {
            $taxes       = $item->get_taxes();
            $item_total  = $item->get_total();
            $total_gross = $item_total + $item->get_total_tax();

            // Do only calculate share if more than one tax rate is included
            $enable_share = sizeof( $taxes['total'] ) > 1 ? true : false;

            foreach( $taxes['total'] as $tax_rate_id => $amount ) {

                if ( ! empty( $amount ) && isset( $subtotals[ $tax_rate_id ] ) ) {
	                $tax_share   = $tax_share_rates[ $tax_rate_id ]['share'];
	                $percentage  = WC_Tax::get_rate_percent_value( $tax_rate_id );

	                if ( ! $percentage || empty( $percentage ) ) {
	                	continue;
	                }

	                $total_share = $enable_share ? ( $total_gross * $tax_share ) : $total_gross;
					$net_amount  = $total_share / ( ( $percentage / 100 ) + 1 );

                    $subtotals[ $tax_rate_id ] += wc_round_tax_total( $net_amount );
                }
            }
        }
        
        $totals = array();
        
        foreach( $subtotals as $tax_rate_id => $total ) {
            $rate     = WC_Tax::_get_tax_rate( $tax_rate_id );
            $percent  = WC_Tax::get_rate_percent( $tax_rate_id );
            
            $totals[ $tax_rate_id ] = array(
                'rate_id'      => $tax_rate_id,
                'rate_percent' => $percent,
                'total'        => wc_round_tax_total( $total ),
                'raw'          => $total,
            );
        }

        do_action( 'woocommerce_germanized_recalculated_order_net_totals', $totals, $order );

        return $totals;
    }

	/**
	 * Needed for Woo >= 3.3 to ensure left to right chars are not printed after currency symbols.
	 * @param $document
	 */
	public function remove_non_printable_chars( $document ) {
		add_filter( 'woocommerce_price_format', array( $this, 'remove_right_to_left_char' ), 10, 2 );
	}

	public function remove_right_to_left_char( $format, $currency_pos ) {
		return str_replace( array( '&#x200f;', '&#x200e;' ), array( '', '' ), $format );
	}

	public function set_cancellation_negative_amount( $amount, $invoice ) {
		if ( $invoice->type === 'cancellation' ) {
			if ( $amount > 0 ) {
				$amount = $amount * -1;
			}
		}
		return $amount;
	}

	public function set_differential_taxation_notice( $invoice ) {

		if ( in_array( $invoice->type, array( 'simple', 'cancellation' ) ) && $invoice->get_option( 'show_differential_taxation_notice' ) ) {

			$order                    = wc_get_order( $invoice->order );
			$is_differential_taxation = false;

			if ( $order ) {
				foreach ( $invoice->items as $item_id => $item ) {
					$_product    = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );

					if ( $_product ) {
						$gzd_product = wc_gzd_get_gzd_product( $_product );

						if ( is_callable( array( $gzd_product, 'is_differential_taxed' ) ) ) {
							if ( $gzd_product->is_differential_taxed() ) {
								$is_differential_taxation = true;
								break;
							}
						}
					}
				}
			}

			if ( $is_differential_taxation ) {

				$notice = apply_filters( 'woocommerce_gzdp_differential_taxation_notice_text_invoice', '** ' . $invoice->get_option( 'differential_taxation_notice_text' ) );
				echo wpautop( '<br/><div class="gzd-differential-taxation-notice-invoice">' . $notice . '</div>' );
			}
		}
	}

	public function set_differential_taxation_mark( $name, $item ) {
		global $invoice;

		if ( in_array( $invoice->type, array( 'simple', 'cancellation' ) ) && $invoice->get_option( 'show_differential_taxation_notice' ) ) {
			$order       = wc_get_order( $invoice->order );
			$_product    = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );

			if ( $_product ) {
				$gzd_product = wc_gzd_get_gzd_product( $_product );

				if ( is_callable( array( $gzd_product, 'is_differential_taxed' ) ) ) {
					if ( $gzd_product->is_differential_taxed() ) {
						$name .= ' **';
					}
				}
			}
		}

		return $name;
	}

	public function pre_attachment_delete_check( $post_id ) {
		$post = get_post( $post_id );

		// Do only check further if it is a PDF file
		if ( 'attachment' === $post->post_type && 'application/pdf' === $post->post_mime_type ) {

			if ( $post->post_parent && ! empty( $post->post_parent ) ) {

				$parent = get_post( $post->post_parent );

				if ( 'invoice' === $parent->post_type ) {
					if ( ! get_post_meta( $parent->ID, '_deletion_allowed', true  ) ) {
						wp_die( __( 'This invoice attachment should not be deleted because it belongs to an active invoice.', 'woocommerce-germanized-pro' ) );
					}
				}
			}
		}
	}

	public function maybe_redirect_after_refund( $order_id, $refund_id ) {
	    if ( is_ajax() && get_post_meta( $order_id, '_refund_invoice_created', true ) ) {
	        delete_post_meta( $order_id, '_refund_invoice_created' );

	        // Clear transients
            wc_delete_shop_order_transients( $order_id );
            // Need to set status to fully_refunded to enable redirect
            wp_send_json_success( array( 'status' => 'fully_refunded' ) );
        }
    }

    public function create_full_partial_cancellation( $invoice ) {

        if ( $order = wc_get_order( $invoice->order ) ) {

            $remaining_refund_amount = $order->get_remaining_refund_amount();
            $remaining_refund_items  = $order->get_remaining_refund_items();

            if ( $remaining_refund_amount <= 0 ) {
                return false;
            }

            $refund_item_count       = 0;
            $refund                  = new WC_Order_Refund();

            $refund->set_currency( $order->get_currency() );
            $refund->set_parent_id( $order->get_id() );
            $refund->set_refunded_by( get_current_user_id() ? get_current_user_id() : 1 );

            $items = $order->get_items( array( 'line_item', 'fee', 'shipping' ) );

            foreach ( $items as $item_id => $item ) {

                $refunded_qty = $order->get_qty_refunded_for_item( $item_id, $item->get_type() );
	            $refund_total = $item->get_total() - $order->get_total_refunded_for_item( $item_id, $item->get_type() );
	            $qty          = $item->get_quantity() + ( $refunded_qty > 0 ? $refunded_qty * -1 : $refunded_qty );

	            // If someone has already created a refund for the item (but not fully) use 1 as quantity fallback.
	            if ( $qty <= 0 ) {
		            $qty = 1;
	            }

                $refund_tax   = array();
                $item_taxes   = $item->get_taxes();

                if ( ! empty( $item_taxes ) && isset( $item_taxes['total'] ) ) {
                    foreach( $item_taxes['total'] as $key => $tax ) {
                        $tax_amount_refunded = $order->get_tax_refunded_for_item( $item_id, $key, $item->get_type() );
                        $tax_amount          = $item_taxes['total'][ $key ];

                        if ( $tax_amount > 0 ) {
                            $tax_amount_refundable = $tax_amount - $tax_amount_refunded;
                            $refund_tax[ $key ]    = $tax_amount_refundable;
                        }
                    }
                }

                $class         = get_class( $item );
                $refunded_item = new $class( $item );
                $refunded_item->set_id( 0 );
                $refunded_item->set_total( wc_format_refund_total( $refund_total ) );
                $refunded_item->set_taxes(
                    array(
                        'total'    => array_map( 'wc_format_refund_total', $refund_tax ),
                        'subtotal' => array_map( 'wc_format_refund_total', $refund_tax ),
                    )
                );

                if ( is_callable( array( $refunded_item, 'set_subtotal' ) ) ) {
                    $refunded_item->set_subtotal( wc_format_refund_total( $refund_total ) );
                }

                if ( is_callable( array( $refunded_item, 'set_quantity' ) ) ) {
                    $refunded_item->set_quantity( $qty * -1 );
                }

                $refund->add_item( $refunded_item );
                $refund_item_count += $qty;
            }

            $refund->update_taxes();
            $refund->calculate_totals( false );
            $refund->set_total( $remaining_refund_amount * -1 );

            if ( isset( $refund ) && is_a( $refund, 'WC_Order_Refund' ) ) {
                $this->create_partial_cancellation( $refund, $order, array() );
                $invoice->update_status( 'wc-gzdp-cancelled' );

                // Delete the refund because it should not be persited
                wp_delete_post( $refund->get_id(), true );

                return true;
            }
        }

        return false;
    }

    protected function create_partial_cancellation( $refund, $order, $args ) {
        if ( $invoice = wc_gzdp_get_order_last_invoice( $order ) ) {
            // Remove filters
            WC_germanized()->emails->remove_order_email_filters();

            // Generate new cancellation
            $cancellation = wc_gzdp_get_invoice( false, 'cancellation-refund' );
            $cancellation->refresh( array( 'invoice_parent' => $invoice->id ), $refund );

            // Lock parent invoice
            update_post_meta( $invoice->id, '_invoice_locked', 'yes' );

            // Maybe enable redirect
            update_post_meta( $order->get_id(), '_refund_invoice_created', 'yes' );

            if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_cancellation_auto_email' ) ) {
                $cancellation->send_to_customer();
            }

            // Set invoice to cancelled if total refunded amount equals invoice amount.
            if ( wc_gzdp_invoice_fully_refunded( $invoice ) ) {
                $invoice->update_status( 'wc-gzdp-cancelled' );
            }
        }
    }

	public function create_refund( $refund_id, $args ) {
	    // Do only create refunds for line items
	    if ( $args['refund_id'] > 0 || sizeof( $args[ 'line_items' ] ) < 1 )
	        return;

	    $refund_order = wc_get_order( $refund_id );

	    if ( ! $refund_order )
	        return;

	    $p = get_post( $refund_order->get_id() );

	    if ( ! $p )
	        return;

	    $order   = wc_get_order( $p->post_parent );
	    $invoice = wc_gzdp_get_order_last_invoice( $order );

	    // Do not generate new refunds if the invoice has been already cancelled
	    if ( ! $invoice || $invoice->is_cancelled() ) {
	        return;
        }

		$this->create_partial_cancellation( $refund_order, $order, $args );
    }

	public function admin_hooks() {
		add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'resend_order_emails' ), 0 );
		add_action( 'init', array( $this, 'register_meta_boxes' ), 15 );
		add_action( 'admin_init', array( $this, 'download_invoice' ), 0 );

		add_action( 'woocommerce_settings_invoice_general_options', array( $this, 'preview_button' ) );
		
		// Export
		add_action( 'export_filters', array( $this, 'invoice_export_filters' ) );
		add_action( 'export_wp', array( $this, 'export_invoices' ), 0, 1 );
		add_filter( 'export_args', array( $this, 'export_args' ), 0, 1 );
		
		// Admin order table download actions
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'order_actions' ), 0, 2 );
		
		// Add layout settings
		add_filter( 'woocommerce_gzdp_invoice_layout_settings_general', array( $this, 'get_settings_general' ) );
		add_filter( 'woocommerce_gzdp_invoice_layout_settings_margins', array( $this, 'get_settings_margin' ) );
		add_filter( 'woocommerce_gzdp_invoice_layout_settings_colors', array( $this, 'get_settings_color' ) );
		add_filter( 'woocommerce_gzdp_invoice_layout_settings_static_texts', array( $this, 'get_settings_static_text' ) );

		// Invoice Search
		add_action( 'parse_query', array( $this, 'shop_order_search_custom_fields' ), 15 );

		// Bulk Download Options
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_print_handler' ), 10, 3 );

		// Packing Slips + Shipments
		add_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_right_column', array( $this, 'meta_box_packing_slip' ), 20, 1 );
		add_filter( 'woocommerce_gzd_shipments_table_actions', array( $this, 'shipment_table_packing_slip_download' ), 10, 2 );

		add_filter( 'woocommerce_gzd_shipments_table_bulk_actions', array( $this, 'shipment_table_bulk_actions' ), 10, 1 );

		// Bulk Packing Slips
		add_filter( 'woocommerce_gzd_shipments_table_bulk_action_handlers', array( $this, 'register_packing_slip_bulk_handler' ) );
	}

	public function register_packing_slip_bulk_handler( $handlers ) {
		$handlers['packing_slips'] = 'WC_GZDP_Admin_Packing_Slip_Bulk_Handler';

		return $handlers;
	}

	public function shipment_table_bulk_actions( $actions ) {
		$actions['packing_slips'] = __( 'Generate and download packing slips', 'woocommerce-germanized-pro' );

		return $actions;
	}

	public function shipment_table_packing_slip_download( $actions, $shipment ) {
		if ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment ) ) {
			$actions['download_packing_slip'] = array(
				'url'    => $packing_slip->get_pdf_url(),
				'name'   => sprintf( _x( 'Download %s', 'invoices', 'woocommerce-germanized-pro' ), $packing_slip->get_title() ),
				'action' => 'download-packing-slip download',
				'target' => '_blank'
			);
		} else {
			$actions['generate_packing_slip'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzdp_create_packing_slip&shipment_id=' . $shipment->get_id() ), 'wc-gzdp-create-packing-slip' ),
				'name'   => __( 'Generate Packing Slip', 'woocommerce-germanized-dhl' ),
				'action' => 'generate-packing-slip generate',
			);
		}

		return $actions;
	}

	public function meta_box_packing_slip( $the_shipment ) {
		$shipment     = $the_shipment;
		$packing_slip = wc_gzdp_get_packing_slip_by_shipment( $the_shipment );

		include WC_Germanized_pro()->plugin_path() . '/includes/admin/meta-boxes/views/html-shipment-packing-slip.php';
	}

	public function check_number_reset( $settings ) {
		$numbers = array( 'wc_gzdp_invoice_simple', 'wc_gzdp_invoice_cancellation', 'wc_gzdp_invoice_packing_slip' );

		foreach( $numbers as $number ) {
			if ( isset( $_POST[ $number ] ) ) {
				if ( ! isset( $_POST[ $number . '_lock' ] ) ) {
					unset( $_POST[ $number ] );
				}
			}
		}
	}

	public function remove_net_price_for_small_businesses( $totals_ordered, $totals, $invoice ) {

	    if ( 'yes' === get_option( 'woocommerce_gzd_small_enterprise' ) ) {
	        foreach( $totals_ordered as $key => $total ) {
	            if ( 'net_price' === $total[ 'key' ] )
	                unset( $totals_ordered[ $key ] );
            }
            return array_values( $totals_ordered );
        }

	    return $totals_ordered;
    }

	public function bulk_actions( $actions ) {

		$print_actions = array();

		foreach( wc_gzdp_get_invoice_types() as $key => $type ) {
		    if ( 'packing_slip' === $key ) {
		        continue;
            }

			$print_actions[ 'print_invoice_' . $key ] = sprintf( __( 'Print %s', 'woocommerce-germanized-pro' ), $type[ 'title' ] );
		}

		return array_merge( $actions, $print_actions );
	}

	public function bulk_print_handler( $redirect_to, $doaction, $post_ids ) {

		$print_actions = array();

		foreach( wc_gzdp_get_invoice_types() as $key => $type ) {
			if ( 'packing_slip' === $key ) {
				continue;
			}

			array_push( $print_actions, 'print_invoice_' . $key );
		}

		if ( ! in_array( $doaction, $print_actions ) || ! current_user_can( 'manage_woocommerce' ) )
			return $redirect_to;

		require_once( WC_germanized_pro()->plugin_path() . '/includes/class-wc-gzdp-pdf-merger.php' );

		$invoice_type = str_replace( 'print_invoice_', '', $doaction );
		$invoices = array();
		$pdf_merger = new WC_GZDP_PDF_Merger();

		foreach ( $post_ids as $post_id ) {

			if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'view_order', $post_id ) )
				continue;

			$order_invoices = wc_gzdp_get_invoices_by_order( wc_get_order( $post_id ), $invoice_type );
			
			if ( ! empty( $order_invoices ) ) {
				foreach( $order_invoices as $invoice ) {
					$path = $invoice->get_pdf_path();
					
					if ( $path && file_exists( $path ) ) {
						$pdf_merger->add_pdf( $path, 'all' );
					}
				}
			}
		}

		$result = $pdf_merger->merge( 'browser', 'invoices.pdf' );
		
		if ( false === $result )
			return $redirect_to;

	}

	public function invoice_search( $term ) {

		global $wpdb;

		$statuses = wc_gzdp_get_invoice_statuses();

		// Search invoices.
		$post_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 INNER JOIN {$wpdb->posts} p ON p1.post_id = p.ID WHERE p.post_type = 'invoice' AND ( p1.meta_key = '_invoice_number' OR p1.meta_key = '_invoice_number_formatted' ) AND p.post_status IN ('" . implode( "','", array_map( 'esc_sql', array_keys( $statuses ) ) ) . "') AND p1.meta_value LIKE '%%%s%%';", wc_clean( $term ) )
		);

		return $post_ids;
	}

	/**
	 * Search for invoice numbers before order search is being executed.
	 * @param WP_Query $wp
	 */
	public function shop_order_search_custom_fields( $wp ) {
		global $pagenow;

        $post_type = isset( $wp->query_vars['post_type'] ) ? $wp->query_vars['post_type'] : false;

		if ( 'edit.php' != $pagenow || $post_type != 'shop_order' ) {
			return;
		}

		if ( isset( $_GET['s'] ) && ! isset( $wp->query_vars['s'] ) ) {
			$wp->query_vars['s'] = wc_clean( $_GET['s'] );
		}

		if ( empty( $wp->query_vars['s'] ) ) {
			return;
		}

		$post_ids = $this->invoice_search( $wp->query_vars['s'] );

		if ( ! empty( $post_ids ) ) {
			
			// Search corresponding orders
			$order_ids = array();
			
			foreach ( $post_ids as $p ) {
				$order = get_post_meta( $p, '_invoice_order', true );
				array_push( $order_ids, $order );
			}

			// Remove "s" - we don't want to search order name.
			unset( $wp->query_vars['s'] );

			$wp->query_vars[ 'shop_order_search' ] = true;

			if ( ! is_array( $wp->query_vars['post__in'] ) )
				$wp->query_vars['post__in'] = array( $wp->query_vars['post__in'] );

			// Search by found posts.
			$wp->query_vars['post__in'] = array_merge( $order_ids, $wp->query_vars['post__in'] );
		} elseif ( isset( $wp->query_vars['shop_order_search'] ) ) {
			// Unset so that Woo order search works
			unset( $wp->query_vars['s'] );
		}
	}

	public function register_meta_boxes() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 40 );
	}

	public function set_table_vat_filter( $template_name ) {
		if ( $template_name == 'invoice/table.php' && get_option( 'woocommerce_gzdp_invoice_table_gross' ) == 'yes' )
			$template_name = 'invoice/table-gross.php'; 

		return $template_name;
	}

	public function set_invoice_static_text_order( $invoice ) {
		if ( 'invoice' === $invoice->content_type ) {
			$GLOBALS[ 'order' ] = $invoice->order;
			$GLOBALS[ 'invoice' ] = $invoice;
		}
	}

	public function set_invoice_attachment_private( $attach_id, $invoice ) {
		if ( 'invoice' === $invoice->content_type ) {
			update_post_meta( $attach_id, '_wc_gzdp_private', true );
		}
	}

	/**
	 * @param WC_Order_Item $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function set_order_meta_crud( $item, $cart_item_key, $values, $order ) {
		if ( is_a( $item, 'WC_Order_Item' ) && $item->get_product() ) {

			if ( $product = $item->get_product() ) {
				add_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, 'set_price_exclude' ), 0 );
				$item->update_meta_data( '_unit_price_excl', wc_gzd_get_product( $product )->get_unit_price_html( false ) );
				remove_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, 'set_price_exclude' ), 0 );
			}
		}
	}

	public function set_price_exclude( $mode ) {
		return 'excl';
	}

	/**
	 * Hide order mtea from order meta default output
	 *  
	 * @param array $metas
	 */
	public function set_order_meta_hidden( $metas ) {
		array_push( $metas, '_unit_price_excl' );
		return $metas;
	}

	public function check_pdf_template_version( $settings ) {

	    // In case module was disabled before - make sure the module gets loaded correctly
	    if ( ! class_exists( 'WC_GZDP_Invoice' ) ) {
	        WC_germanized_pro()->load_invoice_module();
        }

		$templates = array( 'woocommerce_gzdp_invoice_template_attachment', 'woocommerce_gzdp_invoice_template_attachment_first' );
		
		foreach ( $templates as $template ) {
			if ( $file = get_option( $template ) ) {
				$file = get_attached_file( $file );

				if ( ! $file ) {
					continue;
				}

				try {
					$invoice = new WC_GZDP_Invoice_Preview();
					$pdf     = new WC_GZDP_PDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

					$pdf->set_object( $invoice );
					$pdf->setTemplate( $invoice->get_pdf_template() );
					$pdf->addPage();

				} catch( Exception $e ) {
					delete_option( $template );
					WC_Admin_Settings::add_error( _x( 'Your PDF template seems be converted (version > 1.4) or compressed. Please convert (http://convert.neevia.com/pdfconvert/) your pdf file to version 1.4 or lower before uploading.', 'invoices', 'woocommerce-germanized-pro' ) );
				}
			}
		}
	}

	public function resend_order_emails( $emails ) {
		global $theorder;
		
		if ( is_null( $theorder ) ) {
			return $emails;
		}
		
		if ( wc_gzdp_order_has_invoice_type( $theorder, 'cancellation' ) ) {
			array_push( $emails, 'customer_invoice_cancellation' );
		}
		
		return $emails;
	}

	public function order_actions( $actions, $order ) {
		$invoices = wc_gzdp_get_invoices_by_order( $order );

		if ( ! empty( $invoices ) ) {
			foreach ( $invoices as $invoice ) {
				$actions[ 'download-invoice-' . $invoice->type . '-' . $invoice->number ] = array(
					'url'       => $invoice->get_pdf_url(),
					'name'      => sprintf( _x( 'Download %s', 'invoices', 'woocommerce-germanized-pro' ), $invoice->get_title() ),
					'action'    => "download"
				);
			}
		}

		if ( function_exists( 'wc_gzd_get_shipments_by_order' ) ) {
			$shipments = wc_gzd_get_shipments_by_order( $order );

			foreach( $shipments as $shipment ) {
				if ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment ) ) {
					$actions[ 'download-packing-slip-' . $packing_slip->type . '-' . $packing_slip->number ] = array(
						'url'       => $packing_slip->get_pdf_url(),
						'name'      => sprintf( _x( 'Download %s', 'invoices', 'woocommerce-germanized-pro' ), $packing_slip->get_title() ),
						'action'    => "download"
					);
				}
			}
		}

		return $actions;
	}

	public function unset_subscription_invoice_meta( $order_meta ) {
		foreach( $order_meta as $key => $meta ) {
			if ( '_invoices' === $meta[ 'meta_key' ] )
				unset( $order_meta[ $key ] );
		}

		return array_values( $order_meta );
	}

	public function on_change_order_status( $id, $order = false ) {
        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $id );
        }

        $gateway_id      = $order->get_payment_method();
        $default_option  = get_option( 'woocommerce_gzdp_invoice_auto_status' );
        $status_generate = $default_option;

        if ( $gateway_option = get_option( "woocommerce_gzdp_invoice_{$gateway_id}_auto_status" ) ) {
            $status_generate = $gateway_option;
        }

        $status_generate = $this->get_clean_order_status( $status_generate );

        if ( ! empty( $status_generate ) && $order->get_status() === $status_generate ) {
            $this->auto_generate_invoice( $id );
        } elseif ( empty( $status_generate ) ) {
            // Directly after order
            $this->auto_generate_invoice( $id );
        }
    }

    protected function get_clean_order_status( $status ) {
        return 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
    }

	public function set_automation() {

		do_action( 'woocommerce_gzdp_invoice_automation' );

		// Mark invoice as paid
		if ( get_option( 'woocommerce_gzdp_invoice_auto_paid_status' ) ) {

			$new_status = get_option( 'woocommerce_gzdp_invoice_auto_paid_status' );
			$hook       = 'woocommerce_order_status_' . $this->get_clean_order_status( $new_status );

			add_action( $hook, array( $this, 'auto_set_invoice_status' ), 0, 1 );
		}

		// Invoice auto generation
		if ( get_option( 'woocommerce_gzdp_invoice_auto' ) === 'yes' ) {

		    if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_auto_gateway_specific' ) ) {

		        // After order has been created via checkout
		        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'on_change_order_status' ), 10 );

		        // Hook into each order status change event to check whether invoice needs to be generated or not
                foreach( wc_get_order_statuses() as $status => $title ) {
                    $status = $this->get_clean_order_status( $status );

                    add_action( "woocommerce_order_status_{$status}", array( $this, 'on_change_order_status' ), 10, 2 );
                }
            } else {
                $hook = get_option( 'woocommerce_gzdp_invoice_auto_status' ) ? 'woocommerce_order_status_' : 'woocommerce_checkout_update_order_meta';

                if ( $hook == 'woocommerce_order_status_' ) {
                    $new_status  = get_option( 'woocommerce_gzdp_invoice_auto_status' );
                    $hook       .= 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
                }

                add_action( $hook, array( $this, 'auto_generate_invoice' ), 1, 1 );
            }

			// Trigger Manually Pay for Order
			if ( ! get_option( 'woocommerce_gzdp_invoice_auto_status' ) ) {
				add_action( 'woocommerce_before_pay_action', array( $this, 'auto_generate_invoice' ), 1, 1 );
			}

			// Compatibility with WooCommerce Subscriptions
			if ( ! get_option( 'woocommerce_gzdp_invoice_auto_status' ) ) {
				add_filter( 'wcs_renewal_order_created', array( $this, 'generate_subscription_invoice' ), 10, 2 );
			}
		}

		// Cancellation auto generation
		if ( get_option( 'woocommerce_gzdp_invoice_cancellation_auto' ) == 'yes' ) {

			$hook = 'woocommerce_order_status_cancelled';
			
			if ( $new_status = get_option( 'woocommerce_gzdp_invoice_cancellation_auto_status' ) )
				$hook = 'woocommerce_order_status_' . ( 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status );

			add_action( $hook, array( $this, 'auto_generate_cancellation' ), 2, 1 );
		}

		// Refunds auto generation
		if ( get_option( 'woocommerce_gzdp_invoice_cancellation_refunds_auto' ) === 'yes' ) {
            add_action( 'woocommerce_refund_created', array( $this, 'create_refund' ), 10, 2 );
            add_action( 'woocommerce_order_refunded', array( $this, 'maybe_redirect_after_refund' ), 100, 2 );
        }

		// Packing Slip auto generation
		if ( get_option( 'woocommerce_gzdp_invoice_packing_slip_auto' ) == 'yes' && get_option( 'woocommerce_gzdp_invoice_packing_slip_auto_shipment_status' ) ) {

			$status = str_replace( 'gzd-', '', get_option( 'woocommerce_gzdp_invoice_packing_slip_auto_shipment_status' ) );

			add_action( 'woocommerce_gzd_shipment_status_' . $status, array( $this, 'auto_generate_packing_slip' ), 10, 1 );
		}
	}

	public function invoice_export_filters() {
		include_once( WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-invoice-export.php' );
	}

	public function invoice_email_template_name( $name, $tpl ) {
		if ( $name == 'customer_invoice_simple' ) {
			return 'customer_invoice';
        }

		return $name;
	}

	public function generate_subscription_invoice( $renewal_order, $subscription ) {
	    if ( $renewal_order ) {
	        $order_id = $renewal_order->get_id();

	        if ( $order_id > 0 ) {
                $this->auto_generate_invoice( $order_id );
            }
        }

		return $renewal_order;
	}

	public function auto_generate_invoice( $order_id ) {

	    // Make sure changes are not overriden by post data in wp-admin
	    if ( isset( $_REQUEST['wc_gzdp_invoice_data'] ) ) {
	        unset( $_REQUEST['wc_gzdp_invoice_data'] );
        }

		// Remove filters
		WC_germanized()->emails->remove_order_email_filters();
		
		$order = wc_get_order( $order_id );
	
		// Allow Plugins to stop invoice generation - check for payment gateways if option has been set
		if ( ! apply_filters( 'woocommerce_gzdp_generate_invoice', true, $order ) || ( get_option( 'woocommerce_gzdp_invoice_auto_gateways' ) && ! in_array( $order->get_payment_method(), get_option( 'woocommerce_gzdp_invoice_auto_gateways', array() ) ) ) )
			return;
		
		$invoices = wc_gzdp_get_invoices_by_order( $order, 'simple' );
		
		if ( empty( $invoices ) ) {
			// Generate new invoice
			$args = apply_filters( 'woocommerce_gzdp_invoice_defaults', array( 'invoice_status' => ( isset( $GLOBALS[ 'wc_gzdp_new_invoice_status' ] ) ? wc_clean( $GLOBALS[ 'wc_gzdp_new_invoice_status' ] ) : wc_gzdp_get_default_invoice_status() ) ) );

			$invoice = wc_gzdp_get_invoice( false, 'simple' );
			$invoice->refresh( $args, $order );
		}

		$invoices = wc_gzdp_get_invoices_by_order( $order, 'simple' );
		
		foreach ( $invoices as $invoice ) {
			if ( get_option( 'woocommerce_gzdp_invoice_auto_email' ) === 'yes' && ! $invoice->is_delivered() && apply_filters( 'woocommerce_gzdp_invoice_auto_send_to_customer', true, $invoice ) ) {
				$invoice->send_to_customer();
            }
		}
	}

	public function auto_generate_cancellation( $order_id ) {

        // Make sure changes are not overriden by post data in wp-admin
        if ( isset( $_REQUEST['wc_gzdp_invoice_data'] ) ) {
            unset( $_REQUEST['wc_gzdp_invoice_data'] );
        }

		// Remove filters
		WC_germanized()->emails->remove_order_email_filters();

		$order = wc_get_order( $order_id );

		// Allow hook to stop cancellation generation for certain orders
		if ( ! apply_filters( 'woocommerce_gzdp_generate_cancellation', true, $order ) )
			return;

		$invoices = wc_gzdp_get_invoices_by_order( $order, 'simple' );
		
		if ( ! empty( $invoices ) ) {
			foreach ( $invoices as $invoice ) {

			    // Do not generate full cancellations for invoices that have already been cancelled
				if ( $invoice->is_cancelled() ) {
					continue;
                }

				if ( $invoice->is_partially_refunded() ) {
                    $this->create_full_partial_cancellation( $invoice );
                } else {
                    // Generate new cancellation
                    $cancellation = wc_gzdp_get_invoice( false, 'cancellation' );
                    $cancellation->refresh( array( 'invoice_parent' => $invoice->id ) );

                    // Unset parent invoice status - do not allow override after automatically marking parent as cancelled
                    if ( isset( $_POST[ 'invoice_status_' . $invoice->id ] ) ) {
                        unset( $_POST[ 'invoice_status_' . $invoice->id ] );
                    }

                    if ( get_option( 'woocommerce_gzdp_invoice_cancellation_auto_email' ) === 'yes' && ! $cancellation->is_delivered() ) {
                        $cancellation->send_to_customer();
                    }
                }
			}
		}
	}

	public function auto_generate_packing_slip( $shipment_id ) {

		// Remove filters
		WC_germanized()->emails->remove_order_email_filters();

		if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

			$order = $shipment->get_order();

			// Allow hook to stop cancellation generation for certain orders
			if ( ! apply_filters( 'woocommerce_gzdp_generate_packing_slip', true, ( $order ? $order : $shipment ), $shipment ) )
				return;

			$packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment );

			if ( empty( $packing_slip ) ) {

				// Generate packing slips
				$args = apply_filters( 'woocommerce_gzdp_packing_slips_defaults', array( 'invoice_status' => 'wc-gzdp-pending' ) );

				$packing_slip = wc_gzdp_get_invoice( false, 'packing_slip' );
				$packing_slip->refresh( $args, $shipment );
			}
		}
	}

	public function stop_automation_for_free_orders( $do_generate, $order ) {
		if ( $order->get_total() == 0 ) {
			return false;
        }

		return true;
	}

	public function auto_set_invoice_status( $order_id ) {

		$order    = wc_get_order( $order_id );
		$invoices = wc_gzdp_get_invoices_by_order( $order, 'simple' );

		// Set status for new invoices
		$GLOBALS['wc_gzdp_new_invoice_status'] = 'wc-gzdp-paid';
		
		if ( ! empty( $invoices ) ) {
			foreach ( $invoices as $invoice ) {
				if ( isset( $_POST[ 'invoice_status_' . $invoice->id ] ) ) {
					$_POST[ 'invoice_status_' . $invoice->id ] = 'wc-gzdp-paid';
                }

				$invoice->update_status( 'paid' );
			}
		}
	}

	public function preview_button() {
		if ( 'yes' === get_option( 'woocommerce_gzdp_invoice_enable' ) ) {
			include_once( WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-invoice-settings-before.php' );
		}
	}

	public function register_section( $sections ) {
		$sections['invoices'] = _x( 'Invoices & Packing Slips', 'invoices', 'woocommerce-germanized-pro' );
		return $sections;
	}

	public function get_settings() {
		// Make sure that wc_gzdp_get_invoice_types exists
		include_once 'wc-gzdp-invoice-functions.php';

		$status_select              = array_merge( array( '' => '' ), wc_get_order_statuses() );
		$cancellation_status_select = wc_get_order_statuses();

		$shipment_status_select     = wc_gzd_get_shipment_statuses();

		foreach( $cancellation_status_select as $key => $cancellation_status ) {

		    if ( ! in_array( $key, array( 'wc-cancelled', 'wc-failed' ) ) ) {
				unset( $cancellation_status_select[ $key ] );
            }
		}

		$gateways                  = WC()->payment_gateways()->payment_gateways();
		$gateway_select            = array();
		$gateway_specific_settings = array();

		foreach ( $gateways as $gateway ) {
			$gateway_select[ $gateway->id ] = $gateway->get_title();
        }

		$types = wc_gzdp_get_invoice_types();

		foreach ( $types as $key => $val ) {
			$types[ $key ] = $val['title'];
        }

        foreach( $gateways as $gateway ) {
            $gateway_specific_settings[] = array(
                'title' 	        => sprintf( _x( 'Order status (%s)', 'invoices', 'woocommerce-germanized-pro' ), $gateway->get_title() ),
                'desc' 		        => _x( 'Optionally choose an order status on which the invoice should be generated for this specific gateway.', 'invoices', 'woocommerce-germanized-pro' ),
                'id' 		        => "woocommerce_gzdp_invoice_{$gateway->id}_auto_status",
                'css' 		        => 'min-width:250px;',
                'default'	        => '',
                'type' 		        => 'select',
                'custom_attributes' => array(
                	'data-gateway' => esc_attr( $gateway->id ),
	                'data-placeholder' => _x( 'Same as general option', 'invoices', 'woocommerce-germanized-pro' ),
	                'data-show_if_woocommerce_gzdp_invoice_auto_gateway_specific' => '',
                ),
                'class'		        => 'chosen_select_nostd',
                'options'	        =>	$status_select,
                'desc_tip'	        =>  true,
            );
        }

		$options = array(

			array( 
				'title' => '',
				'type'  => 'title',
				'id'    => 'invoice_general_options',
				'desc'  => '',
			),

			array(
				'title' 	=> _x( 'Enable', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Enable Invoices & Packing Slips.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_enable',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
			),

			array(
				'title' 	=> _x( 'Sender address (short)', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip' 	=> _x( 'Choose an address which will be used as address sender. This address will be shown on top of the invoice receiver address. Use new line for reach address element.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_address',
				'default'	=> '',
				'placeholder' => __( "Musterfirma\nMax Mustermann\nMusterstraße 12\n12209 Musterstadt", 'woocommerce-germanized-pro' ),
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> _x( 'Sender address (detailed)', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip' 	=> _x( 'This is your detailed sender address which by default appears on the right side right before the date. Include company details (contact information, VAT ID) here. Use new line for reach address element.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_address_detail',
				'placeholder' => __( "Musterfirma\nTel.: +49 (30) 123 456\nFax: +49 (30) 123 456 7\nUSt.-ID: DE 123 456 78", 'woocommerce-germanized-pro' ),
				'default'	=> '',
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> _x( 'Gross prices', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Show gross item prices instead of net item prices within invoice.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> _x( 'This option will display gross item prices instead of net prices within your invoice. Shipping and fees will be shown as gross too. Taxes and net prices will be show right beneath total price.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_table_gross',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

            array(
                'title' 	=> _x( 'Show net totals', 'invoices', 'woocommerce-germanized-pro' ),
                'desc' 		=> _x( 'Always show net totals per tax rate or do only show in case of an order > 250,00 Euro.', 'invoices', 'woocommerce-germanized-pro' ),
                'id' 		=> 'woocommerce_gzdp_invoice_net_totals',
                'css' 		=> 'min-width:250px;',
                'default'	=> 'greater_250',
                'type' 		=> 'select',
                'class'		=> 'chosen_select',
                'options'	=>	array( 'always' => __( 'Always', 'woocommerce-germanized-pro' ), 'greater_250' => __( 'Order total > 250,00', 'woocommerce-germanized-pro' ), 'never' => __( 'Never', 'woocommerce-germanized-pro' ) ),
                'desc_tip'	=>  true,
            ),

			array(
				'title' 	=> _x( 'Frontend Download', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Enable download for certain invoice types within customer account.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_download_frontend_types',
				'default'	=> array( 'simple', 'cancellation' ),
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'options'	=> $types,
			),

			array(
				'title' 	=> _x( 'Force Download', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Force invoice download instead opening PDF invoice directly within browser.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_download_force',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Default status', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a default invoice status.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_default_status',
				'css' 		=> 'min-width:250px;',
				'default'	=> 'wc-gzdp-pending',
				'type' 		=> 'select',
				'class'		=> 'chosen_select_nostd',
				'options'	=>	wc_gzdp_get_invoice_statuses(),
				'desc_tip'	=>  true,
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_general_options' ),

			array( 'title' => _x( 'Numbering', 'invoices', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'invoice_number_options' ),

			array(
				'title' 	=> _x( 'Number format', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a format to display your invoice number. Use {type} as placeholder for the invoice type (e.g. Invoice, Cancellation), {number} as placeholder for invoice number, {order_number} as placeholder for the corresponding order. You may use date placeholders such as {y} for year, {m} for month (numeric) and {d} for day.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_number_format',
				'type' 		=> 'text',
				'default'	=> sprintf( _x( '%s %s', 'invoices', 'woocommerce-germanized-pro' ), '{type}', '{number}' ),
			),

			array(
				'title' 	=> _x( 'Cancellation format', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'You may want to specifically set the cancellation number format to a completely different value. Leave empty to use invoice number format.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_cancellation_number_format',
				'type' 		=> 'text',
				'default'	=> '',
			),

			array(
				'title' 	=> _x( 'Packing Slip format', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'You may want to specifically set the packing slip number format to a completely different value. Leave empty to use invoice number format. You may additionally use {shipment_number} or {order_number} as a placeholder here.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_number_format',
				'type' 		=> 'text',
				'default'	=> '',
			),

			array(
				'title' 	=> _x( 'Leading zeros', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Optionally choose number of leading zeros for invoice numbers.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_number_leading_zeros',
				'type' 		=> 'number',
				'default'	=> 0,
			),

			array(
				'title' 	=> _x( 'Last invoice number', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip' 	=> _x( 'Use this option to reset invoice numbering. Set to 0 to start from scratch. Please use carefully - modifying this option could lead to data inconsistency.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'wc_gzdp_invoice_simple',
				'default'	=> 0,
				'type' 		=> 'number',
				'class'     => 'wc-gzdp-input-reset-locked',
				'custom_attributes' => array( 'data-unlock-title' => esc_attr( _x( 'Force Reset', 'invoices', 'woocommerce-germanized-pro' ) ) ),
			),

			array(
				'title' 	=> _x( 'Numbers for cancellations', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Check if you want to use different numbering for invoice cancellations.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_cancellation_numbering',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Last cancellation number', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip' 	=> _x( 'Use this option to reset cancellation numbering. Set to 0 to start from scratch. Please use carefully - modifying this option could lead to data inconsistency.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'wc_gzdp_invoice_cancellation',
				'default'	=> 0,
				'type' 		=> 'number',
				'class'     => 'wc-gzdp-input-reset-locked',
				'custom_attributes' => array(
					'data-unlock-title' => esc_attr( _x( 'Force Reset', 'invoices', 'woocommerce-germanized-pro' ) ),
					'data-show_if_woocommerce_gzdp_invoice_cancellation_numbering' => '',
				),
			),

			array(
				'title' 	=> _x( 'Numbering for packing slips', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Enable numbering for packing slips. By default shipment number is being used.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_enable_numbering',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Last packing slip number', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip' 	=> _x( 'Use this option to reset packing slip numbering. Set to 0 to start from scratch. Please use carefully - modifying this option could lead to data inconsistency.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'wc_gzdp_invoice_packing_slip',
				'default'	=> 0,
				'type' 		=> 'number',
				'class'     => 'wc-gzdp-input-reset-locked',
				'custom_attributes' => array(
					'data-unlock-title' => esc_attr( _x( 'Force Reset', 'invoices', 'woocommerce-germanized-pro' ) ),
					'data-show_if_woocommerce_gzdp_invoice_packing_slip_enable_numbering' => '',
				),
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_number_options' ),

            array( 'title' => _x( 'Automation', 'invoices', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'invoice_automation_options' ),

			array(
				'title' 	=> _x( 'Generate invoices', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Automatically generate invoices for orders', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_auto',
				'desc_tip'	=> _x( 'Choose this option to automatically generate an invoice to an order after a certain order status has been reached.', 'invoices', 'woocommerce-germanized-pro' ),
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Order status', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Optionally choose an order status on which the invoice should be generated and (optionally) sent to the customer by email.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_auto_status',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'custom_attributes' => array(
					'data-placeholder' => _x( 'Directly after order', 'invoices', 'woocommerce-germanized-pro' ),
					'data-show_if_woocommerce_gzdp_invoice_auto' => '',
				),
				'class'		=> 'chosen_select_nostd',
				'options'	=>	$status_select,
				'desc_tip'	=>  true,
			),

			array(
				'title' 	=> _x( 'Payment gateways', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Optionally choose which payment gateways should be activated to automatically generate invoices.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_auto_gateways',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'multiselect',
				'custom_attributes' => array(
					'data-placeholder' => _x( 'Every payment gateway', 'invoices', 'woocommerce-germanized-pro' ),
					'data-show_if_woocommerce_gzdp_invoice_auto' => '',
				),
				'class'		=> 'chosen_select_nostd',
				'options'	=>	$gateway_select,
				'desc_tip'	=>  true,
			),

            array(
                'title' 	=> _x( 'Gateway specific statuses', 'invoices', 'woocommerce-germanized-pro' ),
                'desc' 		=> _x( 'Activate this option to choose gateway specific order statuses.', 'invoices', 'woocommerce-germanized-pro' ),
                'id' 		=> 'woocommerce_gzdp_invoice_auto_gateway_specific',
                'custom_attributes' => array(
	                'data-show_if_woocommerce_gzdp_invoice_auto' => '',
                ),
                'default'	=> 'no',
                'type' 		=> 'gzd_toggle',
            ),
        );

        $options = array_merge( $options, $gateway_specific_settings );

        $further_options = array(

			array(
				'title' 	=> _x( 'Sent to customer', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Automatically send invoices to the customer by email after generation', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_auto_email',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Stop for free', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Do not generate invoices for free orders.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_auto_except_free',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

            array(
                'title' 	=> _x( 'Mark as paid', 'invoices', 'woocommerce-germanized-pro' ),
                'desc' 		=> _x( 'Optionally choose to automatically mark an invoice as paid if a certain order status has been reached.', 'invoices', 'woocommerce-germanized-pro' ),
                'id' 		=> 'woocommerce_gzdp_invoice_auto_paid_status',
                'css' 		=> 'min-width:250px;',
                'default'	=> 'wc-completed',
                'type' 		=> 'select',
                'custom_attributes' => array( 'data-placeholder' => _x( 'Never', 'invoices', 'woocommerce-germanized-pro' ) ),
                'class'		=> 'chosen_select_nostd',
                'options'	=>	$status_select,
                'desc_tip'	=>  true,
            ),

			array(
                'title' 	=> _x( 'Generate cancellations', 'invoices', 'woocommerce-germanized-pro' ),
                'desc' 		=> _x( 'Automatically generate cancellations for cancelled orders', 'invoices', 'woocommerce-germanized-pro' ),
                'id' 		=> 'woocommerce_gzdp_invoice_cancellation_auto',
                'default'	=> 'no',
                'desc_tip'	=> _x( 'Choose this option to automatically generate a cancellation to an order after the order is being set to status cancelled.', 'invoices', 'woocommerce-germanized-pro' ),
                'type' 		=> 'gzd_toggle',
            ),

			array(
				'title' 	=> _x( 'Order status', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Optionally choose an order status on which the cancellation should be generated and (optionally) sent to the customer by email.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_cancellation_auto_status',
				'css' 		=> 'min-width:250px;',
				'default'	=> 'wc-cancelled',
				'type' 		=> 'select',
				'class'		=> 'chosen_select_nostd',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_invoice_cancellation_auto' => '',
				),
				'options'	=>	$cancellation_status_select,
				'desc_tip'	=>  true,
			),

	        array(
		        'title' 	=> _x( 'Sent to customer', 'invoices', 'woocommerce-germanized-pro' ),
		        'desc' 		=> _x( 'Automatically send cancellations to the customer by email', 'invoices', 'woocommerce-germanized-pro' ),
		        'id' 		=> 'woocommerce_gzdp_invoice_cancellation_auto_email',
		        'default'	=> 'no',
		        'custom_attributes' => array(
			        'data-show_if_woocommerce_gzdp_invoice_cancellation_auto' => '',
		        ),
		        'type' 		=> 'gzd_toggle',
	        ),

            array(
                'title' 	=> _x( 'Generate partial cancellations', 'invoices', 'woocommerce-germanized-pro' ),
                'desc' 		=> _x( 'Automatically generate partial cancellations for refunded orders.', 'invoices', 'woocommerce-germanized-pro' ),
                'id' 		=> 'woocommerce_gzdp_invoice_cancellation_refunds_auto',
                'default'	=> 'no',
                'desc_tip'	=> _x( 'Choose this option to automatically generate a partial cancellation if a refund for the given order has been added. Partial cancellations only work for line item refunds.', 'invoices', 'woocommerce-germanized-pro' ),
                'type' 		=> 'gzd_toggle',
            ),

			array(
				'title' 	=> _x( 'Generate packing slips', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Automatically generate packing slips for shipments', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_auto',
				'default'	=> 'no',
				'desc_tip'	=> _x( 'Choose this option to automatically generate a packing slip for a shipment after a certain shipment status has been reached.', 'invoices', 'woocommerce-germanized-pro' ),
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Shipment status', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Optionally choose a shipment status on which the packing slip should be generated.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_auto_shipment_status',
				'css' 		=> 'min-width:250px;',
				'default'	=> 'gzd-processing',
				'type' 		=> 'select',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_invoice_packing_slip_auto' => '',
				),
				'class'		=> 'chosen_select',
				'options'	=>	$shipment_status_select,
				'desc_tip'	=>  true,
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_automation_options' ),
		);

        $options = array_merge( $options, $further_options );
		$options = array_merge( $options, WC_GZDP_PDF_Helper::instance()->get_layout_settings( 'invoice' ) );

		return apply_filters( 'woocommerce_gzdp_invoice_settings', $options );
	}

	public function get_settings_general( $settings ) {

		return array_merge( $settings, array(
			array(
				'title' 	=> _x( 'Print sender address', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Show the sender address right above the recipient address.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_sender_address',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print sender address (detailed)', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Show the detailed sender address right above the invoice date field on the right side.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_sender_address_detail',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print tax rate', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Add a column with the item tax rate to the invoice.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_tax_rate',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Line discounts', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'  => _x( 'Adds a column next to the unit price which prints the discount per line. Line total will equal the discounted amount.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc'      => _x( 'Show line discount amounts instead of total discount.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_column_based_discounts',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Differential taxation', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Mark products if differential taxation applies and show notice.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_differential_taxation_notice',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print sku', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print product sku next to the product title.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_sku',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print variation attributes', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print variation attributes beneath the product title.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_variation_attributes',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print item cart description', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print item cart description beneath the product title.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_item_desc',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print delivery time', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print delivery time (if available) beneath the product title.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_delivery_time',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print unit price', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print unit price (if available) beneath the product title.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_unit_price',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Print product units', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print product units beneath the product title.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_show_product_units',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Date format', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose date format to be used to format the invoice date.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_date_format',
				'type' 		=> 'text',
				'default'	=> _x( 'd.m.Y', 'invoices', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> _x( 'Packing slip number', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Print number on packing slips as it is done for invoices.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_print_number',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

		) );

	}

	public function get_settings_margin( $settings ) {

		return array_merge( $settings, array(
			array(
				'title' 	=> _x( 'Address top margin', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose if you wish to have a margin for the address output.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_address_margin_top',
				'type' 		=> 'gzdp_decimal',
				'default'	=> '',
			),

			array(
				'title' 	=> _x( 'Date top margin', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose if you wish to have a margin for the date output.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_date_margin_top',
				'type' 		=> 'gzdp_decimal',
				'default'	=> '',
			),

			array(
				'title' 	=> _x( 'Title top margin', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose if you wish to have a relative margin from last element to the title.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_title_margin_top',
				'type' 		=> 'gzdp_decimal',
				'default'	=> '',
			),

			array(
				'title' 	=> _x( 'Table top margin', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose if you wish to have a relative margin from last element to the table.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_table_margin_top',
				'type' 		=> 'gzdp_decimal',
				'default'	=> '',
			),
		) );

	}

	public function get_settings_color( $settings ) {

		return array_merge( $settings, array(
			array(
				'title' 	=> _x( 'Table header background', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a color which will be used as background color for the header of the product table.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_table_header_bg',
				'type' 		=> 'color',
				'default'	=> '#EEEEEE',
			),

			array(
				'title' 	=> _x( 'Table header font color', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a font color for your table header.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_table_header_font_color',
				'type' 		=> 'color',
				'default'	=> '#000',
			),

			array(
				'title' 	=> _x( 'Table border', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a color which will be used as border color for the product table.', 'invoices', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_invoice_table_border_color',
				'type' 		=> 'color',
				'default'	=> '#CCCCCC',
			),
		) );

	}	

	public function get_settings_static_text( $settings ) {

		return array_merge( $settings, array(

			array(
				'title' 	=> _x( 'Before table', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 	    => sprintf( _x( 'You may want to display a static text right before the output of the product table. You may use basic HTML elements and inline CSS for layouting. Use table layouts instead of divs and floatings. Margins and paddings will be ignored. You may use <a href="%s" target="_blank">Shortcodes</a>.', 'invoices', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/shortcodes-fuer-rechnungen' ),
				'id' 		=> 'woocommerce_gzdp_invoice_text_before_table',
				'default'	=> '',
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'gzdp_editor',
			),

			array(
				'title' 	=> _x( 'After table', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 	    => sprintf( _x( 'You may want to display a static text right after the output of the product table. You may use basic HTML elements and inline CSS for layouting. Use table layouts instead of divs and floatings. Margins and paddings will be ignored. You may use <a href="%s" target="_blank">Shortcodes</a>.', 'invoices', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/shortcodes-fuer-rechnungen' ),
				'id' 		=> 'woocommerce_gzdp_invoice_text_after_table',
				'default'	=> ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' ? '[small_business_info]' : '' ),
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'gzdp_editor',
			),

			array(
				'title' 	=> _x( 'Different on cancellations', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Check if you want to use different content for cancellations.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_cancellation_table_content',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Cancellation before table', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 	    => sprintf( _x( 'You may want to display a static text right before the output of the product table. You may use basic HTML elements and inline CSS for layouting. Use table layouts instead of divs and floatings. Margins and paddings will be ignored. You may use <a href="%s" target="_blank">Shortcodes</a>.', 'invoices', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/shortcodes-fuer-rechnungen' ),
				'id' 		=> 'woocommerce_gzdp_invoice_cancellation_text_before_table',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_invoice_cancellation_table_content' => '',
				),
				'default'	=> '',
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'gzdp_editor',
			),

			array(
				'title' 	=> _x( 'Cancellation after table', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 	    => sprintf( _x( 'You may want to display a static text right after the output of the product table. You may use basic HTML elements and inline CSS for layouting. Use table layouts instead of divs and floatings. Margins and paddings will be ignored. You may use <a href="%s" target="_blank">Shortcodes</a>.', 'invoices', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/shortcodes-fuer-rechnungen' ),
				'id' 		=> 'woocommerce_gzdp_invoice_cancellation_text_after_table',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_invoice_cancellation_table_content' => '',
				),
				'default'	=> '',
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'gzdp_editor',
			),

			array(
				'title' 	=> _x( 'Different on packing slips', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Check if you want to use different content for packing slips.', 'invoices', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_table_content',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Packing slip before table', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 	    => sprintf( _x( 'You may want to display a static text right before the output of the product table. You may use basic HTML elements and inline CSS for layouting. Use table layouts instead of divs and floatings. Margins and paddings will be ignored. You may use <a href="%s" target="_blank">Shortcodes</a>.', 'invoices', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/shortcodes-fuer-rechnungen' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_text_before_table',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_invoice_packing_slip_table_content' => '',
				),
				'default'	=> '',
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'gzdp_editor',
			),

			array(
				'title' 	=> _x( 'Packing slip after table', 'invoices', 'woocommerce-germanized-pro' ),
				'desc' 	    => sprintf( _x( 'You may want to display a static text right after the output of the product table. You may use basic HTML elements and inline CSS for layouting. Use table layouts instead of divs and floatings. Margins and paddings will be ignored. You may use <a href="%s" target="_blank">Shortcodes</a>.', 'invoices', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/shortcodes-fuer-rechnungen' ),
				'id' 		=> 'woocommerce_gzdp_invoice_packing_slip_text_after_table',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_invoice_packing_slip_table_content' => '',
				),
				'default'	=> '',
				'css' 		=> 'width:70%; height: 85px;',
				'type' 		=> 'gzdp_editor',
			),

			array(
				'title' 	=> __( 'Reverse Charge Text', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Choose a Plain Text which will be used for the shortcode [reverse_charge].', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'Tax liability of the recipient of the services (reverse charge)', 'woocommerce-germanized-pro' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzdp_invoice_reverse_charge_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Third Party Text', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Choose a Plain Text which will be used for the shortcode [third_party_country] which will be shown to third party country customers.', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'Tax free export', 'woocommerce-germanized-pro' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzdp_invoice_third_party_country_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Differential Taxation Text', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Choose a Plain Text which will be used as differential taxation notice (if applicable).', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'Differential Taxation according to §25a UStG.', 'woocommerce-germanized-pro' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzdp_invoice_differential_taxation_notice_text',
				'type' 		=> 'textarea',
			),
		) );
	}

	public function download_invoice() {
		if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'wc-gzdp-download-invoice' ) {
		
			$invoice = false;
		
			if ( isset( $_GET[ 'preview' ] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc-gzdp-download' ) ) {
				
				// Treat preview specially
				ob_start();
				$invoice = new WC_GZDP_Invoice_Preview();
				$invoice->generate_pdf( true );
				echo ob_get_clean();
				exit();
		
			} elseif ( isset( $_GET[ 'id' ] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc-gzdp-download' ) ) {
				
				$id = absint( $_GET[ 'id' ] );

				if ( current_user_can( 'edit_shop_orders' ) || current_user_can( 'view_order', $invoice->order ) )
					$invoice = wc_gzdp_get_invoice( $id );

			} else {
				wp_die( __( 'Cheatin huh?', 'woocommerce-germanized-pro' ) );
				exit();
			}

			if ( $invoice ) {
				WC_GZDP_Download_Handler::download( $invoice, ( ( isset( $_GET[ 'force' ] ) && $_GET[ 'force' ] ) ? true : false ) );
			} else {
				wp_die( __( 'Missing permissions to download invoice', 'woocommerce-germanized-pro' ) );
				exit();
			}
		} elseif ( isset( $_GET['action'] ) && 'wc-gzdp-download-packing-slip-export' === $_GET['action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc-gzdp-download' ) ) {

			if ( current_user_can( 'edit_shop_orders' ) ) {
				$handler = new WC_GZDP_Admin_Packing_Slip_Bulk_Handler();

				if ( ( $file = $handler->get_file() ) && file_exists( $file ) ) {
					if ( ! isset( $_GET['force'] ) || 'no' === $_GET['force'] ) {
						WC_GZDP_Download_Handler::out( $handler->get_filename(), $file, false );
					} else {
						WC_GZDP_Download_Handler::out( $handler->get_filename(), $file, true );
					}
				}
            }
        }
	}

	public function get_meta_box_class( $type ) {
		// Invoice Meta Box
		$classname = 'WC_GZDP_Meta_Box_Invoice';
		
		if ( ! in_array( $type, array( 'simple', 'cancellation' ) ) )
			$classname = 'WC_GZDP_Meta_Box_Invoice_' . implode( '_', array_map( 'ucfirst', explode( '_', $type ) ) );
		
		if ( ! class_exists( $classname ) )
			return false;

		return $classname;
	}

	public function add_meta_boxes() {
		
		global $post;

		if ( ! $post ) {
			return;
		}

		if ( ! in_array( $post->post_type, wc_get_order_types( 'order-meta-boxes' ) ) )
			return;

		$order = wc_get_order( $post );
		$invoices = wc_gzdp_get_invoices_by_order( $order );
	
		if ( ! empty( $invoices ) ) {

			$i_count = 0;
			
			foreach ( $invoices as $id => $invoice ) {

				$i_count++;
				
				$theinvoice = $invoice;
				
				if ( ! $classname = $this->get_meta_box_class( $invoice->type ) )
					continue;

				add_meta_box( 'woocommerce-invoice-' . $i_count, $invoice->get_title(), $classname . '::output', 'shop_order', 'normal', 'high', array( 'invoice' => $theinvoice ) );
				
				if ( 'simple' === $invoice->type && ! $invoice->is_cancellation() && ! $invoice->is_cancelled() ) {

					$theinvoice = wc_gzdp_get_invoice( false, 'cancellation' );
					$theinvoice->set_parent( $invoice->id );
					
					// Add new Cancellation
					add_meta_box( 'woocommerce-invoice-cancellation-new', sprintf( __( 'Cancel %s', 'woocommerce-germanized-pro' ), $invoice->get_title() ), 'WC_GZDP_Meta_Box_Invoice::output', 'shop_order', 'normal', 'high', array( 'invoice' => $theinvoice ) );
				
				}
			}
		}

		foreach ( wc_gzdp_get_invoice_types() as $type => $values ) {

			if ( 'simple' === $type && ! wc_gzdp_order_supports_new_invoice( $order ) )
				continue;
			else if ( 'simple' !== $type && wc_gzdp_order_has_invoice_type( $order, $type ) )
				continue;

			if ( 'packing_slip' === $type ) {
				continue;
			}

			if ( $values[ 'manual' ] || ! $classname = $this->get_meta_box_class( $type ) )
				continue;

			$theinvoice = wc_gzdp_get_invoice( false, $type );

			// Add new Invoice
			add_meta_box( 'woocommerce-invoice-' . $theinvoice->type . '-new', $values[ 'title_new' ], $classname . '::output', 'shop_order', 'normal', 'high', array( 'invoice' => $theinvoice ) );

		}

	}

	public function save_invoices( $post_id, $post ) {

		if ( ! isset( $_REQUEST['wc_gzdp_invoice_data'] ) || ! wp_verify_nonce( $_REQUEST['wc_gzdp_invoice_data'], 'woocommerce_save_data' ) ) {
			return;
        }
		
		$order    = wc_get_order( $post );
		$invoices = $_POST['invoice'];
		
		if ( ! empty( $invoices ) ) {
		
			foreach ( $invoices as $id ) {
				
				$id = absint( $id );
				
				if ( empty( $id ) )
					continue;
				
				$invoice = wc_gzdp_get_invoice( $id );
				$data = array();
				
				if ( ! empty( $_POST ) ) {
					foreach ( $_POST as $key => $field ) {
						if ( ! empty( $field ) && substr( $key, 0, 8 ) == 'invoice_' && preg_replace( "/[^0-9]/", "", $key ) == $id ) {
							$data[ substr( str_replace( $id, '', $key ), 0, -1 ) ] = sanitize_text_field( $field );
                        }
					}
				}

				if ( ! $invoice ) {
					continue;
                }

				if ( $invoice->is_new() && $invoice->type === 'simple' && ! wc_gzdp_order_supports_new_invoice( $order ) ) {
					continue;
				}

				// Update Invoice Status
				if ( ! $invoice->is_new() && isset( $data['invoice_status'] ) && $invoice->get_status() !== $data['invoice_status'] ) {
					$invoice->update_status( $data['invoice_status'] );
                }
				
				if ( ! isset( $data['invoice_generate'] ) && $invoice->is_new() ) {
					continue;
				} elseif ( isset( $data['invoice_send'] ) && $invoice->has_attachment() ) {
					$invoice->send_to_customer();
					continue;
				} elseif ( isset( $data['invoice_delete'] ) ) {
					$invoice->delete( true );
					continue;
				}

				// Refresh only if chosen
				if ( isset( $data['invoice_generate'] ) ) {

				    // Support cancelling the rest of an invoice if partial cancellation exist
				    if ( 'cancellation' === $invoice->type && isset( $data['invoice_parent'] ) ) {
				        $parent_invoice = wc_gzdp_get_invoice( absint( $data['invoice_parent'] ) );

				        if ( $parent_invoice && $parent_invoice->is_partially_refunded() ) {
				            $result = $this->create_full_partial_cancellation( $parent_invoice );

				            if ( $result ) {
					            continue;
				            }
                        }
                    }

					$invoice->refresh( $data, $order );
                }
			}
		}
	}

	public function export_args( $args = array() ) {
		
		if ( 'invoice' == $_GET['content'] ) {
			$args['content'] = 'invoice';

			if ( $_GET['invoice_start_date'] || $_GET['invoice_end_date'] ) {
				$args['start_date'] = $_GET['invoice_start_date'];
				$args['end_date'] = $_GET['invoice_end_date'];
			}

			if ( $_GET['invoice_status'] )
				$args['status'] = $_GET['invoice_status'];

			if ( $_GET['invoice_type'] )
				$args['type'] = $_GET['invoice_type'];

			if ( $_GET['invoice_export_format'] )
				$args['format'] = $_GET['invoice_export_format'];
		}
		return $args;
	}

	public function export_invoices( $args = array() ) {
		if ( $args[ 'content' ] != 'invoice' )
			return;
		if ( $args[ 'format' ] == 'csv' )
			$export = new WC_GZDP_Admin_Invoice_Export( $args );
		else if ( $args[ 'format' ] == 'zip' )
			$export = new WC_GZDP_Admin_Invoice_Export_Attachments( $args );
		exit();
	}

}
WC_GZDP_Invoice_Helper::instance();