<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Condition: Cart Item Subtotals - Product Categories
 *
 * @class RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Categories
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Categories')) {

class RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Categories extends RightPress_Condition_Cart_Item_Subtotals_Product_Categories
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

RP_WCDPD_Condition_Cart_Item_Subtotals_Product_Categories::get_instance();

}
