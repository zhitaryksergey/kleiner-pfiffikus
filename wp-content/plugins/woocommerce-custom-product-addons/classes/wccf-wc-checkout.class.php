<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Methods related to WooCommerce Checkout
 *
 * @class WCCF_WC_Checkout
 * @package WooCommerce Custom Fields
 * @author RightPress
 */
if (!class_exists('WCCF_WC_Checkout')) {

class WCCF_WC_Checkout
{

    private static $positions           = null;
    private static $positions_selectors = null;

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

        // Move product custom field values to order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);

        // Validate field data submitted on checkout
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_field_values'));

        // Move checkout custom field values to order meta
        add_action('woocommerce_checkout_create_order', array($this, 'add_order_meta'), 10, 2);

        // Set up Checkout page hooks for Checkout Fields
        add_action('template_redirect', array('WCCF_WC_Checkout', 'set_up_checkout_hooks'));

        // Add extra fees on checkout based on checkout fields
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_checkout_field_fees'));

        // Checkout field view update
        add_action('wp_ajax_wccf_update_checkout_field_view', array($this, 'maybe_update_checkout_field_view'));
        add_action('wp_ajax_nopriv_wccf_update_checkout_field_view', array($this, 'maybe_update_checkout_field_view'));
    }

    /**
     * Get list of field positions on Checkout page
     *
     * @access public
     * @return array
     */
    public static function get_positions()
    {

        // Define field positions
        if (self::$positions === null) {
            self::$positions = array(
                'woocommerce_checkout_before_customer_details'  => __('Above Customer Details', 'rp_wccf'),
                'woocommerce_checkout_after_customer_details'   => __('Below Customer Details', 'rp_wccf'),
                'woocommerce_before_checkout_billing_form'      => __('Above Billing Fields', 'rp_wccf'),
                'woocommerce_after_checkout_billing_form'       => __('Below Billing Fields', 'rp_wccf'),
                'woocommerce_before_checkout_shipping_form'     => __('Above Shipping Fields', 'rp_wccf'),
                'woocommerce_after_checkout_shipping_form'      => __('Below Shipping Fields', 'rp_wccf'),
                'woocommerce_before_order_notes'                => __('Above Order Notes', 'rp_wccf'),
                'woocommerce_after_order_notes'                 => __('Below Order Notes', 'rp_wccf'),
                'woocommerce_checkout_order_review_above'       => __('Above Order Review', 'rp_wccf'), // Hack using priority
                'woocommerce_checkout_order_review'             => __('Below Order Review', 'rp_wccf'),
            );
        }

        return self::$positions;
    }

    /**
     * Get position selector data by hook
     *
     * @access public
     * @param string $hook
     * @return array
     */
    public static function get_positions_selector($hook)
    {

        // Define field position selectors
        if (self::$positions_selectors === null) {
            self::$positions_selectors = array(
                'woocommerce_checkout_before_customer_details'  => array(
                    'method'    => 'before',
                    'selector'  => 'form.checkout #customer_details',
                ),
                'woocommerce_checkout_after_customer_details'   => array(
                    'method'    => 'after',
                    'selector'  => 'form.checkout #customer_details',
                ),
                'woocommerce_before_checkout_billing_form'      => array(
                    'method'    => 'before',
                    'selector'  => 'form.checkout .woocommerce-billing-fields__field-wrapper',
                ),
                'woocommerce_after_checkout_billing_form'       => array(
                    'method'    => 'after',
                    'selector'  => 'form.checkout .woocommerce-billing-fields__field-wrapper',
                ),
                'woocommerce_before_checkout_shipping_form'     => array(
                    'method'    => 'before',
                    'selector'  => 'form.checkout .woocommerce-shipping-fields__field-wrapper',
                ),
                'woocommerce_after_checkout_shipping_form'      => array(
                    'method'    => 'after',
                    'selector'  => 'form.checkout .woocommerce-shipping-fields__field-wrapper',
                ),
                'woocommerce_before_order_notes'                => array(
                    'method'    => 'prepend',
                    'selector'  => 'form.checkout .woocommerce-additional-fields',
                ),
                'woocommerce_after_order_notes'                 => array(
                    'method'    => 'append',
                    'selector'  => 'form.checkout .woocommerce-additional-fields',
                ),
                'woocommerce_checkout_order_review_above'       => array(  // Hack using priority
                    'method'    => 'prepend',
                    'selector'  => 'form.checkout #order_review',
                ),
                'woocommerce_checkout_order_review'             => array(
                    'method'    => 'append',
                    'selector'  => 'form.checkout #order_review',
                ),
            );
        }

        // Return position selector data by hook
        return isset(self::$positions_selectors[$hook]) ? self::$positions_selectors[$hook] : null;
    }

    /**
     * Set up checkout field hooks
     *
     * @access public
     * @return void
     */
    public static function set_up_checkout_hooks()
    {

        // Check if this is a Checkout page
        if (!is_checkout()) {
            return;
        }

        // Get all checkout fields that are going to be displayed
        $fields = WCCF_Checkout_Field_Controller::get_filtered();

        // Set up checkout field hooks
        self::set_up_checkout_hooks_by_fields($fields);

        // Get all user fields that are going to be displayed
        $fields = WCCF_User_Field_Controller::get_filtered();

        // Set up user field hooks
        self::set_up_checkout_hooks_by_fields($fields);
    }

    /**
     * Set up Checkout page hooks
     *
     * @access public
     * @param array $fields
     * @return void
     */
    public static function set_up_checkout_hooks_by_fields($fields)
    {

        // Track which hooks were already added
        $hooked = array();

        // Iterate over matched fields
        foreach ($fields as $field) {

            // Get current field display position (action hook)
            $hook = $field->get_position();

            // Check if this hook was already set up
            if (!in_array($hook, $hooked)) {

                // Set up hook
                self::set_up_single_checkout_hook($hook);

                // Track which hooks were already added
                $hooked[] = $hook;
            }
        }
    }

    /**
     * Set up single Checkout page hook
     *
     * @access public
     * @param string $hook
     * @return void
     */
    public static function set_up_single_checkout_hook($hook)
    {

        // Above order review
        if ($hook === 'woocommerce_checkout_order_review_above') {
            add_action('woocommerce_checkout_order_review', array('WCCF_WC_Checkout', 'display_frontend_fields_above'), 9);
        }
        // Below order review
        else if ($hook === 'woocommerce_checkout_order_review') {
            add_action($hook, array('WCCF_WC_Checkout', 'display_frontend_fields_default'), 11);
        }
        // All other hooks
        else {
            add_action($hook, array('WCCF_WC_Checkout', 'display_frontend_fields_default'));
        }
    }

    /**
     * Above order review position hack
     *
     * @access public
     * @return void
     */
    public static function display_frontend_fields_above()
    {

        self::display_frontend_fields('woocommerce_checkout_order_review_above');
    }

    /**
     * Default frontend field display hook
     *
     * @access public
     * @return void
     */
    public static function display_frontend_fields_default()
    {

        self::display_frontend_fields(current_filter());
    }

    /**
     * Add checkout fields
     *
     * @access public
     * @param string $current_filter
     * @return void
     */
    public static function display_frontend_fields($current_filter)
    {

        // Print user fields above checkout fields
        if (apply_filters('wccf_user_fields_above_checkout_fields', false, $current_filter)) {
            self::print_user_fields_on_checkout($current_filter);
        }

        // Print checkout fields
        self::print_checkout_fields_on_checkout($current_filter);

        // Print user fields below checkout fields
        if (!apply_filters('wccf_user_fields_above_checkout_fields', false, $current_filter)) {
            self::print_user_fields_on_checkout($current_filter);
        }
    }

    /**
     * Print checkout fields on checkout page
     *
     * @access public
     * @param string $current_filter
     * @return void
     */
    public static function print_checkout_fields_on_checkout($current_filter)
    {

        // Get checkout fields to print
        $fields = WCCF_Checkout_Field_Controller::get_filtered(null, array(), $current_filter);

        // Print checkout fields
        WCCF_Field_Controller::print_fields($fields);
    }

    /**
     * Print user fields on checkout page
     *
     * @access public
     * @param string $current_filter
     * @return void
     */
    public static function print_user_fields_on_checkout($current_filter)
    {

        // Get user fields to print
        $fields = WCCF_User_Field_Controller::get_filtered(null, array(), $current_filter);

        // Get current user id
        $user_id = is_user_logged_in() ? get_current_user_id() : null;

        // Print user fields
        WCCF_Field_Controller::print_fields($fields, $user_id);
    }

    /**
     * Move product custom field values from cart item meta to order item meta
     *
     * @access public
     * @param mixed $item
     * @param mixed $do_not_use_1
     * @param array $values
     * @param mixed $do_not_use_2
     * @return void
     */
    public function add_order_item_meta($item, $do_not_use_1, $values, $do_not_use_2)
    {

        // Check if any product field values were stored in cart
        if (!empty($values['wccf'])) {

            // Iterate over values and add them to order item meta
            foreach ($values['wccf'] as $field_id => $field_value) {

                // Get field
                $field = WCCF_Field_Controller::get($field_id, 'wccf_product_field');

                // Make sure this field exists
                if (!$field) {
                    continue;
                }

                // Store value
                $field->store_value($item, $field_value);
            }
        }
    }

    /**
     * Move product custom field values from cart item meta to order item meta
     * Pre WC 3.0 compatibility
     *
     * @access public
     * @param int $item_id
     * @param array $values
     * @return void
     */
    public function add_order_item_meta_legacy($item_id, $values)
    {

        $this->add_order_item_meta($item_id, null, $values, null);
    }

    /**
     * Validate field data submitted on checkout
     *
     * @access public
     * @param array $posted
     * @return void
     */
    public function validate_field_values($posted)
    {

        // Validate checkout fields
        WCCF_Field_Controller::validate_posted_field_values('checkout_field');

        // Validate user fields
        WCCF_Field_Controller::validate_posted_field_values('user_field');
    }

    /**
     * Move checkout custom field values to order meta
     *
     * @access public
     * @param mixed $order
     * @param array $posted
     * @return void
     */
    public function add_order_meta($order, $posted)
    {

        // Store posted checkout field values
        WCCF_Field_Controller::store_field_values($order, 'checkout_field', false, true);

        // Store posted user field values in both user meta (if logged in) and order meta
        WCCF_Field_Controller::store_field_values($order, 'user_field', false, true);
    }

    /**
     * Add extra fees on checkout based on checkout fields
     *
     * @access public
     * @param object $cart
     * @return void
     */
    public function add_checkout_field_fees($cart)
    {

        // Make sure this is a correct request
        if (is_admin() && !is_ajax()) {
            return;
        }

        // Parse data if needed
        if (isset($_POST['post_data'])) {
            parse_str($_POST['post_data'], $posted);
        }
        else {
            $posted = $_POST;
        }

        // Do not proceed if there are no checkout fields set
        if (!is_array($posted) || empty($posted['wccf']['checkout_field'])) {
            return;
        }

        // Get applicable checkout fields
        $fields = WCCF_Checkout_Field_Controller::get_filtered();

        // Get sanitized field values
        $values = WCCF_Field_Controller::sanitize_posted_field_values('checkout_field', array(
            'skip_invalid'      => true,
            'leave_no_trace'    => true,
            'fields'            => $fields,
            'posted'            => $posted,
        ));

        // Check if we have any values
        if (empty($values) || !is_array($values)) {
            return;
        }

        // Get cart total to base percentage fees/discounts on
        $cart_total = $cart->prices_include_tax ? ($cart->cart_contents_total + $cart->tax_total) : $cart->cart_contents_total;

        // Get fees
        $fees = WCCF_Pricing::get_checkout_fees($cart_total, $fields, $values);

        // Iterate over fees
        foreach ($fees as $fee) {

            $fee_label = false;

            // Ensure fee label is unique, otherwise it won't be added
            do {

                $is_unique = true;

                // Get fee label
                if ($fee_label === false) {
                    $fee_label = $fee['label'];
                }
                else {
                    $fee_label .= apply_filters('wccf_duplicate_fee_label_suffix', ' 2');
                }

                // Get fee id from fee label
                $fee_id = sanitize_title($fee_label);

                // Check if fee id is really unique
                foreach ($cart->get_fees() as $cart_fee) {
                    if ($cart_fee->id === $fee_id) {
                        $is_unique = false;
                        break;
                    }
                }
            }
            while (!$is_unique);

            // Check if fee is taxable and get tax class
            $taxable    = $fee['tax_class'] !== null;
            $tax_class  = ($taxable && $fee['tax_class'] !== 'standard') ? $fee['tax_class'] : '';

            // Add fee to cart
            $cart->add_fee($fee_label, $fee['amount'], $taxable, $tax_class);
        }
    }

    /**
     * Checkout field view update
     *
     * @access public
     * @return void
     */
    public function maybe_update_checkout_field_view()
    {

        try {

            // Default result
            $result = array('result' => 'success');

            // Get displayed field ids
            $displayed = !empty($_POST['field_ids']) ? array_map('intval', $_POST['field_ids']) : array();

            // Get ids of fields that are supposed to be displayed
            $fields = WCCF_Checkout_Field_Controller::get_filtered();
            $to_display = array_keys($fields);

            // Get fields to remove
            if ($remove = array_diff($displayed, $to_display)) {
                $result['remove'] = array_fill_keys($remove, null);
            }

            // Get fields to add
            if ($add = array_diff($to_display, $displayed)) {

                // Group by position
                $add_by_position = array();

                // Iterate over field ids to add
                foreach ($add as $field_id) {

                    // Get field position
                    $position = $fields[$field_id]->get_position();

                    // Add position to array if it does not exist yet
                    if (!isset($add_by_position[$position])) {

                        $add_by_position[$position] = array(
                            'position'  => WCCF_WC_Checkout::get_positions_selector($position),
                            'fields'    => array(),
                        );
                    }

                    // Get field html
                    ob_start();
                    WCCF_Field_Controller::print_fields(array($field_id => $fields[$field_id]));
                    $html = ob_get_clean();

                    // Add field to position
                    $add_by_position[$position]['fields'][] = array(
                        'id'    => $field_id,
                        'html'  => $html,
                    );
                }

                // Add for return
                if (!empty($add_by_position)) {
                    $result['add'] = $add_by_position;
                }
            }

            // Send result
            echo json_encode($result);
        }
        catch (Exception $e) {

            echo json_encode(array(
                'result' => 'error',
            ));
        }

        exit;
    }





}

WCCF_WC_Checkout::get_instance();

}
