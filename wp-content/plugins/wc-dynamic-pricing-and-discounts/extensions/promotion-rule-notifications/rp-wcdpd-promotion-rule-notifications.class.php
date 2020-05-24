<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Promotion: Rule Notifications
 *
 * @class RP_WCDPD_Promotion_Rule_Notifications
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Promotion_Rule_Notifications')) {

class RP_WCDPD_Promotion_Rule_Notifications
{

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

        // Register settings structure
        add_filter('rp_wcdpd_settings_structure', array($this, 'register_settings_structure'), 170);

        // Load classes
        require_once plugin_dir_path(__FILE__) . 'classes/rp-wcdpd-rule-notifications-cart-discounts.class.php';
        require_once plugin_dir_path(__FILE__) . 'classes/rp-wcdpd-rule-notifications-checkout-fees.class.php';
        require_once plugin_dir_path(__FILE__) . 'classes/rp-wcdpd-rule-notifications-product-pricing.class.php';
    }

    /**
     * Register settings structure
     *
     * @access public
     * @param array $settings
     * @return array
     */
    public function register_settings_structure($settings)
    {

        $settings['promo']['children']['rule_notifications'] = array(
            'title' => __('Customer Notifications', 'rp_wcdpd'),
            'info'  => __('Displays a notification when pricing rule, cart discount or checkout fee is applied.', 'rp_wcdpd'),
            'children' => array(),
        );

        return $settings;
    }





}

RP_WCDPD_Promotion_Rule_Notifications::get_instance();

}
