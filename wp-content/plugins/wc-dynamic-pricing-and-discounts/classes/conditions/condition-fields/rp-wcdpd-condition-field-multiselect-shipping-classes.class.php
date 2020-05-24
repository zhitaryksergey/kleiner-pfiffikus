<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Condition Field: Multiselect - Shipping Classes
 *
 * @class RP_WCDPD_Condition_Field_Multiselect_Shipping_Classes
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Condition_Field_Multiselect_Shipping_Classes')) {

class RP_WCDPD_Condition_Field_Multiselect_Shipping_Classes extends RightPress_Condition_Field_Multiselect_Shipping_Classes
{

    protected $plugin_prefix = RP_WCDPD_PLUGIN_PRIVATE_PREFIX;

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

RP_WCDPD_Condition_Field_Multiselect_Shipping_Classes::get_instance();

}
