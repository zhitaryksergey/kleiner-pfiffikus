<?php
    /**
     * Single product short description
     *
     * This template can be overridden by copying it to yourtheme/woocommerce/single-product/short-description.php.
     *
     * HOWEVER, on occasion WooCommerce will need to update template files and you
     * (the theme developer) will need to copy the new files to your theme to
     * maintain compatibility. We try to do this as little as possible, but it does
     * happen. When this occurs the version of the template file will be bumped and
     * the readme will list any important changes.
     *
     * @see     https://docs.woocommerce.com/document/template-structure/
     * @package WooCommerce/Templates
     * @version 3.3.0
     */

    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly.
    }

    global $post, $product;

    $short_description = apply_filters('woocommerce_short_description', $post->post_excerpt);

    if (!$short_description) {
        return;
    }

?>
<?php $attributes = $product->get_attributes() ?>
    <div class="woocommerce-product-details__short-description my_addon">
        <div class="desc"><?php echo $short_description; // WPCS: XSS ok. ?></div>
        <?php if (isset($attributes['pa_warnhinweise'])): ?>
            <?php if (in_array(215, $attributes['pa_warnhinweise']->get_data()['options'])): ?>
                <div class="attemption">
                    <img src="<?= get_stylesheet_directory_uri() ?>/img/warning.jpg"/>
                </div>
            <?php endif ?>
        <?php endif ?>
    </div>
