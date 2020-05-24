<?php

/**
 * Volume Pricing Table - Modal - Horizontal
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<!-- Anchor -->
<div class="rp_wcdpd_product_page">
    <div class="rp_wcdpd_product_page_modal_link"><span><?php echo $title; ?></span></div>
</div>

<!-- Modal -->
<div class="rp_wcdpd_modal" style="min-width: 400px;">
    <?php RightPress_Help::include_extension_template('promotion-volume-pricing-table', 'horizontal', RP_WCDPD_PLUGIN_PATH, RP_WCDPD_PLUGIN_KEY, array('title' => $title, 'data' => $data)); ?>
</div>
