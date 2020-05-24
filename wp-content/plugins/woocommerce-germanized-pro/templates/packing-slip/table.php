<?php
/**
 * The Template for displaying the packing slip table on PDF packing slips.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/packing-slip/table.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/dokument/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 2.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order    = $invoice->get_order();
$shipment = $invoice->get_shipment();

$total_width = $total_width - 5;
$columns = 2;
$first_width = $total_width * 0.8;
$total_width_left = $total_width - $first_width;
$column_width = $total_width_left;

?>

<?php if ( $invoice->get_static_pdf_text( 'before_table' ) ) : ?>
	<div class="static">
		<?php echo $invoice->get_static_pdf_text( 'before_table' ); ?>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_gzdp_packing_slip_before_item_table', $invoice ); ?>

<table class="main">
	<thead>
		<tr class="header">
			<th class="first" width="<?php echo $first_width; ?>"><?php _e( 'Services', 'woocommerce-germanized-pro' ); ?></th>
			<th class="last" width="<?php echo $column_width; ?>"><?php _e( 'Quantity', 'woocommerce-germanized-pro' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( $invoice->get_items() ) : ?>

			<?php foreach ( $invoice->get_items() as $item_id => $item ) :

                $order_item      = $item->get_order_item();
			    $_product        = wc_get_product( $item->get_product_id() );
				$item_meta_print = '';

			    if ( $order_item ) {
			        $_product        = $order_item->get_product();
				    $item_meta_print = wc_gzdp_get_order_meta_print( $_product, $order_item );
                }
			?>
				<tr class="data" nobr="true">
					<td class="first" width="<?php echo $first_width; ?>">
						<?php

						// Product name
						echo apply_filters( 'woocommerce_gzdp_invoice_item_name', $item->get_name(), ( $order_item ? $order_item : $item ), false );

						// SKU
						if ( $invoice->get_option( 'show_sku' ) === 'yes' && is_object( $_product ) && $_product->get_sku() ) {
							echo ' (#' . $_product->get_sku() . ')';
						}

						// allow other plugins to add additional product information here
						do_action( 'woocommerce_gzdp_packing_slip_item_meta_start', $item_id, ( $order_item ? $order_item : $item ), $order, $item );

						if ( $invoice->get_option( 'show_variation_attributes' ) == 'yes' && ! empty( $item_meta_print ) ) {
							echo '<br/><small>' . $item_meta_print . '</small>';
						}

						?>

						<?php if ( $order_item && $invoice->get_option( 'show_delivery_time' ) == 'yes' ) : $product_delivery_time = wc_gzd_cart_product_delivery_time( '', $order_item ); ?>

							<?php if ( ! empty( $product_delivery_time ) ) : ?>
                                <p><small><?php echo trim( strip_tags( $product_delivery_time ) ); ?></small></p>
							<?php endif; ?>

						<?php endif; ?>

						<?php if ( $order_item && $invoice->get_option( 'show_product_units' ) == 'yes' ) : $product_units = wc_gzd_cart_product_units( '', $order_item ); ?>

							<?php if ( ! empty( $product_units ) ) : ?>
                                <p><small><?php echo strip_tags( $product_units ); ?></small></p>
							<?php endif; ?>

						<?php endif; ?>

						<?php if ( $order_item && $invoice->get_option( 'show_item_desc' ) == 'yes' ) : $product_desc = wc_gzd_cart_product_item_desc( '', $order_item ); ?>

							<?php if ( ! empty( $product_desc ) ) : ?>
								<?php echo wpautop( $product_desc ); ?>
							<?php endif; ?>

						<?php endif; ?>

						<?php do_action( 'woocommerce_gzdp_packing_slip_after_column_name', ( $order_item ? $order_item : $item ), $invoice, $item ); ?>
                    </td>
					<td class="last" width="<?php echo $column_width; ?>"><?php echo $item->get_quantity(); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>

<?php do_action( 'woocommerce_gzdp_packing_slip_after_item_table', $invoice ); ?>

<?php if ( $invoice->get_static_pdf_text( 'after_table' ) ) : ?>
	<div class="static">
		<?php echo $invoice->get_static_pdf_text( 'after_table' ); ?>
	</div>
<?php endif; ?>
