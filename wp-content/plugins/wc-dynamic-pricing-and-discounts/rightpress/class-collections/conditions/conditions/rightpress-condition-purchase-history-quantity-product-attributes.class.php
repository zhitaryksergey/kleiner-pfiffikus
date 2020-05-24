<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RightPress_Condition_Purchase_History_Quantity')) {
    require_once('rightpress-condition-purchase-history-quantity.class.php');
}

/**
 * Condition: Purchase History Quantity - Product Attributes
 *
 * @class RightPress_Condition_Purchase_History_Quantity_Product_Attributes
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Purchase_History_Quantity_Product_Attributes')) {

abstract class RightPress_Condition_Purchase_History_Quantity_Product_Attributes extends RightPress_Condition_Purchase_History_Quantity
{

    protected $key          = 'product_attributes';
    protected $method       = 'numeric';
    protected $fields       = array(
        'before'    => array('product_attributes'),
        'after'     => array('number'),
    );
    protected $main_field   = 'number';
    protected $position     = 40;

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

        return __('Quantity purchased - Attributes', 'rightpress');
    }





}
}
