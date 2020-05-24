<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Condition: Cart Item Subtotals - Product Shipping Classes
 *
 * @class RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Shipping_Classes
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Shipping_Classes')) {

class RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Shipping_Classes extends RightPress_Condition_Cart_Item_Subtotals_Product_Shipping_Classes
{

    protected $plugin_prefix = RP_WCDPD_PLUGIN_PRIVATE_PREFIX;

    protected $contexts = array(
        'cart_discounts',
        'checkout_fees',
    );

    // Singleton instance
    protected static $instance = false;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

        parent::__construct();
    }





}

RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Shipping_Classes::get_instance();

}
