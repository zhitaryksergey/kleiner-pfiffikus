<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RightPress_Condition_Cart_Item_Subtotals')) {
    require_once('rightpress-condition-cart-item-subtotals.class.php');
}

/**
 * Condition: Cart Item Subtotals - Product Tags
 *
 * @class RightPress_Condition_Cart_Item_Subtotals_Product_Tags
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Cart_Item_Subtotals_Product_Tags')) {

abstract class RightPress_Condition_Cart_Item_Subtotals_Product_Tags extends RightPress_Condition_Cart_Item_Subtotals
{

    protected $key          = 'product_tags';
    protected $method       = 'numeric';
    protected $fields       = array(
        'before'    => array('product_tags'),
        'after'     => array('decimal'),
    );
    protected $main_field   = 'decimal';
    protected $position     = 50;

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

        return __('Cart item subtotal - Tags', 'rightpress');
    }





}
}
