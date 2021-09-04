<?php
/**
 * The Template for displaying a vendor in the vendor list shortcode
 *
 * Override this template by copying it to yourtheme/wc-vendors/front
 *
 * @package    WCVendors_Pro
 * @version    1.6.3
 */

$store_icon_src 	= wp_get_attachment_image_src( get_user_meta( $vendor_id, '_wcv_store_icon_id', true ), array( 100, 100 ) );
$store_icon 			= '';
$store_banner_src 	= wp_get_attachment_image_src( get_user_meta( $vendor_id, '_wcv_store_banner_id', true ), 'full');
$store_banner 		= '';

// see if the array is valid
if ( is_array( $store_icon_src ) ) {
	$store_icon 	= '<img src="'.esc_url($store_icon_src[0]).'" alt="'.esc_attr($vendor_meta['pv_shop_name']).'" class="store-icon" />';
}

if ( is_array( $store_banner_src ) ) {
	$store_banner	= '<img src="'. esc_url($store_banner_src[0]).'" alt="" class="store-banner" style="max-height: 200px;"/>';
} else {
	//  Getting default banner
	$default_banner_src = WCVendors_Pro::get_option( 'default_store_banner_src' );
	$store_banner	= '<img src="'. esc_url($default_banner_src).'" alt="" class="store-banner" style="max-height: 200px;"/>';
}

// Get all vendor products
$vendor_products_ids = WCVendors_Pro_Vendor_Controller::get_products_by_id( $vendor_id );
$products_count = count($vendor_products_ids);

// Get Vendor address
$address1 			= ( array_key_exists( '_wcv_store_address1', $vendor_meta ) ) ? $vendor_meta[ '_wcv_store_address1' ] : '';
$city	 					= ( array_key_exists( '_wcv_store_city', $vendor_meta ) ) ? $vendor_meta[ '_wcv_store_city' ]  : '';
$state	 				= ( array_key_exists( '_wcv_store_state', $vendor_meta ) ) ? $vendor_meta[ '_wcv_store_state' ] : '';
$store_postcode	= ( array_key_exists( '_wcv_store_postcode', $vendor_meta ) ) ? $vendor_meta[ '_wcv_store_postcode' ]  : '';
$address 				= ( $address1 != '') ? $address1 .', ' . $city .', '. $state .', '. $store_postcode : '';


?>

<div class="wcv-pro-vendorlist">

	<div class="wcv-store-grid row">

		<div class="wcv-banner-wrapper hidden-xs col-sm-4 col-md-2">

			<div class="wcv-banner-inner">

				    <?php echo $store_banner; ?>

                <div class="wcv-inner-details">

                    <?php if ($store_icon) { ?>
					<div class="wcv-icon-container">
							<?php echo $store_icon; ?>
					</div>
					<?php } ?>

					<?php if ($social_icons) { ?>
					<div class="wcv-socials-container">
							<?php echo wcv_format_store_social_icons( $vendor_id ); ?>
							<i class="fa fa-share-alt" aria-hidden="true"></i>
					</div>
					<?php } ?>
                </div>
			</div>

		</div>

		<div class="wcv-description-wrapper col-xs-12 col-sm-8 col-md-4">

			<div class="wcv-description-inner">
				<h4><?php echo $shop_name; ?></h4>
				<span class="rating-container">
						<?php if ( 'no' === get_option( 'wcvendors_ratings_management_cap' ) ) echo WCVendors_Pro_Ratings_Controller::ratings_link( $vendor_id, true ); ?>
				</span>
				<?php if ($products_count && $products_count>0) echo '<span class="products-count">'.esc_html( sprintf( _n( '%s product', '%s products', $products_count, 'handystore' ), $products_count ) ).'</span>'; ?>
				<?php if ($address && $address!='') echo '<span class="vendor-address"><i class="fa fa-map-marker" aria-hidden="true"></i>'.$address.'</span>'; ?>
				<div class="short-description"><?php echo $vendor_meta[ 'pv_shop_description' ]; ?></div>
				<a href="<?php echo $shop_link; ?>" class="button" rel="nofollow"><?php esc_html_e('Visit Store', 'handystore'); ?></a>
			</div>

		</div>

		<div class="wcv-products-wrapper hidden-xs hidden-sm col-md-6">

			<div class="wcv-products-inner">
				<?php $product_images = '';
				foreach ($vendor_products_ids as $key => $id) {
						if ($key == 5 || $key == (count($vendor_products_ids)-1) ) {
							$product_images .= '<div class="product-img">'.get_the_post_thumbnail($id, 'pt-vendor-product-thumbs').'<span class="total-qty">'.sprintf( _n( '<span>%s</span> item', '<span>%s</span> items', $products_count, 'handystore' ), $products_count ).'</span></div>';
							break;
						} else {
							$product_images .= '<div class="product-img">'.get_the_post_thumbnail($id, 'pt-vendor-product-thumbs').'</div>';
						}
				}
				echo $product_images; ?>
			</div>

		</div>

	</div><!-- close wcv-store-grid -->

</div>
