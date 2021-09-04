<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead>
			<tr>
				<th class="product-name"><?php esc_html_e( 'Product', 'handystore' ); ?></th>
				<th class="product-price"><?php esc_html_e( 'Price', 'handystore' ); ?></th>
				<th class="product-status"><?php esc_html_e( 'Status', 'handystore' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'Qty', 'handystore' ); ?></th>
				<th class="product-subtotal"><?php esc_html_e( 'Total', 'handystore' ); ?></th>
				<th class="product-remove"><?php esc_html_e( 'Remove', 'handystore' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

						<td class="product-name" data-title="<?php esc_html_e( 'Product', 'handystore' ); ?>">
							<?php
								$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

								if ( ! $product_permalink ) {
									echo '<div class="product-thumbnail">' . $thumbnail . '</div>'; // PHPCS: XSS ok.
								} else {
									echo '<div class="product-thumbnail">';
									printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
									echo '</div>';
								}

								if ( ! $product_permalink ) {
									echo  wp_kses_post(apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;');
								} else {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ));
								}

								// Meta data.
								echo wc_get_formatted_cart_item_data( $cart_item );

								// Backorder notification
								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
									echo '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'handystore' ) . '</p>';
								}
							?>
						</td>

						<td class="product-price" data-title="<?php esc_html_e( 'Price', 'handystore' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
							?>
						</td>

						<td class="product-status">
							<?php
								$availability = $_product->get_stock_status();
								$stock_qty = $_product->get_stock_quantity();
	    						if ( $availability == 'instock') {
	    							$stock_qty = $_product->get_stock_quantity();
	    							if ( $stock_qty && $stock_qty !=='0' && get_option('woocommerce_stock_format')!=='no_amount' ) {
	    								$stock_qty = '&nbsp;(' . $stock_qty . ')';
	    							} else { $stock_qty = ''; }
	        						echo '<p class="stock">' . esc_html__('In Stock', 'handystore') . $stock_qty . '</p>';
	    						} else {
	    							echo '<p class="stock">' . esc_html__('Out of Stock', 'handystore') . '</p>';
	    						}
							?>
						</td>

						<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'handystore' ); ?>"><?php
						if ( $_product->is_sold_individually() ) {
							$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
						} else {
							$product_quantity = woocommerce_quantity_input( array(
								'input_name'    => "cart[{$cart_item_key}][qty]",
								'input_value'   => $cart_item['quantity'],
								'max_value'     => $_product->get_max_purchase_quantity(),
								'min_value'     => '0',
								'product_name'  => $_product->get_name(),
							), $_product, false );
						}

						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
						?></td>

						<td class="product-subtotal" data-title="<?php esc_html_e( 'Total', 'handystore' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
							?>
						</td>

						<td class="product-remove">
							<?php
								echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
									'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									esc_html__( 'Remove this item', 'handystore' ),
									esc_attr( $product_id ),
									esc_attr( $_product->get_sku() )
								), $cart_item_key );
							?>
						</td>

					</tr>
					<?php
				}
			}

			do_action( 'woocommerce_cart_contents' );
			?>
			<tr>
				<td colspan="6" class="actions">

					<?php if ( wc_coupons_enabled() ) { ?>
						<div class="coupon">
							<label for="coupon_code"><?php esc_html_e( 'Coupon:', 'handystore' ); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'handystore' ); ?>" /> <input type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'handystore' ); ?>" />
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php } ?>

					<?php /* Return to shop button */
						$shop_page_url = get_permalink( wc_get_page_id( 'shop' ) );
						echo '<a class="button return-to-shop" rel="nofollow" href="'.esc_url($shop_page_url).'">'.esc_html__('Continue shopping', 'handystore').'</a>';
					?>

					<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'handystore' ); ?>"><?php esc_html_e( 'Update cart', 'handystore' ); ?></button>

					<?php do_action( 'woocommerce_cart_actions' ); ?>

					<?php wp_nonce_field( 'woocommerce-cart' ); ?>
				</td>
			</tr>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</tbody>
	</table>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<div class="cart-collaterals">
	<?php do_action( 'woocommerce_cart_collaterals' ); ?>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
