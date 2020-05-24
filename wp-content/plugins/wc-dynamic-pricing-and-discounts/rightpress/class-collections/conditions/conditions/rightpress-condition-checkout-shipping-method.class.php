<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RightPress_Condition_Checkout')) {
    require_once('rightpress-condition-checkout.class.php');
}

/**
 * Condition: Checkout - Shipping Method
 *
 * @class RightPress_Condition_Checkout_Shipping_Method
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Checkout_Shipping_Method')) {

abstract class RightPress_Condition_Checkout_Shipping_Method extends RightPress_Condition_Checkout
{

    protected $key      = 'shipping_method';
    protected $method   = 'list';
    protected $fields   = array(
        'after' => array('shipping_methods'),
    );
    protected $position = 20;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

        parent::__construct();

        $this->hook();
    }

    /**
     * Get label
     *
     * @access public
     * @return string
     */
    public function get_label()
    {

        return __('Shipping method', 'rightpress');
    }

    /**
     * Get value to compare against condition
     *
     * @access public
     * @param array $params
     * @return mixed
     */
    public function get_value($params)
    {

        // Get chosen shipping methods
        if ($shipping_methods = WC()->session->get('chosen_shipping_methods')) {

            // Get single shipping method
            // TBD: We should introduce multiple shipping method support
            $shipping_method = array_shift($shipping_methods);

            // Return shipping method as both parent shipping method id and combined instance identifier
            return array(
                $shipping_method,
                strtok($shipping_method, ':'),
            );
        }

        return null;
    }





}
}
