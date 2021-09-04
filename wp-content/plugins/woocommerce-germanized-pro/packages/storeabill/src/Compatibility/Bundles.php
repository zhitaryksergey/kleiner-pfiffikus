<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Interfaces\Compatibility;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Item;

defined( 'ABSPATH' ) || exit;

class Bundles implements Compatibility {

	public static function is_active() {
		return class_exists( 'WC_Bundles' );
	}

	public static function init() {
		$document_types = array(
			'invoice',
			'invoice_cancellation'
		);

		foreach( $document_types as $document_type ) {
			add_filter( "storeabill_{$document_type}_item_table_items", array( __CLASS__, 'hide_bundled_items' ), 10, 2 );
			add_filter( "storeabill_{$document_type}_item_table_column_classes", array( __CLASS__, 'add_bundle_classes' ), 10, 3 );
			add_action( "storeabill_{$document_type}_item_table_after_row", array( __CLASS__, 'add_bundle_items' ), 10, 5 );
			add_action( "storeabill_{$document_type}_item_table_before_row", array( __CLASS__, 'maybe_hide_bundle_container_prices' ), 10, 5 );

			add_action( "storeabill_{$document_type}_hide_email_details", array( __CLASS__, 'maybe_hide_details' ), 10, 2 );
		}
	}

	/**
	 * Hide email document items table in case the invoice contains bundles.
	 *
	 * @param $hide_details
	 * @param Invoice $document
	 */
	public static function maybe_hide_details( $hide_details, $document ) {
		if ( self::invoice_has_bundle( $document ) ) {
			$hide_details = true;
		}

		return $hide_details;
	}

	/**
	 * @param Invoice $document
	 */
	protected static function invoice_has_bundle( $document ) {
		$has_bundle = false;

		if ( function_exists( 'wc_pb_is_bundle_container_order_item' ) ) {
			foreach( $document->get_items() as $item ) {
				if ( $ref_item = $item->get_reference() ) {
					$order_item = $ref_item->get_order_item();

					if ( wc_pb_is_bundle_container_order_item( $order_item ) ) {
						$has_bundle = true;
						break;
					}
				}
			}
		}

		return $has_bundle;
	}

	/**
     * @param array $classes
	 * @param Item $item
	 * @param Invoice $document
	 */
	public static function add_bundle_classes( $classes, $item, $document ) {
		if (
			is_a( $document, 'Vendidero\StoreaBill\Invoice\Invoice' ) &&
			function_exists( 'wc_pb_is_bundle_container_order_item' ) &&
            function_exists( 'wc_pb_is_bundled_order_item' )
        ) {
			if ( $ref_item = $item->get_reference() ) {
				$order_item = $ref_item->get_order_item();

				if ( wc_pb_is_bundle_container_order_item( $order_item ) ) {
                    $classes[] = 'sab-bundle-container-item';
				} elseif( wc_pb_is_bundled_order_item( $order_item, $document->get_order() ) ) {
				    $classes[] = 'sab-bundle-child-item';
                }
			}
        }

		return $classes;
    }

	/**
	 * @param Item $item
	 * @param Invoice $document
	 * @param array $columns
	 * @param integer $count
	 * @param integer $item_size
	 */
    public static function maybe_hide_bundle_container_prices( $item, $document, $columns, $count, $item_size ) {
	    if (
		    is_a( $document, 'Vendidero\StoreaBill\Invoice\Invoice' ) &&
		    function_exists( 'wc_pb_is_bundle_container_order_item' ) )
	    {
		    if ( $ref_item = $item->get_reference() ) {
			    $order_item = $ref_item->get_order_item();

			    if ( wc_pb_is_bundle_container_order_item( $order_item ) ) {
				    $items = self::get_bundled_items( $order_item, $document );

				    if ( ! empty( $items ) ) {
					    /**
					     * Bundled prices with zero total
					     */
					    $hide_zero_prices = apply_filters( 'storeabill_invoice_hide_container_bundle_zero_prices', true, $item, $document );

					    if ( $item->get_total() == 0 && $hide_zero_prices ) {
						    add_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
						    add_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
						    add_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );
					    }
				    }
			    }
		    }
	    }
    }

	/**
	 * @param Item $item
	 * @param Invoice $document
     * @param array $columns
     * @param integer $count
     * @param integer $item_size
	 */
	public static function add_bundle_items( $item, $document, $columns, $count, $item_size ) {
		if (
			is_a( $document, 'Vendidero\StoreaBill\Invoice\Invoice' ) &&
			function_exists( 'wc_pb_is_bundle_container_order_item' ) )
		{
			if ( $ref_item = $item->get_reference() ) {
				$order_item = $ref_item->get_order_item();

				if ( wc_pb_is_bundle_container_order_item( $order_item ) ) {
					/**
					 * Maybe remove filters set within maybe_hide_bundle_container_prices
					 */
					remove_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
					remove_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
					remove_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );

					$items = self::get_bundled_items( $order_item, $document );

					if ( ! empty( $items ) ) {
					    foreach( $items as $bundled_item ) {
						    /**
						     * Bundled prices with zero total
						     */
						    $hide_zero_prices = apply_filters( 'storeabill_invoice_hide_bundled_zero_prices', true, $bundled_item, $document );

					    	if ( $bundled_item->get_total() == 0 && $hide_zero_prices ) {
					    		add_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
					    		add_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
							    add_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );
						    }

						    sab_get_template( 'blocks/item-table/row.php', array(
							    'document'  => $document,
							    'count'     => $count,
							    'item'      => $bundled_item,
							    'item_size' => $item_size,
							    'columns'   => $columns
						    ) );

						    /**
						     * Bundled prices with zero total
						     */
						    if ( $bundled_item->get_total() == 0 && $hide_zero_prices ) {
							    remove_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
							    remove_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
							    remove_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );
						    }
					    }
                    }
				}
			}
		}
	}

	public static function hide_tax_rate( $tax_rate ) {
		return '-';
	}

	public static function hide_price( $price ) {
		return '-';
	}

	/**
	 * @param \WC_Order_Item_Product $order_item
     * @param Invoice $invoice
	 */
	protected static function get_bundled_items( $order_item, $invoice ) {
	    if ( ! function_exists( 'wc_pb_get_bundled_order_items' ) ) {
	        return array();
        }

        $order_item_ids = wc_pb_get_bundled_order_items( $order_item, $invoice->get_order(), true );
        $bundled_items  = array();

        foreach( $order_item_ids as $order_item_id ) {
            if ( $bundled_item = $invoice->get_item_by_reference_id( $order_item_id ) ) {
                $bundled_items[] = $bundled_item;
            }
        }

        return $bundled_items;
	}

	/**
	 * Remove bundled items from table.
	 *
	 * @param Item[] $items
	 * @param $document
	 *
	 * @return mixed
	 */
	public static function hide_bundled_items( $items, $document ) {
		if (
			is_a( $document, 'Vendidero\StoreaBill\Invoice\Invoice' ) &&
			function_exists( 'wc_pb_is_bundled_order_item' ) )
		{
			foreach( $items as $key => $item ) {
				if ( $ref_item = $item->get_reference() ) {
					$order_item = $ref_item->get_order_item();

					if ( wc_pb_is_bundled_order_item( $order_item, $document->get_order() ) ) {
						unset( $items[ $key ] );
					}
				}
			}
		}

		return $items;
	}
}
