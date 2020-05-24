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
 * Condition Field: Multiselect - Shipping Zones
 *
 * @class RightPress_Condition_Field_Multiselect_Shipping_Zones
 * @package RightPress
 * @author RightPress
 */
if (!class_exists('RightPress_Condition_Field_Multiselect_Shipping_Zones')) {

abstract class RightPress_Condition_Field_Multiselect_Shipping_Zones extends RightPress_Condition_Field_Multiselect
{

    protected $key = 'shipping_zones';

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

        return RightPress_Conditions::get_all_shipping_zones($ids, $query);
    }

    /**
     * Get placeholder
     *
     * @access public
     * @return string
     */
    public function get_placeholder()
    {

        return __('Select shipping zones', 'rightpress');
    }





}
}
