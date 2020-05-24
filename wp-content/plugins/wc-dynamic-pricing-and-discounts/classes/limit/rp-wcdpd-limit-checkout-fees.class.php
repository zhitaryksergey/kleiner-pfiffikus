<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RP_WCDPD_Limit')) {
    require_once('rp-wcdpd-limit.class.php');
}

/**
 * Checkout Fee Limit Controller
 *
 * @class RP_WCDPD_Limit_Checkout_Fees
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Limit_Checkout_Fees')) {

class RP_WCDPD_Limit_Checkout_Fees extends RP_WCDPD_Limit
{

    protected $context = 'checkout_fees';

    // Singleton instance
    protected static $instance = false;

    /**
     * Singleton control
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Get method controller
     *
     * @access protected
     * @return object
     */
    protected function get_method_controller()
    {

        return RP_WCDPD_Controller_Methods_Checkout_Fee::get_instance();
    }

    /**
     * Round limited amount
     *
     * @access public
     * @param float $amount
     * @return float
     */
    protected function round($amount)
    {

        return round($amount, wc_get_price_decimals());
    }

    /**
     * Get initial limit value
     *
     * @access protected
     * @param float $value
     * @param float $reference
     * @return float|bool
     */
    protected function get_initial_limit_value($value, $reference = null)
    {

        // Fixed limit adjustment
        if (RightPress_Help::string_ends_with_substring($this->get_method(), '_amount')) {

            // Subtract potential tax from fee amount
            return RP_WCDPD_Controller_Methods_Checkout_Fee::subtract_tax_from_fee_amount($value);
        }

        // Call parent method if no adjustments are needed
        return parent::get_initial_limit_value($value, $reference);
    }

    /**
     * Check whether or not zero amounts should be displayed
     *
     * @access public
     * @return bool
     */
    public function allow_zero_amount()
    {

        return apply_filters('rp_wcdpd_allow_zero_checkout_fee', false);
    }





}

RP_WCDPD_Limit_Checkout_Fees::get_instance();

}
