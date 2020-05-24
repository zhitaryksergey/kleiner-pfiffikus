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
 * Condition Field: Multiselect - Product Categories
 *
 * @class RightPress_Condition_Field_Multiselect_Product_Categories
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Field_Multiselect_Product_Categories')) {

abstract class RightPress_Condition_Field_Multiselect_Product_Categories extends RightPress_Condition_Field_Multiselect
{

    protected $key                  = 'product_categories';
    protected $supports_hierarchy   = true;

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
     * Get child ids for fields that support hierarchy
     *
     * @access public
     * @param array $values
     * @return array
     */
    public function get_children($values)
    {

        $values_with_children = array();

        foreach ($values as $value) {
            $values_with_children[$value] = RightPress_Help::get_term_with_children($value, 'product_cat');
        }

        return $values_with_children;
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

        return RightPress_Conditions::get_all_product_categories($ids, $query);
    }

    /**
     * Get placeholder
     *
     * @access public
     * @return string
     */
    public function get_placeholder()
    {

        return __('Select product categories', 'rightpress');
    }





}
}
