<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RightPress_Condition_Field_Multiselect')) {
    require_once('rightpress-condition-field-multiselect.class.php');
}

/**
 * Condition Field: Multiselect - Product Variations
 *
 * @class RightPress_Condition_Field_Multiselect_Product_Variations
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Field_Multiselect_Product_Variations')) {

abstract class RightPress_Condition_Field_Multiselect_Product_Variations extends RightPress_Condition_Field_Multiselect
{

    protected $key = 'product_variations';

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
     * Load multiselect options
     *
     * @access public
     * @param array $ids
     * @param string $query
     * @return array
     */
    public function load_multiselect_options($ids = array(), $query = '')
    {

        return RightPress_Conditions::get_all_product_variations($ids, $query);
    }

    /**
     * Get placeholder
     *
     * @access public
     * @return string
     */
    public function get_placeholder()
    {

        return __('Select product variations', 'rightpress');
    }





}
}
