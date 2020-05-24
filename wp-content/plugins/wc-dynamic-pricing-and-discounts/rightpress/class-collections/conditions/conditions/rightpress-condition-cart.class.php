<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RightPress_Condition')) {
    require_once('rightpress-condition.class.php');
}

/**
 * Condition Group: Cart
 *
 * @class RightPress_Condition_Cart
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Cart')) {

abstract class RightPress_Condition_Cart extends RightPress_Condition
{

    protected $group_key        = 'cart';
    protected $group_position   = 110;
    protected $is_cart          = true;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

        parent::__construct();

        $this->hook_group();
    }

    /**
     * Get group label
     *
     * @access public
     * @return string
     */
    public function get_group_label()
    {

        return __('Cart', 'rightpress');
    }





}
}
