<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('WCCF_Post_Object_Controller')) {
    require_once('wccf-post-object-controller.class.php');
}

/**
 * Field controller class
 *
 * @class WCCF_Field_Controller
 * @package WooCommerce Custom Fields
 * @author RightPress
 */
if (!class_exists('WCCF_Field_Controller')) {

class WCCF_Field_Controller extends WCCF_Post_Object_Controller
{
    protected $supports_pricing     = false;
    protected $supports_position    = false;
    protected $supports_visibility  = false;
    protected $supports_quantity    = false;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set up post type controller
     *
     * @access public
     * @return void
     */
    public function plugins_loaded_own()
    {
        // Automatically disable new fields
        add_action('wccf_new_post_object', array($this, 'new_post_object_created'));

        // Process status change
        add_action('parse_request', array($this, 'process_status_change'), 999);
        add_action('admin_init', array($this, 'process_bulk_status_change'));

        // Process duplicate action
        add_action('parse_request', array($this, 'process_duplicate'), 999);

        // Process delete permanently
        add_action('parse_request', array($this, 'process_delete_permanently'), 999);

        // Object post trashed
        add_action('trashed_post', array($this, 'post_trashed'));

        // Show all fields in one page
        add_action('pre_get_posts', array($this, 'set_posts_per_page'));

        // Add class to actions meta box
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_status', array($this, 'add_field_status_meta_box_class'));
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_checkboxes', array($this, 'add_field_checkboxes_meta_box_class'));
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_settings', array($this, 'add_field_settings_meta_box_class'));
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_pricing', array($this, 'add_field_pricing_meta_box_class'));
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_options', array($this, 'add_field_options_meta_box_class'));
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_conditions', array($this, 'add_field_conditions_meta_box_class'));
        add_filter('postbox_classes_' . $this->get_post_type() . '_' . $this->get_post_type() . '_advanced', array($this, 'add_field_advanced_meta_box_class'));

        // Force post layout options
        add_filter('get_user_option_screen_layout_' . $this->get_post_type(), array($this, 'force_two_column_post_layout'), 999);
        add_filter('hidden_meta_boxes', array($this, 'force_metabox_display'), 999, 3);
        add_filter('hidden_columns', array($this, 'force_column_display'), 999, 3);
        add_filter('screen_options_show_screen', array($this, 'hide_screen_options'), 999, 2);

        // Pass field configuration to Javascript
        add_action('admin_enqueue_scripts', array($this, 'configuration_to_javascript'), 999);

        // Ajax handler - update field sort order
        add_action('wp_ajax_wccf_update_field_sort_order', array($this, 'ajax_update_field_sort_order'));

        // Ajax handler - validate field key
        add_action('wp_ajax_wccf_validate_field_key', array($this, 'ajax_validate_field_key'));

        // Recheck field sort order
        add_action('admin_init', array($this, 'recheck_field_sort_order'));

        // Sort fields as per sort order
        add_action('pre_get_posts', array($this, 'sort_fields'));

        // Field is trashed
        add_action('trashed_post', array($this, 'reset_sort_order'));

        // Print field description in field list
        add_filter('views_edit-' . $this->get_post_type(), array($this, 'maybe_print_admin_field_description'));

        // Filter out archived fields from all field list in admin
        add_filter('parse_query', array($this, 'remove_archived_fields_from_main_list'));
    }

    /**
     * Run on WP init
     *
     * @access public
     * @return void
     */
    public function on_init_own()
    {
        // This is overriden in context-specific classes
    }

    /**
     * Get taxonomies
     *
     * @access public
     * @return array
     */
    public function get_taxonomies()
    {
        return array(
            'status' => array(
                'singular'  => __('Status', 'rp_wccf'),
                'plural'    => __('Status', 'rp_wccf'),
                'all'       => __('All statuses', 'rp_wccf'),
            ),
            'field_type' => array(
                'singular'  => __('Field type', 'rp_wccf'),
                'plural'    => __('Field type', 'rp_wccf'),
                'all'       => __('All field types', 'rp_wccf'),
            ),
        );
    }

    /**
     * Define and return default statuses
     *
     * @access public
     * @return array
     */
    public static function get_status_list()
    {
        return array(
            'enabled'   => array(
                'title' => __('enabled', 'rp_wccf'),
            ),
            'disabled'    => array(
                'title' => __('disabled', 'rp_wccf'),
            ),
            'archived'    => array(
                'title' => __('archived', 'rp_wccf'),
            ),
        );
    }

    /**
     * Define and return default field types
     *
     * @access public
     * @return array
     */
    public static function get_field_type_list()
    {

        return array(
            'text' => array(
                'title'             => __('Text', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                ),
            ),
            'textarea' => array(
                'title'             => __('Text area', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                ),
            ),
            'password' => array(
                'title'             => __('Password', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                ),
            ),
            'email' => array(
                'title'             => __('Email', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                ),
            ),
            'number' => array(
                'title'             => __('Number', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                    'less_than', 'less_or_equal_to', 'more_than', 'more_or_equal',
                ),
            ),
            'decimal' => array(
                'title'             => __('Decimal', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                    'less_than', 'less_or_equal_to', 'more_than', 'more_or_equal',
                ),
            ),
            'date' => array(
                'title'             => __('Date picker', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                    'less_than', 'less_or_equal_to', 'more_than', 'more_or_equal',
                ),
            ),
            'time' => array(
                'title'             => __('Time picker', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                    'less_than', 'less_or_equal_to', 'more_than', 'more_or_equal',
                ),
            ),
            'datetime' => array(
                'title'             => __('Date/time picker', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'begins_with', 'ends_with', 'equals', 'does_not_equal',
                    'less_than', 'less_or_equal_to', 'more_than', 'more_or_equal',
                ),
            ),
            'color' => array(
                'title'             => __('Color picker', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'equals', 'does_not_equal',
                ),
            ),
            'select' => array(
                'title'             => __('Select', 'rp_wccf'),
                'requires_options'  => true,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'equals', 'does_not_equal', 'less_than', 'less_or_equal_to',
                    'more_than', 'more_or_equal',
                ),
            ),
            'multiselect' => array(
                'title'             => __('Multiselect', 'rp_wccf'),
                'requires_options'  => true,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'equals', 'does_not_equal',
                ),
            ),
            'checkbox' => array(
                'title'             => __('Checkboxes', 'rp_wccf'),
                'requires_options'  => true,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'equals', 'does_not_equal', 'is_checked', 'is_not_checked'
                ),
            ),
            'radio' => array(
                'title'             => __('Radio buttons', 'rp_wccf'),
                'requires_options'  => true,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty', 'contains', 'does_not_contain',
                    'equals', 'does_not_equal', 'less_than', 'less_or_equal_to',
                    'more_than', 'more_or_equal', 'is_checked', 'is_not_checked'
                ),
            ),
            'file' => array(
                'title'             => __('File upload', 'rp_wccf'),
                'requires_options'  => false,
                'other_field_condition_methods' => array(
                    'is_empty', 'is_not_empty',
                ),
            ),
            'heading' => array(
                'title'                         => __('Heading', 'rp_wccf'),
                'requires_options'              => false,
                'other_field_condition_methods' => null,
                'is_page_element'               => true,
            ),
            'separator' => array(
                'title'                         => __('Separator', 'rp_wccf'),
                'requires_options'              => false,
                'other_field_condition_methods' => null,
                'is_page_element'               => true,
            ),
        );
    }

    /**
     * Check if field type requires options
     *
     * @access public
     * @param string $field_type
     * @return bool
     */
    public static function field_type_requires_options($field_type)
    {
        $field_types = self::get_field_type_list();

        if (!isset($field_types[$field_type]) || !isset($field_types[$field_type]['requires_options'])) {
            return false;
        }

        return $field_types[$field_type]['requires_options'];
    }

    /**
     * Add list columns
     *
     * @access public
     * @param array $columns
     * @return array
     */
    public function add_list_columns($columns)
    {
        $columns['wccf_label']       = __('Field', 'rp_wccf');
        $columns['wccf_key']         = __('Key', 'rp_wccf');
        $columns['wccf_field_type']  = __('Field type', 'rp_wccf');
        $columns['wccf_settings']    = __('Settings', 'rp_wccf');
        $columns['wccf_status']      = __('Status', 'rp_wccf');
        $columns['wccf_sort']        = __('Sort', 'rp_wccf');

        return $columns;
    }

    /**
     * Print list column value
     *
     * @access public
     * @param object $object
     * @param array $column_key
     * @return array
     */
    public function print_list_column_value($object, $column_key)
    {
        switch ($column_key) {

            case 'wccf_label':

                // Get label
                $label = $object->get_label();
                $label = !empty($label) ? $label : __('(no label)', 'rp_wccf');

                // Check if post is trashed
                if (!RightPress_Help::post_is_trashed($object->get_id())) {
                    self::print_link_to_post($object->get_id(), $label, '<span class="wccf_row_label_cell">', '</span>');
                }
                else {
                    echo $label;
                }

                // Maybe display private note
                $private_note = $object->get_private_note();

                if (!RightPress_Help::is_empty($private_note)) {
                    echo '<span class="wccf_row_private_note">' . $private_note . '</span>';
                }

                // Print actions
                $this->print_post_actions();
                break;

            case 'wccf_key':

                // Key is not applicable to page elements
                if (!$object->is_page_element()) {
                    echo '<span class="wccf_row_key">' . $object->get_key() . '</span>';
                }

                break;

            case 'wccf_field_type':
                $trashed = RightPress_Help::post_is_trashed($object->get_id()) ? '&amp;post_status=trash' : '';
                echo '<a class="wccf_field_type' . '" href="edit.php?s&amp;post_type=' . $this->get_post_type() . '&amp;' . $this->get_post_type() . '_field_type=' . $object->get_field_type() . $trashed . '">' . $object->get_field_type_title() . '</a>';
                break;

            case 'wccf_settings':

                // Enabled class
                $class = 'wccf_row_settings_icon_enabled';

                // Some icons are not applicable to page elements
                if (!$object->is_page_element()) {

                    // Required
                    $is_required = $object->is_required();
                    echo '<span class="wccf_row_settings_required wccf_row_settings_icon ' . ($is_required ? $class : '') . '"><i class="fa fa-check wccf-tip" aria-hidden="true" title="' . ($is_required ? __('Required', 'rp_wccf') : __('Not required', 'rp_wccf')) . '"></i></span>';

                    // Visibility
                    if ($this->supports_visibility()) {
                        $is_public = $object->is_public();
                        echo '<span class="wccf_row_settings_visibility wccf_row_settings_icon ' . ($is_public ? $class : '') . '"><i class="fa fa-eye wccf-tip" aria-hidden="true" title="' . ($is_public ? __('Visible to customers', 'rp_wccf') : __('Not visible to customers', 'rp_wccf')) . '"></i></span>';
                    }

                    // Quantity based
                    if ($this->supports_quantity()) {
                        $is_quantity_based = $object->is_quantity_based();
                        echo '<span class="wccf_row_settings_quantity_based wccf_row_settings_icon ' . ($is_quantity_based ? $class : '') . '"><i class="fa fa-bars wccf-tip" aria-hidden="true" title="' . ($is_quantity_based ? __('Multiple fields based on quantity', 'rp_wccf') : __('One field only despite quantity', 'rp_wccf')) . '"></i></span>';
                    }

                    // Pricing
                    if ($this->supports_pricing()) {

                        // Check if field has pricing
                        $has_pricing = $object->has_pricing();

                        // Get currency
                        $currency = strtolower(get_woocommerce_currency());
                        $currency = in_array($currency, array('gbp', 'eur')) ? $currency : 'usd';

                        // Get title based on field context
                        if ($object->context_is('checkout_field')) {
                            $title = ($has_pricing ? __('Adds fee', 'rp_wccf') : __('Does not add fee', 'rp_wccf'));
                        }
                        else {
                            $title = ($has_pricing ? __('Changes product price', 'rp_wccf') : __('Does not change product price', 'rp_wccf'));
                        }

                        // Print icon
                        echo '<span class="wccf_row_settings_pricing wccf_row_settings_icon ' . ($has_pricing ? $class : '') . '"><i class="fa fa-' . $currency . ' wccf-tip" aria-hidden="true" title="' . $title . '"></i></span>';
                    }
                }

                // Conditions
                $has_conditions = $object->has_conditions();
                echo '<span class="wccf_row_settings_conditions wccf_row_settings_icon ' . ($has_conditions ? $class : '') . '"><i class="fa fa-cogs wccf-tip" aria-hidden="true" title="' . ($has_conditions ? __('Has conditions', 'rp_wccf') : __('Does not have conditions', 'rp_wccf')) . '"></i></span>';

                break;

            case 'wccf_status':
                $trashed = RightPress_Help::post_is_trashed($object->get_id()) ? '&amp;post_status=trash' : '';
                echo '<a class="wccf_status_' . $object->get_status() . '" href="edit.php?s&amp;post_type=' . $this->get_post_type() . '&amp;' . $this->get_post_type() . '_status=' . $object->get_status() . $trashed . '"><span>' . $object->get_status_title() . '</span></a>';
                break;

            case 'wccf_sort':
                if (!RightPress_Help::post_is_trashed($object->get_id()) && !isset($_GET['s'])) {
                    echo '<div class="wccf_post_sort_handle"><i class="fa fa-bars" aria-hidden="true"></i></div>';
                }
                break;

            default:
                break;
        }
    }

    /**
     * Possibly disable new field
     *
     * @access public
     * @param int $post_id
     * @return void
     */
    public function new_post_object_created($post_id)
    {
        // Check if this is our field type
        if (RightPress_Help::post_type_is($post_id, $this->get_post_type())) {

            // Allow developers to override
            $disable = apply_filters('wccf_disable_new_fields', true);
            $disable = apply_filters('rp_wccf_disable_new_fields', $disable); // Legacy filter

            if ($disable) {

                // Get object
                $object = self::get($post_id);

                // Check object
                if ($object) {

                    // Disable new field
                    $object->disable();
                }
            }
        }
    }

    /**
     * Ensure unique field key
     *
     * @access public
     * @param string $key
     * @return string
     */
    public function ensure_unique_key($key)
    {
        while ($this->key_exists($key)) {
            $key .= '_' . RightPress_Help::get_hash();
        }

        return $key;
    }

    /**
     * Check if field key exists
     *
     * @access public
     * @param string $key
     * @param int $current_post_id
     * @return bool
     */
    public function key_exists($key, $current_post_id = null)
    {
        $query = new WP_Query(array(
            'post_type'     => $this->get_post_type(),
            'fields'        => 'ids',
            'post_status'   => array('publish', 'trash'),
            'meta_query'    => array(
                array(
                    'key'       => 'key',
                    'value'     => $key,
                    'compare'   => '=',
                ),
            ),
        ));

        // Check if only one field with such key exists and it is current post
        if ($current_post_id && count($query->posts) === 1) {
            return (int) $current_post_id !== (int) array_pop($query->posts);
        }

        return empty($query->posts) ? false : true;
    }

    /**
     * Process status change request
     *
     * @access public
     * @return void
     */
    public function process_status_change()
    {
        // Check request and get object
        $object = $this->get_valid_list_action_object('wccf_status_change');

        if (!$object) {
            return;
        }

        // User not authorized
        if (!WCCF::is_authorized('manage_fields')) {
            return;
        }

        // Get status list
        $status_list = self::get_status_list();
        $new_status = $_REQUEST['wccf_status_change'];

        // Make sure status is valid
        if (!isset($status_list[$new_status])) {
            return;
        }

        // Change status
        if ($new_status === 'enabled') {
            $object->enable(null, true);
        }
        else if ($new_status === 'disabled') {
            $object->disable(null, true);
        }
        else if ($new_status === 'archived') {
            $object->archive(null, true);
        }

        // Preserve error messages so they survive redirect
        WCCF_Settings::preserve_error_messages();

        // Get redirect URL
        if ($new_status !== 'archived') {
            $redirect_url = remove_query_arg(array('wccf_status_change', 'wccf_object_id'));
        }
        else {
            $post_type = $object->get_post_type();
            $redirect_url = $this->get_archive_list_view($post_type);
        }

        // Redirect user and exit
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Process bulk status change request
     *
     * @access public
     * @return void
     */
    public function process_bulk_status_change()
    {
        // Not our request
        if (empty($_REQUEST['action']) && empty($_REQUEST['action2'])) {
            return;
        }

        // Get action
        $action = ($_REQUEST['action'] != -1) ? $_REQUEST['action'] : $_REQUEST['action2'];

        // Not our action
        if (!in_array($action, array('wccf_enable_field', 'wccf_disable_field'), true)) {
            return;
        }

        // Check post type and if any fields were selected
        if (!isset($_REQUEST['post_type']) || $_REQUEST['post_type'] !== $this->get_post_type() || empty($_REQUEST['post'])) {
            return;
        }

        // Check if user is allowed to manage fields
        if (!WCCF::is_authorized('manage_fields')) {
            return;
        }

        // Iterate over field ids
        foreach ((array) $_REQUEST['post'] as $field_id) {

            // Load field
            $field = WCCF_Field_Controller::cache($field_id);

            // Enable field
            if ($field && $action === 'wccf_enable_field' && $field->get_status() !== 'enabled') {
                $field->enable(null, true);
            }
            // Disable field
            else if ($field && $action === 'wccf_disable_field' && $field->get_status() !== 'disabled') {
                $field->disable(null, true);
            }
        }

        // Preserve error messages so they survive redirect
        WCCF_Settings::preserve_error_messages();
    }

    /**
     * Process duplicate request
     *
     * @access public
     * @return void
     */
    public function process_duplicate()
    {
        // Get reference object
        $reference_object = $this->get_valid_list_action_object('wccf_duplicate');

        // Check if request is valid duplicate request
        if (!$reference_object) {
            return;
        }

        // User not authorized
        if (!WCCF::is_authorized('manage_fields')) {
            return;
        }

        // Attempt to duplicate field
        try {

            // Make sure that object is not archived
            if (method_exists($reference_object, 'is_archived') && $reference_object->is_archived()) {
                throw new Exception(__('Changes are not allowed to archived fields.', 'rp_wccf'));
            }

            // Make sure that object is configurable
            if (!$reference_object->is_configurable()) {
                $error_message = __('Error:', 'rp_wccf') . ' ' . __('Field is not configurable.', 'rp_wccf') . ' ' . __('Field was not duplicated.', 'rp_wccf');
                throw new Exception($error_message);
            }

            // Check if field is page element
            $is_page_element = $reference_object->is_page_element();

            // Make sure that field key is present for regular fields
            if (!$is_page_element) {
                if (!isset($_REQUEST['wccf_field_key']) || RightPress_Help::is_empty($_REQUEST['wccf_field_key']) || !is_string($_REQUEST['wccf_field_key'])) {
                    throw new Exception(__('Error: New field key is not valid.', 'rp_wccf'));
                }
            }

            // Get field key
            if ($is_page_element) {
                $key = RightPress_Help::get_hash();
            }
            else {
                $key = WCCF_Field::filter_key_value_characters($_REQUEST['wccf_field_key']);
            }

            // Make sure that field key does not exist
            if ($this->key_exists($key)) {
                throw new Exception(__('Error: New field key is not unique.', 'rp_wccf'));
            }

            // Make sure that the field key does not end with an underscore followed by a number (related to issue #313)
            if (preg_match('/_\d+$/', $key)) {
                throw new Exception(__('Error: Field key can not end with an underscore followed by a number.', 'rp_wccf'));
            }

            // Make sure that the field key does not start with either "id_" or "data_" (related to issue #317)
            if (preg_match('/^id_/i', $key) || preg_match('/^data_/i', $key)) {
                throw new Exception(__('Error: Field key can not start with "id_" or "data_".', 'rp_wccf'));
            }

            // Insert new post
            $post_id = wp_insert_post(array(
                'post_title'        => '',
                'post_content'      => '',
                'post_name'         => '',
                'post_status'       => 'draft',
                'post_type'         => $reference_object->get_post_type(),
                'ping_status'       => 'closed',
                'comment_status'    => 'closed',
            ));

            // Check if post was inserted successfully
            if (is_wp_error($post_id) || empty($post_id)) {
                $error_message = __('Error:', 'rp_wccf') . ' ' . __('Failed inserting new WordPress custom post to store field settings.', 'rp_wccf') . ' ' . __('Field was not duplicated.', 'rp_wccf');
                throw new Exception($error_message);
            }

            // Retrieve class name by post type
            $class_name = self::$post_types[$reference_object->get_post_type()];

            // Initialize new object
            $new_object = new $class_name($post_id);

            // Disable
            $new_object->disable();

            // Save object configuration
            $new_object->save_configuration(array(
                'post_ID'       => $post_id,
                'wccf_settings' => array_merge($reference_object->get_duplicate_values(), array('key' => $key)),
            ), 'duplicate');

        }
        catch (Exception $e) {

            // Add admin notice
            add_settings_error(
                'wccf',
                'failed_duplicating_field',
                $e->getMessage()
            );
        }

        // Preserve error messages so they survive redirect
        WCCF_Settings::preserve_error_messages();

        // Get redirect URL (redirecting to default list page without any
        // filters to make sure duplicate is displayed and not filtered out)
        $redirect_url = admin_url('/edit.php?post_type=' . $this->get_post_type());

        // Redirect user and exit
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Process delete permanently
     *
     * @access public
     * @return void
     */
    public function process_delete_permanently()
    {
        // Get reference object
        $reference_object = $this->get_valid_list_action_object('wccf_delete_permanently');

        // Check if request is valid delete request
        if (!$reference_object) {
            return;
        }

        // User not authorized
        if (!WCCF::is_authorized('manage_fields')) {
            return;
        }

        // Get post type
        $post_type = $reference_object->get_post_type();

        // Delete field
        wp_delete_post($reference_object->get_id(), true);

        // Add notice
        add_settings_error(
            'wccf',
            'field_permanently_deleted',
            sprintf(__('%s deleted.', 'rp_wccf'), WCCF_Post_Object_Controller::get_general_short_name($post_type)),
            'updated'
        );

        // Preserve error messages so they survive redirect
        WCCF_Settings::preserve_error_messages();

        // Get redirect URL (redirecting to archived list view for current field type)
        $redirect_url = $this->get_archive_list_view($post_type);

        // Redirect user and exit
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Object trashed - disable it if object uses statuses
     *
     * @access public
     * @param int $post_id
     * @return void
     */
    public function post_trashed($post_id)
    {
        // Make sure it's our post type
        if (!RightPress_Help::post_type_is($post_id, $this->get_post_type())) {
            return;
        }

        // Load object
        $object = self::get($post_id);

        // No object?
        if (!$object) {
            return;
        }

        // Disable object if it is configurable and has method disable
        if ($object->is_configurable()) {
            $object->disable();
        }
    }

    /**
     * Modify actions list
     *
     * @access protected
     * @param array $actions
     * @return array
     */
    protected function modify_action_list($actions = array())
    {
        global $post;

        // General object name
        $object_name = self::get_general_short_name($this->get_post_type());

        // Rename save action
        if (isset($actions['save'])) {
            $actions['save'] = __('Save', 'rp_wccf') . ' ' . $object_name;
        }

        // Get status
        $object = self::get($post->ID);
        $status = $object ? $object->get_status() : null;

        // New item?
        if (empty($status)) {
            return $actions;
        }

        // Archived item?
        if (method_exists($object, 'is_archived') && $object->is_archived()) {
            return array('object_archived' => __('Changes not allowed', 'rp_wccf'));
        }

        // Enable
        if ($status === 'disabled') {
            $actions['enable'] = __('Enable', 'rp_wccf') . ' ' . $object_name;
        }

        // Disable
        if ($status === 'enabled') {
            $actions['disable'] = __('Disable', 'rp_wccf') . ' ' . $object_name;
        }

        // Archive
        if ($status !== 'archived' && !$object->is_page_element()) {
            $actions['archive'] = __('Archive', 'rp_wccf') . ' ' . $object_name;
        }

        return $actions;
    }

    /**
     * Allow child classes to modify list view actions list
     *
     * @access protected
     * @param array $actions
     * @return array
     */
    protected function modify_list_view_actions_list($actions)
    {
        global $post;

        if ($post->post_status !== 'trash' && $this->is_configurable()) {

            // Get object
            $object = self::get($post->ID);

            // Check if object was loaded and is not archived
            if ($object) {

                // Get object id
                $object_id = $object->get_id();

                // Check if object is archived
                $is_archived = $object->is_archived();

                // Object is not archived
                if (!$is_archived) {

                    // Check if object is enabled
                    $is_enabled = $object->is_enabled();

                    // Duplicate
                    $url = WCCF_Field_Controller::get_duplicate_url($object_id);
                    $actions = RightPress_Help::insert_to_array_after_key($actions, 'edit', array(
                        'duplicate' => '<a href="' . $url . '" class="wccf_duplicate_field" title="' .  __('Duplicate', 'rp_wccf') . '">' . __('Duplicate', 'rp_wccf') . '</a>',
                    ));

                    // Archive
                    if (!$object->is_page_element()) {
                        $url = WCCF_Field_Controller::get_archive_url($object_id);
                        $actions = RightPress_Help::insert_to_array_after_key($actions, 'duplicate', array(
                            'archive' => '<a href="' . $url . '" title="' .  __('Archive', 'rp_wccf') . '">' . __('Archive', 'rp_wccf') . '</a>',
                        ));
                    }

                    // Enable
                    if (!$is_enabled) {
                        $url = WCCF_Field_Controller::get_enable_url($object_id);
                        $actions = RightPress_Help::insert_to_array_after_key($actions, 'duplicate', array(
                            'enable' => '<a href="' . $url . '" title="' .  __('Enable', 'rp_wccf') . '">' . __('Enable', 'rp_wccf') . '</a>',
                        ));
                    }

                    // Disable
                    if ($is_enabled) {
                        $url = WCCF_Field_Controller::get_disable_url($object_id);
                        $actions = RightPress_Help::insert_to_array_after_key($actions, 'duplicate', array(
                            'disable' => '<a href="' . $url . '" title="' .  __('Disable', 'rp_wccf') . '">' . __('Disable', 'rp_wccf') . '</a>',
                        ));
                    }

                }

                // Object is archived
                if ($is_archived) {

                    // View
                    $url = 'post.php?post=' . $object_id . '&amp;action=edit';
                    $actions = RightPress_Help::insert_to_array_after_key($actions, 'edit', array(
                        'view' => '<a href="' . $url . '" title="' .  __('View', 'rp_wccf') . '">' . __('View', 'rp_wccf') . '</a>',
                    ));

                    // Unset edit link
                    unset($actions['edit']);

                    // Unset trash link
                    unset($actions['trash']);

                    // Delete Permanently
                    $url = WCCF_Field_Controller::get_delete_permanently_url($object_id, $object->get_post_type());
                    $actions = RightPress_Help::insert_to_array_after_key($actions, 'view', array(
                        'trash' => '<a class="submitdelete wccf_delete_permanently" href="' . $url . '" title="' .  __('Delete Permanently', 'rp_wccf') . '">' . __('Delete Permanently', 'rp_wccf') . '</a>',
                    ));
                }
            }
        }

        return $actions;
    }

    /**
     * Get duplicate action url
     *
     * @access public
     * @param int $object_id
     * @return string
     */
    public static function get_duplicate_url($object_id)
    {
        return add_query_arg(array(
            'wccf_duplicate'    => '1',
            'wccf_object_id'    => $object_id,
        ));
    }

    /**
     * Get archive action url
     *
     * @access public
     * @param int $object_id
     * @return string
     */
    public static function get_archive_url($object_id)
    {
        return WCCF_Field_Controller::get_status_change_url($object_id, 'archived');
    }

    /**
     * Get enable action url
     *
     * @access public
     * @param int $object_id
     * @return string
     */
    public static function get_enable_url($object_id)
    {
        return WCCF_Field_Controller::get_status_change_url($object_id, 'enabled');
    }

    /**
     * Get disable action url
     *
     * @access public
     * @param int $object_id
     * @return string
     */
    public static function get_disable_url($object_id)
    {
        return WCCF_Field_Controller::get_status_change_url($object_id, 'disabled');
    }

    /**
     * Get status change action url
     *
     * @access public
     * @param int $object_id
     * @param string $new_status
     * @return string
     */
    public static function get_status_change_url($object_id, $new_status)
    {
        return add_query_arg(array(
            'wccf_status_change'    => $new_status,
            'wccf_object_id'        => $object_id,
        ));
    }

    /**
     * Get delete permanently action url
     *
     * @access public
     * @param int $object_id
     * @param string $post_type
     * @return string
     */
    public static function get_delete_permanently_url($object_id, $post_type)
    {
        // Format and return url
        return admin_url('edit.php?s&post_type=' . $post_type . '&' . $post_type . '_status=archived&wccf_delete_permanently=1&wccf_object_id=' . $object_id);
    }

    /**
     * Add meta boxes
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function add_meta_boxes_own($post)
    {
        // Actions
        add_meta_box(
            $this->get_post_type() . '_actions',
            __('Field Actions', 'rp_wccf'),
            array($this, 'render_meta_box_actions'),
            $this->get_post_type(),
            'side',
            'high'
        );

        // Status
        add_meta_box(
            $this->get_post_type() . '_status',
            __('Status', 'rp_wccf'),
            array($this, 'render_meta_box_status'),
            $this->get_post_type(),
            'side',
            'high'
        );

        // Checkboxes
        add_meta_box(
            $this->get_post_type() . '_checkboxes',
            __('Checkboxes', 'rp_wccf'),
            array($this, 'render_meta_box_checkboxes'),
            $this->get_post_type(),
            'side',
            'high'
        );

        // Settings
        add_meta_box(
            $this->get_post_type() . '_settings',
            __('Settings', 'rp_wccf'),
            array($this, 'render_meta_box_settings'),
            $this->get_post_type(),
            'normal',
            'high'
        );

        // Pricing
        if ($this->supports_pricing()) {
            add_meta_box(
                $this->get_post_type() . '_pricing',
                __('Pricing', 'rp_wccf'),
                array($this, 'render_meta_box_pricing'),
                $this->get_post_type(),
                'normal',
                'low'
            );
        }

        // Options
        add_meta_box(
            $this->get_post_type() . '_options',
            __('Options', 'rp_wccf'),
            array($this, 'render_meta_box_options'),
            $this->get_post_type(),
            'normal',
            'low'
        );

        // Conditions
        add_meta_box(
            $this->get_post_type() . '_conditions',
            __('Conditions', 'rp_wccf'),
            array($this, 'render_meta_box_conditions'),
            $this->get_post_type(),
            'normal',
            'low'
        );

        // Advanced
        add_meta_box(
            $this->get_post_type() . '_advanced',
            __('Advanced', 'rp_wccf'),
            array($this, 'render_meta_box_advanced'),
            $this->get_post_type(),
            'normal',
            'low'
        );
    }

    /**
     * Render edit page meta box Status content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_status($post)
    {
        // Render meta box content
        $this->render_meta_box('status', $post, 'fields/status');
    }

    /**
     * Render edit page meta box Checkboxes content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_checkboxes($post)
    {
        // Render meta box content
        $this->render_meta_box('checkboxes', $post, 'fields/checkboxes');
    }

    /**
     * Render edit page meta box Settings content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_settings($post)
    {
        // Get context
        $context = $this->get_context();

        // Define variables to be passed into view
        $variables = array(
            'context' => $context,
        );

        // Render meta box content
        $this->render_meta_box('settings', $post, 'fields/settings', $variables);

        // Enqueue templates to be rendered in footer
        add_action('admin_footer', array($this, 'render_templates_in_footer'));
    }

    /**
     * Render edit page meta box Pricing content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_pricing($post)
    {
        // Get context
        $context = $this->get_context();

        // Define variables to be passed into view
        $variables = array(
            'context' => $context,
        );

        // Render meta box content
        $this->render_meta_box('pricing', $post, 'fields/pricing', $variables);
    }

    /**
     * Render edit page meta box Options content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_options($post)
    {
        // Render meta box content
        $this->render_meta_box('options', $post, 'fields/options');
    }

    /**
     * Render edit page meta box Conditions content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_conditions($post)
    {
        // Render meta box content
        $this->render_meta_box('conditions', $post, 'fields/conditions');
    }

    /**
     * Render edit page meta box Advanced content
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function render_meta_box_advanced($post)
    {
        // Render meta box content
        $this->render_meta_box('advanced', $post, 'fields/advanced');
    }

    /**
     * Render templates in footer
     *
     * @access public
     * @return void
     */
    public function render_templates_in_footer()
    {
        global $post;

        // Get context
        $context = $this->get_context();

        // Get current field id
        $field_id = (is_object($post) && !empty($post->ID)) ? $post->ID : null;

        // Load form builder templates
        include WCCF_PLUGIN_PATH . 'views/fields/templates.php';
    }

    /**
     * Set posts per page
     *
     * @access public
     * @param object $query
     * @return void
     */
    public function set_posts_per_page($query)
    {
        global $typenow;

        if (is_admin() && $query->is_main_query() && $typenow === $this->get_post_type()) {
            $query->set('posts_per_page', '-1');
        }
    }

    /**
     * Add class to field status meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_status_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_status_meta_box');
        return $classes;
    }

    /**
     * Add class to field checkboxes meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_checkboxes_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_checkboxes_meta_box');
        return $classes;
    }

    /**
     * Add class to field settings meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_settings_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_settings_meta_box');
        return $classes;
    }

    /**
     * Add class to field pricing meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_pricing_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_pricing_meta_box');
        return $classes;
    }

    /**
     * Add class to field options meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_options_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_options_meta_box');
        return $classes;
    }

    /**
     * Add class to field conditions meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_conditions_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_conditions_meta_box');
        return $classes;
    }

    /**
     * Add class to field advanced meta boxes
     *
     * @access public
     * @param array $classes
     * @return array
     */
    public function add_field_advanced_meta_box_class($classes)
    {
        array_push($classes, 'wccf_field_advanced_meta_box');
        return $classes;
    }

    /**
     * Force two column post layout
     *
     * @access public
     * @param array $result
     * @return array
     */
    public function force_two_column_post_layout($result)
    {

        return '2';
    }

    /**
     * Force metabox display
     *
     * @access public
     * @param array $hidden
     * @param object $screen
     * @param bool $use_defaults
     * @return array
     */
    public function force_metabox_display($hidden, $screen, $use_defaults)
    {

        // Force our own metabox display
        if (is_a($screen, 'WP_Screen') && isset($screen->post_type) && $screen->post_type === $this->get_post_type()) {
            if (is_array($hidden)) {
                foreach ($hidden as $key => $value) {
                    if (RightPress_Help::string_begins_with_substring($value, 'wccf_')) {
                        unset($hidden[$key]);
                    }
                }
            }
        }

        return $hidden;
    }

    /**
     * Force column display
     *
     * @access public
     * @param array $hidden
     * @param object $screen
     * @param bool $use_defaults
     * @return array
     */
    public function force_column_display($hidden, $screen, $use_defaults)
    {

        // Force our own column display
        if (is_a($screen, 'WP_Screen') && isset($screen->post_type) && $screen->post_type === $this->get_post_type()) {
            if (is_array($hidden)) {
                foreach ($hidden as $key => $value) {
                    if (RightPress_Help::string_begins_with_substring($value, 'wccf_')) {
                        unset($hidden[$key]);
                    }
                }
            }
        }

        return $hidden;
    }

    /**
     * Hide screen options
     *
     * @access public
     * @param bool $show
     * @param object $screen
     * @return array
     */
    public function hide_screen_options($show, $screen)
    {

        // Force our own metabox display
        if (is_a($screen, 'WP_Screen') && isset($screen->post_type) && $screen->post_type === $this->get_post_type()) {
            $show = false;
        }

        return $show;
    }

    /**
     * Check if field type supports pricing
     *
     * @access public
     * @return bool
     */
    public function supports_pricing()
    {
        return $this->supports_pricing;
    }

    /**
     * Check if field type supports position
     *
     * @access public
     * @return bool
     */
    public function supports_position()
    {
        return $this->supports_position;
    }

    /**
     * Check if field type supports visibility
     *
     * @access public
     * @return bool
     */
    public function supports_visibility()
    {
        return $this->supports_visibility;
    }

    /**
     * Check if field type supports quantity
     *
     * @access public
     * @return bool
     */
    public function supports_quantity()
    {
        return $this->supports_quantity;
    }

    /**
     * Pass object configuration to JavaScript
     *
     * @access public
     * @return void
     */
    public function configuration_to_javascript()
    {
        global $post;
        $screen = get_current_screen();

        // Check if post type is our and it is a single post edit page
        if (gettype($screen) !== 'object' || !isset($screen->base) || $screen->base !== 'post' || !isset($screen->id) || $screen->id !== $this->get_post_type()) {
            return;
        }

        // Get object
        if (!($object = self::get($post->ID))) {
            return;
        }

        // Get conditions
        $conditions = $object->get_conditions();

        // Add options and conditions
        wp_localize_script('wccf-backend-scripts', 'wccf_fb', array(
            'options'       => $object->get_options(),
            'conditions'    => $conditions,
        ));

        // Add multiselect field selected option labels
        wp_localize_script('wccf-backend-scripts', 'wccf_fb_multiselect_options', array(
            'conditions' => WCCF_Controller_Conditions::get_conditions_multiselect_field_option_labels($conditions),
        ));
    }

    /**
     * Get all fields
     *
     * @access public
     * @param string $post_type
     * @param array $status
     * @param array $key
     * @return array
     */
    public static function get_all_fields($post_type, $status = array(), $key = array())
    {
        $status = (array) $status;
        $key    = (array) $key;

        $meta_query = array();
        $tax_query  = array();

        // Get enabled fields by default if status list is empty
        if (empty($status)) {
            $status[] = 'enabled';
        }

        // Get only fields with specific field keys
        if (!empty($key)) {
            $meta_query[] = array(
                array(
                    'key'       => 'key',
                    'value'     => $key,
                    'compare'   => 'IN',
                ),
            );
        }

        // Build tax query
        $tax_query[] = array(
            'taxonomy'  => $post_type . '_status',
            'field'     => 'slug',
            'terms'     => $status,
            'operator'  => 'IN',
        );

        // Get all objects of this type
        return self::get_all_objects($post_type, false, $meta_query, $tax_query);
    }

    /**
     * Update field sort order
     * Note: this handler is actually added multiple times (there are multiple
     * instances of this class) but is only executed once since we exit from it
     *
     * @access public
     * @return void
     */
    public function ajax_update_field_sort_order()
    {
        // User not authorized
        if (!WCCF::is_authorized('manage_fields')) {
            echo json_encode(array(
                'result' => 'error',
            ));
            exit;
        }

        // Check if field sort order is set
        if (empty($_POST['sort_order'])) {
            echo '0';
            exit;
        }

        // Parse field sort order
        parse_str($_POST['sort_order'], $sort_order);

        // Check if field sort order is valid
        if (!is_array($sort_order) || empty($sort_order)) {
            echo '0';
            exit;
        }

        // Get array of all sorted field ids
        $all_sorted_post_ids = array();

        foreach ($sort_order as $sort_order_values) {
            foreach ($sort_order_values as $position => $post_id) {
                $all_sorted_post_ids[] = $post_id;
            }
        }

        // Store new sort order
        WCCF_Field_Controller::store_field_sort_order($sort_order, $all_sorted_post_ids);

        // End request
        echo '1';
        exit;
    }

    /**
     * Store field sort order
     *
     * @access public
     * @param array $sort_order
     * @param array $all_sorted_post_ids
     * @return void
     */
    public static function store_field_sort_order($sort_order, $all_sorted_post_ids)
    {
        global $wpdb;

        // Get current sort order
        $current_sort_order = array();

        foreach ($all_sorted_post_ids as $post_id) {

            // Query database
            $results = $wpdb->get_results("SELECT menu_order FROM $wpdb->posts WHERE ID = " . intval($post_id));

            // Add to current sort order array
            foreach ($results as $result) {
                $current_sort_order[] = $result->menu_order;
            }
        }

        // Sort current sort order
        sort($current_sort_order);

        // Update sort order for each field
        foreach ($sort_order as $sort_order_values) {
            foreach ($sort_order_values as $position => $post_id) {
                $wpdb->update($wpdb->posts, array('menu_order' => $current_sort_order[$position]), array('ID' => intval($post_id)));
            }
        }
    }

    /**
     * Recheck field sort order
     *
     * @access public
     * @return void
     */
    public function recheck_field_sort_order()
    {
        global $wpdb;

        // Get total post coult for current post type and maximum sort value
        $results = $wpdb->get_results("
            SELECT count(*) as post_count, max(menu_order) as max_sort_value
            FROM $wpdb->posts
            WHERE post_type = '" . $this->get_post_type() . "'
            AND post_status = 'publish'
        ");

        // Check if any changes were made
        if ($results[0]->post_count == 0 || $results[0]->post_count == $results[0]->max_sort_value) {
            return;
        }

        // Get post ids to update
        $results = $wpdb->get_results("
            SELECT ID
            FROM $wpdb->posts
            WHERE post_type = '" . $this->get_post_type() . "'
            AND post_status = 'publish'
            ORDER BY menu_order ASC
        ");

        // Iterate over posts and update sort order
        foreach ($results as $result_key => $result) {
            $wpdb->update($wpdb->posts, array('menu_order' => ($result_key + 1)), array('ID' => $result->ID));
        }
    }

    /**
     * Reset field sort order when it is trashed
     *
     * @access public
     * @param int $post_id
     * @return void
     */
    public function reset_sort_order($post_id)
    {
        // Check if this is our post type
        if (!RightPress_Help::post_type_is($post_id, $this->get_post_type())) {
            return;
        }

        global $wpdb;

        // Reset sort order
        $wpdb->update($wpdb->posts, array('menu_order' => 0), array('ID' => $post_id));
    }

    /**
     * Sort fields during WP_Query
     *
     * @access public
     * @param object $query
     * @return void
     */
    public function sort_fields($query)
    {
        // Make sure it's our post type
        if (!isset($query->query['post_type']) || $query->query['post_type'] !== $this->get_post_type()) {
            return;
        }

        // Set ordering properties
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }

    /**
     * Ensure that field key is unique across all fields of current type
     *
     * @access public
     * @return void
     */
    public function ajax_validate_field_key()
    {
        try {

            // Check if value is set
            if (empty($_POST['value']) && $_POST['value'] !== '0') {
                throw new Exception;
            }

            $value = strtolower((string) $_POST['value']);

            // Check if post type is set
            if (empty($_POST['post_type']) || !WCCF_Post_Object_Controller::post_type_exists($_POST['post_type'])) {
                throw new Exception;
            }

            $post_type = $_POST['post_type'];

            // Check if this is our post type
            if ($post_type !== $this->get_post_type()) {
                return;
            }

            // Check if post id is set
            if (empty($_POST['post_id'])) {
                throw new Exception;
            }

            $post_id = $_POST['post_id'];

            // Check if field key already exists
            if ($this->key_exists($value, $post_id)) {
                throw new Exception(__('Field key must be unique across all fields of this type (including archived and trashed).', 'rp_wccf'));
            }

            // Make sure that the field key does not end with an underscore followed by a number (related to issue #313)
            if (preg_match('/_\d+$/', $value)) {
                throw new Exception(__('Field key can not end with an underscore followed by a number.', 'rp_wccf'));
            }

            // Make sure that the field key does not start with either "id_" or "data_" (related to issue #317)
            if (preg_match('/^id_/i', $value) || preg_match('/^data_/i', $value)) {
                throw new Exception(__('Field key can not start with "id_" or "data_".', 'rp_wccf'));
            }
        }
        catch (Exception $e) {

            $return = array('result' => 'error');

            $message = $e->getMessage();

            if (!empty($message)) {
                $return['message'] = $message;
            }

            // Invalid
            echo json_encode($return);
            exit;
        }

        // Valid
        echo json_encode(array(
            'result' => 'success',
        ));
        exit;

    }

    /**
     * Print a list of fields
     *
     * @access public
     * @param array $fields
     * @param int $item_id
     * @param int $quantity
     * @param int $quantity_index
     * @param object $product
     * @param array $values
     * @return void
     */
    public static function print_fields($fields, $item_id = null, $quantity = null, $quantity_index = null, $product = null, $values = array())
    {

        // Track iterations
        $continue = true;

        // Reduce set of fields by various criteria
        while ($continue) {

            $continue = false;

            // Iterate over fields
            foreach ($fields as $field_id => $field) {

                // Allow developers to skip printing this field
                if (!apply_filters('wccf_print_field', true, $field)) {
                    unset($fields[$field_id]);
                    $continue = true;
                    break;
                }

                // Field contains disabled condition
                if (WCCF_Controller_Conditions::field_contains_disabled_condition($field)) {
                    unset($fields[$field_id]);
                    $continue = true;
                    break;
                }

                // Field has other field condition and other field is not being printed
                // Note: Commented out to fix issue #535
                /*if ($frontend_conditions = $field->get_frontend_conditions()) {
                    foreach ($frontend_conditions as $condition) {
                        if ($condition['type'] === 'other__other_custom_field') {
                            if (!isset($fields[(int) $condition['other_field_id']])) {
                                unset($fields[$field_id]);
                                $continue = true;
                                break 2;
                            }
                        }
                    }
                }*/
            }
        }

        // No fields to print
        if (empty($fields)) {
            return;
        }

        // Iterate over fields and display them
        foreach ($fields as $field) {

            // Check how many times to print the same field (used for quantity-based product fields)
            if ($quantity_index !== null) {
                $iterations = ($quantity_index + 1);
                $i = $quantity_index;
            }
            else {
                $iterations = ($field->is_quantity_based() && $quantity) ? $quantity : 1;
                $i = 0;
            }

            // Print fields
            for ($i = $i; $i < $iterations; $i++) {
                WCCF_Field_Controller::print_field($field, $item_id, $i, $product, $values);
            }

            $printed = true;
        }

        // Enqueue frontend scripts
        WCCF_Assets::enqueue_frontend_scripts();
    }

    /**
     * Print single field
     *
     * @access public
     * @param object $field
     * @param int $item_id
     * @param int $quantity_index
     * @param object $product
     * @param array $values
     * @return void
     */
    public static function print_field($field, $item_id, $quantity_index, $product = null, $values = array())
    {

        // Get some properties
        $field_id       = $field->get_id();
        $field_key      = $field->get_key();
        $field_type     = $field->get_field_type();
        $context        = $field->get_context();
        $field_label    = $field->get_label();

        // Field label treatment for quantity based product fields
        if ($quantity_index) {
            $field_label = WCCF_Field_Controller::get_quantity_adjusted_field_label($field_label, $quantity_index);
        }

        // Field name treatment for quantity based product fields
        $field_id_for_name = $quantity_index ? ($field_id . '_' . $quantity_index) : $field_id;

        // Configure field
        $params = array(
            'id'                => 'wccf_' . $context . '_' . $field_key . ($quantity_index ? ('_' . $quantity_index) : ''),
            'name'              => WCCF_Field_Controller::get_input_name($field, $quantity_index),
            'class'             => 'wccf wccf_' . $context . ' wccf_' . $field_type . ' wccf_' . $context . '_' . $field_type,
            'label'             => $field_label,
            'required'          => $field->is_required(),
            'maxlength'         => $field->get_character_limit(),
            'min'               => $field->get_min_value(),
            'max'               => $field->get_max_value(),
            'quantity_index'    => $quantity_index,
            'item_id'           => $item_id,
            'product'           => $product,
        );

        // Get stored field value
        $stored_value = $item_id ? $field->get_stored_value($item_id) : false;

        // Values are provided
        if (!empty($values)) {

            if (isset($values[$field_id_for_name])) {
                $value = $values[$field_id_for_name];
                $params['value'] = is_string($value) ? stripslashes($value) : $value;
            }
        }
        // Values are not provided
        else {

            // Use newly posted value
            if (isset($_REQUEST['wccf'][$context][$field_id_for_name])) {
                $value = $_REQUEST['wccf'][$context][$field_id_for_name];
                $params['value'] = is_string($value) ? stripslashes($value) : $value;
            }
            // Use values from query string - quantity index aware
            else if (!isset($_REQUEST['wccf']) && isset($_REQUEST['wccf_' . $context . '_' . $field_id_for_name])) {
                if (is_array($_REQUEST['wccf_' . $context . '_' . $field_id_for_name])) {
                    foreach ($_REQUEST['wccf_' . $context . '_' . $field_id_for_name] as $value_from_query_vars) {
                        $params['value'][] = stripslashes((rawurldecode($value_from_query_vars)));
                    }
                }
                else {
                    $params['value'] = stripslashes(rawurldecode($_REQUEST['wccf_' . $context . '_' . $field_id_for_name]));
                }
            }
            // Use values from query string - quantity index unaware
            else if (!isset($_REQUEST['wccf']) && isset($_REQUEST['wccf_' . $context . '_' . $field_id])) {
                if (is_array($_REQUEST['wccf_' . $context . '_' . $field_id])) {
                    foreach ($_REQUEST['wccf_' . $context . '_' . $field_id] as $value_from_query_vars) {
                        $params['value'][] = stripslashes(rawurldecode($value_from_query_vars));
                    }
                }
                else {
                    $params['value'] = stripslashes(rawurldecode($_REQUEST['wccf_' . $context . '_' . $field_id]));
                }
            }
            // Use previously stored value
            else if ($stored_value !== false) {
                $params['value'] = $stored_value;
            }
        }

        // Set options if this field has any
        if ($field->has_options()) {
            $params['options'] = $field->get_options_list();
        }

        // Get custom CSS
        $custom_css = $field->get_custom_css();

        // Check if field uses custom CSS
        if (!empty($custom_css)) {
            $params['style'] = $custom_css;
        }

        // Get placeholder
        $placeholder = $field->get_placeholder();

        // Check if field uses placeholder
        if ($placeholder !== false && !RightPress_Help::is_empty($placeholder)) {
            $params['placeholder'] = $placeholder;
        }

        // Display field
        WCCF_FB::$field_type($params, $field);
    }

    /**
     * Get input name for field
     *
     * @access public
     * @param object $field
     * @param int $quantity_index
     * @return string
     */
    public static function get_input_name($field, $quantity_index)
    {
        $field_id_for_name = $quantity_index ? ($field->get_id() . '_' . $quantity_index) : $field->get_id();
        return 'wccf[' . $field->get_context() . '][' . $field_id_for_name . ']' . ($field->accepts_multiple_values() ? '[]' : '');
    }

    /**
     * Validate POSTed field set data and add WooCommerce notices
     *
     * @access public
     * @param string $context
     * @param array $params
     * @return bool
     */
    public static function validate_posted_field_values($context, $params = array())
    {
        return self::sanitize_posted_field_values($context, array_merge($params, array('validate_only' => true)));
    }

    /**
     * Sanitize posted field values
     *
     * Returns false on failure and array( %field_id% => array('value' => %field_value%) ) on success
     *
     * @access public
     * @param string $context
     * @param array $params
     * @return mixed
     */
    public static function sanitize_posted_field_values($context, $params = array())
    {
        extract(RightPress_Help::filter_by_keys_with_defaults($params, array(
            'object_id' => null, 'fields' => null, 'user_field_type' => null, 'values' => null,
        )));

        // Get fields if they were not provided
        if ($fields === null) {

            // Get controller instance
            $controller = WCCF_Field_Controller::get_controller_instance_by_context($context);

            // Get all applicable fields
            $fields = $controller::get_filtered(null, array('item_id' => $object_id));

            // Filter by user field type if needed
            if ($user_field_type !== null) {
                $fields = WCCF_Field_Controller::filter_by_property($fields, 'display_as', $user_field_type);
            }

            // Merge fields to params
            $params = array_merge($params, array('fields' => $fields));
        }

        // Unset page elements
        if (!empty($params['fields']) && is_array($params['fields'])) {
            foreach ($params['fields'] as $field_id => $field) {
                if ($field->is_page_element()) {
                    unset($params['fields'][$field_id]);
                }
            }
        }

        // Extract posted field values by fields if values are not provided
        if ($values === null && !RightPress_Product_Price_Shop::is_calculating()) {
            $values = WCCF_Field_Controller::extract_posted_values($context, $params);
        }

        // Sanitize field values and return (returns bool for validation request)
        return WCCF_Field_Controller::sanitize_field_values($values, $params);
    }

    /**
     * Extract field values by fields from posted data
     *
     * Stores null for field id if data for that field is not present
     *
     * @access public
     * @param string $context
     * @param array $params
     * @return array
     */
    public static function extract_posted_values($context, $params = array())
    {
        extract(RightPress_Help::filter_by_keys_with_defaults($params, array(
            'fields' => array(), 'posted' => null, 'quantity' => 1, 'quantity_index' => null, 'files' => null
        )));

        // Get posted data
        $posted = $posted === null ? $_POST : (array) $posted;
        $files = $files === null ? $_FILES : (array) $files;

        // Store values
        $values = array();

        // Iterate over fields and prepare values
        foreach ($fields as $field) {

            // Check how many times to iterate the same field (used for quantity-based product fields)
            if ($quantity_index !== null) {
                $iterations = ($quantity_index + 1);
                $i = $quantity_index;
            }
            else {
                $iterations = ($field->is_quantity_based() && $quantity) ? $quantity : 1;
                $i = 0;
            }

            // Start iteration of the same field
            for ($i = $i; $i < $iterations; $i++) {

                // Get field id
                $field_id = $field->get_id() . ($i ? ('_' . $i) : '');

                // Special handling for files
                if ($field->field_type_is('file')) {

                    // Ajax request - get file data
                    if (defined('WCCF_UPLOADING_FILE')) {

                        // Store field value if it was uploaded
                        if (!empty($files['wccf']['name'][$context][$field_id])) {

                            // Multiple file upload enabled
                            if (is_array($files['wccf']['name'][$context][$field_id])) {
                                $values[$field_id] = array(
                                    'value' => array(
                                        'name'      => $files['wccf']['name'][$context][$field_id][0],
                                        'type'      => $files['wccf']['type'][$context][$field_id][0],
                                        'tmp_name'  => $files['wccf']['tmp_name'][$context][$field_id][0],
                                        'error'     => $files['wccf']['error'][$context][$field_id][0],
                                        'size'      => $files['wccf']['size'][$context][$field_id][0],
                                    ),
                                );
                            }
                            // Multiple file upload disabled
                            else if (is_string($files['wccf']['name'][$context][$field_id])) {
                                $values[$field_id] = array(
                                    'value' => array(
                                        'name'      => $files['wccf']['name'][$context][$field_id],
                                        'type'      => $files['wccf']['type'][$context][$field_id],
                                        'tmp_name'  => $files['wccf']['tmp_name'][$context][$field_id],
                                        'error'     => $files['wccf']['error'][$context][$field_id],
                                        'size'      => $files['wccf']['size'][$context][$field_id],
                                    ),
                                );
                            }
                        }
                        else {
                            $values[$field_id] = null;
                        }
                    }
                    // Regular form submit - get access keys for already uploaded files
                    else {

                        // Files are actually uploaded via separate Ajax request
                        // Ajax request returns file access keys and they are added as hidden inputs
                        // Here we get their access keys inside $_POST data
                        // Note that the result of this field must always be array since we have plans to support multiple file uploads

                        // Check if file was uploaded
                        if (!empty($posted['wccf'][$context][$field_id])) {
                            $values[$field_id] = array(
                                'value' => (array) $posted['wccf'][$context][$field_id],
                            );
                        }
                        else {
                            $values[$field_id] = null;
                        }
                    }
                }
                // Handle other field values
                else {

                    // Check if any data for this field was posted or is available in request query vars for GET requests
                    if (isset($posted['wccf'][$context][$field_id]) || ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['wccf_' . $context . '_' . $field_id]))) {

                        // Get field value
                        if (isset($posted['wccf'][$context][$field_id])) {
                            $field_value = $posted['wccf'][$context][$field_id];
                        }
                        else {
                            $field_value = $_GET['wccf_' . $context . '_' . $field_id];
                        }

                        // Prepare multiselect field values
                        if ($field->accepts_multiple_values()) {

                            // Ensure that value is array
                            $value = !RightPress_Help::is_empty($field_value) ? (array) $field_value : array();

                            // Filter out hidden placeholder input value
                            $value = array_filter((array) $value, function($test_value) {
                                return trim($test_value) !== '';
                            });
                        }
                        else {
                            $value = is_string($field_value) ? stripslashes(trim($field_value)) : $field_value;
                        }

                        // Store field value
                        $values[$field_id] = array(
                            'value' => $value,
                        );
                    }
                    else {
                        $values[$field_id] = null;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Validate and sanitize field values
     *
     * @access public
     * @param array $values
     * @param array $params
     * @return mixed
     */
    public static function sanitize_field_values($values, $params = array())
    {
        extract(RightPress_Help::filter_by_keys_with_defaults($params, array(
            'fields' => array(), 'item_id' => null, 'validate_only' => false, 'skip_invalid' => false, 'leave_no_trace' => false, 'quantity' => 1, 'quantity_index' => null, 'skip_frontend_validation' => false, 'wp_errors' => null, 'store_empty_value' => false
        )));

        // Store sanitized values
        $sanitized_values = array();

        // Iterate over fields
        foreach ($fields as $field) {

            // Check if field is required
            if ($field->context_is('user_field') && $field->get_display_as() === 'shipping_address' && empty($_REQUEST['ship_to_different_address'])) {
                $is_required = false;
            }
            else {
                $is_required = $field->is_required();
            }

            // Check how many times to iterate the same field (used for quantity-based product fields)
            if ($quantity_index !== null) {
                $iterations = ($quantity_index + 1);
                $i = $quantity_index;
            }
            else {
                $iterations = ($field->is_quantity_based() && $quantity) ? $quantity : 1;
                $i = 0;
            }

            // Iterate field
            for ($i = $i; $i < $iterations; $i++) {

                // Get field id
                $field_id = $field->get_id() . ($i ? ('_' . $i) : '');

                // Skip field if it is not required based on frontend conditions
                if (!$skip_frontend_validation && !WCCF_Controller_Conditions::check_frontend_conditions($field, $fields, $values)) {
                    continue;
                }

                // Track validation
                $empty_error = false;
                $value_error = false;

                // Store additional data
                $extra_data = array();
                $files = array();

                // Clear file data from session
                $clear_session_access_keys = array();

                // Get field value
                $value = (!empty($values[$field_id]) && is_array($values[$field_id])) ? $values[$field_id]['value'] : null;

                // Fix decimals with comma as decimal separator
                if ($field->field_type_is(array('number', 'decimal')) && $value !== null) {
                    $value = RightPress_Help::normalize_string_decimal($value);
                }

                // Check if value is empty
                if (empty($value) && $value !== '0') {

                    // Value is empty but is required
                    if ($is_required) {
                        $empty_error = true;
                    }
                }
                else {

                    // Validate value
                    $validation_result = self::validate_field_value($field, $value, $item_id, $i);

                    // Value is not valid
                    if ($validation_result !== true) {
                        $value_error = $validation_result;
                    }
                }

                // Load file data if this is a regular request (not Ajax file upload)
                if ($field->field_type_is('file') && !empty($value) && !$empty_error && !$value_error && !defined('WCCF_UPLOADING_FILE')) {

                    // Load session object
                    $session = WCCF_WC_Session::initialize_session();

                    // Iterate over file access keys
                    foreach ($value as $index => $access_key) {

                        $file_data = false;

                        // Get meta access key
                        $meta_access_key = WCCF_Field::get_temp_file_data_access_key($access_key);

                        // Attempt to load from session
                        if ($session) {
                            $file_data = $session->get($meta_access_key, false);
                        }

                        // Loaded from session
                        if ($file_data !== false) {
                            $clear_session_access_keys[] = $meta_access_key;
                        }
                        // Attempt to load file data from post meta
                        else {

                            // Check for just uploaded files
                            if ($field->data_exists($item_id, $meta_access_key)) {
                                $file_data = maybe_unserialize($field->get_data($item_id, $meta_access_key, true));
                            }
                            else if ($field->context_is('order_field') || $field->context_is('product_prop') || $field->context_is('user_field')) {
                                $file_data = maybe_unserialize($field->get_data($item_id, WCCF_Field::get_file_data_access_key($access_key), true));
                            }
                        }

                        // File data found
                        if (!empty($file_data)) {
                            $files[$access_key] = $file_data;
                        }
                        // File data not found and file is required
                        else if ($is_required) {
                            $empty_error = true;
                        }
                        // File data not found and file is not required
                        else {
                            unset($value[$index]);
                        }
                    }
                }

                // Validation succeeded
                if (!$empty_error && !$value_error) {

                    // Store value if this is not a validate-only request
                    if (!$validate_only) {

                        // Process file
                        if ($field->field_type_is('file') && !$leave_no_trace) {

                            // Ajax request - store file
                            if (defined('WCCF_UPLOADING_FILE')) {

                                // Store file
                                $storage_data = WCCF_Files::store_file($value);

                                // Unable to store file
                                if (!$storage_data) {
                                    continue;
                                }

                                // Get access key
                                $access_key = WCCF_Files::get_unique_file_access_key(null, $files);

                                // Add file data
                                $files[$access_key] = array(
                                    'subdirectory'  => $storage_data['subdirectory'],
                                    'storage_key'   => $storage_data['storage_key'],
                                    'name'          => $value['name'],
                                    'type'          => $value['type'],
                                    'field_id'      => $field_id,
                                );

                                // Set value to access key
                                // Storing this as array to prepare for multiple file uploads
                                $value = array($access_key);
                            }
                            // Not Ajax request - remove file reference from session data
                            // This was removed to allow the same product with the same configuration to be added to cart multiple times
                            /*else if ($field->context_is('product_field') && !empty($clear_session_access_keys)) {
                                foreach ($clear_session_access_keys as $clear_session_access_key) {
                                    unset(WC()->session->$clear_session_access_key);
                                }
                            }*/
                        }

                        // Allow developers to do their own data sanitization
                        $value = apply_filters('wccf_sanitized_field_value', $value, $field, $i);

                        // Check if we need to store empty value
                        if ($field->context_is('order_field') || $field->context_is('product_prop') || $field->context_is('user_field')) {
                            $store_empty_value = true;
                        }
                        // Allow developers to override default value
                        else {
                            $store_empty_value = apply_filters('wccf_store_empty_value', $store_empty_value, $field);
                        }

                        // Store any other empty value with developer's permission
                        if (!empty($value) || $value === '0' || $store_empty_value) {
                            $sanitized_values[$field_id] = array(
                                'value' => $value,
                                'data'  => $extra_data,
                                'files' => $files
                            );
                        }
                    }
                }
                // Validation failed
                else {

                    // Maybe skip fields with invalid values and continue to other fields
                    if ($skip_invalid) {
                        continue;
                    }

                    $sanitized_values = false;

                    // Add validation message if this is a validate-only request
                    if ($validate_only) {

                        // Format message
                        $label_for_message = ($field->is_quantity_based() && $i) ? WCCF_Field_Controller::get_quantity_adjusted_field_label($field->get_label(), $i) : null;
                        $message = $empty_error ? self::get_empty_field_error_message($field, $label_for_message) : self::get_field_value_error_message($field, $label_for_message, $value_error);

                        // Add message
                        if (is_object($wp_errors)) {
                            $wp_errors->add('wccf_' . $field->get_context() . '_' . $field->get_key() . '_error', $message);
                        }
                        else {
                            RightPress_Help::wc_add_notice($message, 'error');
                        }
                    }
                    // Otherwise break the cycle
                    else {
                        break;
                    }
                }
            }
        }

        // Return correct data type
        if ($validate_only) {
            return $sanitized_values !== false;
        }
        else {
            return $sanitized_values ?: false;
        }
    }

    /**
     * Validate single field data
     *
     * Returns boolean true if validation is passed
     * Returns string error message if validation fails
     *
     * @access public
     * @param object $field
     * @param mixed $value
     * @param mixed $item
     * @param int $quantity_index
     * @return mixed
     */
    public static function validate_field_value($field, $value, $item = null, $quantity_index = null)
    {
        // Do not validate files during regular form submit since they are uploaded via separate Ajax request
        if ($field->field_type_is('file') && !defined('WCCF_UPLOADING_FILE')) {
            return true;
        }

        // Check character limit
        $character_limit = $field->get_character_limit();

        if ($character_limit !== null && $character_limit !== '' && !is_array($value) && $value !== null && mb_strlen(trim((string) $value)) > $character_limit) {
            return __('is too long', 'rp_wccf');
        }

        // Check min selected
        $min_selected = $field->get_min_selected();

        if ($min_selected !== null && $min_selected !== '' && count((array) $value) < $min_selected) {
            return sprintf(__('must have at least %d options selected', 'rp_wccf'), $min_selected);
        }

        // Check max selected
        $max_selected = $field->get_max_selected();

        if ($max_selected !== null && $max_selected !== '' && count((array) $value) > $max_selected) {
            return sprintf(__('must have no more than %d options selected', 'rp_wccf'), $max_selected);
        }

        // Check min value
        $min_value = $field->get_min_value();

        if ($min_value !== null && $min_value !== '' && $value < $min_value) {
            return sprintf(__('must be at least %d', 'rp_wccf'), $min_value);
        }

        // Check max value
        $max_value = $field->get_max_value();

        if ($max_value !== null && $max_value !== '' && $value > $max_value) {
            return sprintf(__('must be less than %d', 'rp_wccf'), $max_value);
        }

        // Handle empty file upload values
        if ($field->field_type_is('file') && empty($value)) {
            if (!$field->is_required() || ($item && $field->get_stored_value($item))) {
                return true;
            }
            else {
                return __('must be uploaded', 'rp_wccf');
            }
        }

        // Validation by field type
        try {

            // Get valdiation method name
            $field_type = $field->get_field_type();
            $method = 'validate_' . $field_type;

            // Validate
            return WCCF_FB::$method($value, $field, $quantity_index, $item);
        }
        catch (Exception $e) {

            // Validation by field type returned error message
            return $e->getMessage();
        }
    }

    /**
     * Get empty field error message
     *
     * @access public
     * @param object $field
     * @param string $label
     * @return void
     */
    public static function get_empty_field_error_message($field, $label = null)
    {
        // Get label
        if ($label === null) {
            $label = $field->get_label();
        }

        // Add field id
        $label = '<span data-wccf-error-field-id="' . $field->get_id() . '">' . $label . '</span>';

        // Proceed depending on field type
        switch ($field->get_field_type()) {

            // Selectable required fields
            case 'date':
            case 'time':
            case 'datetime':
            case 'color':
            case 'select':
            case 'multiselect':
            case 'checkbox':
            case 'radio':
                $message = sprintf(__('<strong>%s</strong> must be selected.', 'rp_wccf'), $label);
                break;

            // Required file upload
            case 'file':
                $message = sprintf(__('<strong>%s</strong> must be uploaded.', 'rp_wccf'), $label);
                break;

            // All types of required text inputs
            default:
                $message = sprintf(__('<strong>%s</strong> is a required field.', 'rp_wccf'), $label);
        }

        // Allow developers to override value and return it
        return apply_filters('wccf_field_empty_error', $message, $field);
    }

    /**
     * Get field value error message
     *
     * @access public
     * @param object $field
     * @param string $label
     * @param string $message
     * @return void
     */
    public static function get_field_value_error_message($field, $label = null, $message = null)
    {
        // Get label
        if ($label === null) {
            $label = $field->get_label();
        }

        // Add field id
        $label = '<span data-wccf-error-field-id="' . $field->get_id() . '">' . $label . '</span>';

        // Custom message provided
        if (is_string($message)) {
            $message = sprintf(__('<strong>%s</strong> %s.', 'rp_wccf'), $label, $message);
        }
        else {

            // Proceed depending on field type
            switch ($field->get_field_type()) {

                // Email address
                case 'email':
                    $message = sprintf(__('<strong>%s</strong> is not a valid email.', 'rp_wccf'), $label);
                    break;

                // Number
                case 'number':
                    $message = sprintf(__('<strong>%s</strong> is not a valid number.', 'rp_wccf'), $label);
                    break;

                // Decimal
                case 'decimal':
                    $message = sprintf(__('<strong>%s</strong> is not a valid decimal.', 'rp_wccf'), $label);
                    break;

                // Date
                case 'date':
                    $message = sprintf(__('<strong>%s</strong> is not a valid date.', 'rp_wccf'), $label);
                    break;

                // Time
                case 'time':
                    $message = sprintf(__('<strong>%s</strong> is not a valid time.', 'rp_wccf'), $label);
                    break;

                // Datetime
                case 'datetime':
                    $message = sprintf(__('<strong>%s</strong> is not a valid date/time.', 'rp_wccf'), $label);
                    break;

                // Color
                case 'color':
                    $message = sprintf(__('<strong>%s</strong> is not a valid hexadecimal color code.', 'rp_wccf'), $label);
                    break;

                // Invalid file upload
                case 'file':
                    $message = sprintf(__('<strong>%s</strong> is not a valid file.', 'rp_wccf'), $label);
                    break;

                // All other types of invalid inputs
                default:
                    $message = sprintf(__('<strong>%s</strong> value is not valid.', 'rp_wccf'), $label);
            }
        }

        // Allow developers to override value and return it
        return apply_filters('wccf_field_value_error', $message, $field);
    }

    /**
     * Get all fields by context
     *
     * @access public
     * @param string $context
     * @param array $status
     * @param array $key
     * @return array
     */
    public static function get_all_by_context($context, $status = array(), $key = array())
    {
        // Get post controller instance
        $controller = WCCF_Field_Controller::get_controller_instance_by_context($context);

        // Return all fields of this type including disabled fields
        return $controller::get_all($status, $key);
    }

    /**
     * Get all fields list for use in select fields
     *
     * @access public
     * @param string $context
     * @param array $status
     * @param int $exclude_field_id
     * @return array
     */
    public static function get_all_field_list_by_context($context, $status = array(), $exclude_field_id = null)
    {
        $list = array();

        // Iterate over fields
        foreach (WCCF_Field_Controller::get_all_by_context($context, $status) as $field) {

            // Add to list
            if ($exclude_field_id === null || (int) $exclude_field_id !== (int) $field->get_id()) {
                $list[$field->get_id()] = $field->get_label() . ' - ' . $field->get_key();
            }
        }

        return $list;
    }

    /**
     * Alias to get short version of post type
     * Used to identify what kind of fields we are working with
     *
     * @access public
     * @return string
     */
    public function get_context()
    {
        return $this->get_post_type_short();
    }

    /**
     * Maybe print field description in admin field list
     * Using a filter hook as there's no other way to print where we need to print
     *
     * @access public
     * @return array
     */
    public function maybe_print_admin_field_description($views)
    {
        global $typenow;
        $screen = get_current_screen();

        // Check if we are in correct view
        if ($typenow && $typenow === $this->get_post_type() && $screen->base === 'edit') {

            // Print field description
            echo '<div id="wccf_admin_field_description">' . $this->get_admin_field_description_content() . '</div>';
        }

        // Return param that was passed in
        return $views;
    }

    /**
     * Add search contexts and meta whitelist
     *
     * @access public
     * @return array
     */
    public function expand_list_search_context_where_properties()
    {
        return array(
            'contexts' => array(
                'id'    => 'ID',
                'ID'    => 'ID',
                'key'   => 'key',
                'label' => 'label',
            ),
            'meta_whitelist' => array(
                'key', 'label',
            ),
        );
    }

    /**
     * Get controller instance by field context
     *
     * @access public
     * @param string $context
     * @return object
     */
    public static function get_controller_instance_by_context($context)
    {
        return self::get_controller_instance_by_post_type('wccf_' . $context);
    }

    /**
     * Sanitize and store field values
     *
     * @access public
     * @param mixed $item
     * @param string $context
     * @param bool $skip_invalid
     * @param bool $is_checkout
     * @param string $user_field_type
     * @return void
     */
    public static function store_field_values($item, $context, $skip_invalid = false, $is_checkout = false, $user_field_type = null)
    {
        // Get item id
        $item_id = is_object($item) ? $item->get_id() : $item;

        // Special handling of user fields on checkout
        if ($context === 'user_field' && $is_checkout) {

            // Reference order object or order id to store user data copy to order
            $order_reference = $item;

            // Load order object if only id was passed in
            $order = is_object($item) ? $item : wc_get_order($item);

            // Get customer id
            if ($order_customer_id = $order->get_customer_id()) {
                $item_id = $order_customer_id;
                $item = RightPress_Help::wc_get_customer($item_id);
                $store_value = true;
            }
            else {
                $store_value = false;
            }
        }
        // Always store other fields and user fields not on checkout
        else {
            $store_value = true;
        }

        // Get sanitized field values
        $values = WCCF_Field_Controller::sanitize_posted_field_values($context, array(
            'object_id'         => $item_id,
            'item_id'           => $item_id,
            'skip_invalid'      => true,
            'user_field_type'   => $user_field_type,
        ));

        // Check if values were passed in
        if (empty($values) || !is_array($values)) {
            return;
        }

        // Iterate over values and add them to meta
        foreach ($values as $field_id => $field_value) {

            // Get field
            $field = WCCF_Field_Controller::get($field_id, 'wccf_' . $context);

            // Check if field was loaded
            if (!$field) {
                continue;
            }

            // Store value
            if ($store_value) {
                $field->store_value($item, $field_value);
            }

            // Store a copy of user field value in order meta during checkout
            if ($context === 'user_field' && $is_checkout) {

                // Store field value as hidden meta
                $value_to_store = apply_filters('wccf_field_value_to_store', $field_value['value'], $order_reference, $field);
                RightPress_WC::order_update_meta_data($order_reference, $field->get_value_access_key(), $value_to_store);

                // Store field id as hidden meta
                RightPress_WC::order_update_meta_data($order_reference, $field->get_id_access_key(), $field->get_id());

                // Store option labels in extra data if needed
                if ($field->has_options()) {
                    $field_value['data']['labels'] = $field->get_option_labels_from_keys($field_value['value']);
                }

                // Store extra field data as hidden meta
                RightPress_WC::order_update_meta_data($order_reference, $field->get_extra_data_access_key(), $field_value['data']);

                // Store file data
                foreach ($field_value['files'] as $access_key => $file_data) {

                    // Store file in meta
                    RightPress_WC::order_update_meta_data($order_reference, WCCF_Field::get_file_data_access_key($access_key), $file_data);
                }
            }
        }

        // Save user object if needed
        if ($context === 'user_field' && $is_checkout && is_object($item)) {
            $item->save();
        }

        // Clear cached prices for product updates
        // Will need to run clear_cached_price() from any other place where we introduct a way to change product property values for a product (like some kind of APIs etc)
        if (is_a($item, 'WC_Product')) {
            WCCF_WC_Product_Price_Shop::clear_cached_price($item);
        }
    }

    /**
     * Prepare Product Property, Checkout Field and Order Field values for display in frontend
     *
     * @access public
     * @param array $fields
     * @param int $item_id
     * @param string $context
     * @return array
     */
    public static function get_field_values_for_frontend($fields, $item_id, $context)
    {
        // Check if this field type support visibility
        $controller = WCCF_Field_Controller::get_controller_instance_by_context($context);
        $supports_visibility = $controller->supports_visibility();

        // Store fields to display
        $values = array();

        // Iterate over fields
        foreach ($fields as $field) {

            // Check if field is public
            if ($supports_visibility && !$field->is_public()) {
                continue;
            }

            // Get field value
            if ($supports_visibility && WCCF_Settings::get('display_default_' . $context . '_values') && $field->is_enabled()) {
                $field_value = $field->get_final_value($item_id, true);
            }
            else {
                $field_value = $field->get_stored_value($item_id, null, null, true);
            }

            // Check if field has value
            if ($field_value === false) {
                continue;
            }

            // Get display value
            $display_value = $field->format_display_value(array(
                'value' => $field_value,
                'data'  => array(),
            ));

            // Add to return array
            $values[] = array(
                'id'            => $field->get_id(),
                'value'         => $field_value,
                'display_value' => $display_value,
                'field'         => $field,
            );
        }

        // Allow developers to modify values and return
        return apply_filters('wccf_frontend_' . $context . '_values', $values, $item_id);
    }

    /**
     * Manage field list views
     *
     * @access public
     * @param array $views
     * @return array
     */
    public function filter_list_views($views)
    {
        $new_views = $views;

        global $wp_query;

        // Get post type and status taxonomy
        $post_type = $this->get_post_type();
        $status_taxonomy = $post_type . '_status';

        // Fix post count for "All" view
        $views['all'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            admin_url('edit.php?post_type=' . $post_type),
            ((!isset($wp_query->query_vars[$status_taxonomy]) && !isset($wp_query->query_vars['post_status'])) ? ' class="current"' : ''),
            __('All', 'rp_wccf'),
            $this->get_count_by_status(array('enabled', 'disabled'))
        );

        // Get archived count and check if archive link needs to be displayed
        if ($archived_count = $this->get_count_by_status('archived')) {

            // Add archive view
            $new_views = RightPress_Help::insert_to_array_after_key($views, 'all', array(
                'archived' => sprintf(
                    '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
                    $this->get_archive_list_view(),
                    ((isset($wp_query->query_vars[$status_taxonomy]) && $wp_query->query_vars[$status_taxonomy] === 'archived') ? ' class="current"' : ''),
                    __('Archived', 'rp_wccf'),
                    $archived_count
                ),
            ));
        }

        return $new_views;
    }

    /**
     * Get count by status
     *
     * @access public
     * @param mixed $status
     * @return int
     */
    public function get_count_by_status($status)
    {
        $taxonomy = $this->get_post_type() . '_status';
        return $this->get_count_by_taxonomy($taxonomy, $status);
    }

    /**
     * Filter out archived fields from all field list in admin
     *
     * @access public
     * @param object $query
     * @return void
     */
    public function remove_archived_fields_from_main_list($query)
    {
        // Get current post type and status taxonomy key
        $post_type  = $this->get_post_type();
        $taxonomy   = $post_type . '_status';

        // Make sure this is admin area request and field type is ours
        if (!is_admin() || empty($query->query['post_type']) || $query->query['post_type'] !== $post_type) {
            return;
        }

        // Make sure this is all fields view
        if (isset($query->query[$taxonomy]) || isset($query->query_vars['tax_query'][0]['taxonomy'])) {
            return;
        }

        // Add tax query
        $query->query_vars['tax_query'][] = array(
            'taxonomy'  => $taxonomy,
            'field'     => 'slug',
            'terms'     => array('archived'),
            'operator'  => 'NOT IN',
        );
    }

    /**
     * Get admin list archive view
     *
     * @access public
     * @param string $post_type
     * @return string
     */
    public function get_archive_list_view($post_type = null)
    {
        // Get post type
        if ($post_type === null) {
            $post_type = $this->get_post_type();
        }

        return admin_url('edit.php?s&post_type=' . $post_type . '&' . $post_type . '_status=archived');
    }

    /**
     * Field label treatment for quantity based product fields
     *
     * @access public
     * @param string $field_label
     * @param int $quantity_index
     * @return void
     */
    public static function get_quantity_adjusted_field_label($field_label, $quantity_index)
    {
        return sprintf(_x('%1$s #%2$d', 'Quantity-based product field label format', 'rp_wccf'), $field_label, ($quantity_index + 1));
    }

    /**
     * Get quantity index from field id
     *
     * @access public
     * @param mixed $field_id
     * @param mixed $default
     * @return mixed
     */
    public static function get_quantity_index_from_field_id($field_id, $default = null)
    {
        // Check if field id has quantity index appended
        if (WCCF_Field_Controller::field_id_has_quantity_index($field_id)) {
            return (int) preg_replace('/^\d+_/i', '', $field_id);
        }

        // Field id does not contain quantity index
        return $default;
    }

    /**
     * Get clean field id from potentially modified field id
     *
     * This needs to be done when quantity index is appended to the field id
     * for us in quantity based product fields
     *
     * @access public
     * @param mixed $field_id
     * @return mixed
     */
    public static function clean_field_id($field_id)
    {
        // Check if field id has quantity index appended
        if (WCCF_Field_Controller::field_id_has_quantity_index($field_id)) {
            return (int) preg_replace('/_\d+$/i', '', $field_id);
        }

        // Return original value
        return $field_id;
    }

    /**
     * Check if field id has quantity index appended
     *
     * @access public
     * @param mixed $field_id
     * @return bool
     */
    public static function field_id_has_quantity_index($field_id)
    {
        return (is_string($field_id) && preg_match('/^\d+_\d+$/i', $field_id));
    }

    /**
     * Check if admin is allowed to edit user submitted field values
     *
     * @access public
     * @return bool
     */
    public static function field_value_editing_allowed($context, $field, $value, $item_id)
    {
        // Never allowed in frontend
        if (!is_admin()) {
            return false;
        }

        // Currently file field values can't be edited
        if ($field->field_type_is('file')) {
            return false;
        }

        // Get option
        $is_allowed = (bool) WCCF_Settings::get('allow_' . $context . '_editing');

        // Allow developers to override and return
        return apply_filters('wccf_allow_backend_field_value_editing', $is_allowed, $field, $value, $item_id);
    }

    /**
     * Get field by key
     *
     * @access public
     * @param string $context
     * @param string $key
     * @param bool $backwards_compatibility
     * @return mixed
     */
    public static function get_field_by_key($context, $key, $backwards_compatibility = false)
    {
        // Fix key for backwards compatibility
        if ($backwards_compatibility) {
            $key = preg_replace(array('/^wccf_/', '/^rp_wccf_/'), array('', ''), $key);
        }

        // Run query
        $query = new WP_Query(array(
            'post_type'     => 'wccf_' . $context,
            'fields'        => 'ids',
            'post_status'   => array('publish', 'trash'),
            'meta_query'    => array(
                array(
                    'key'       => 'key',
                    'value'     => $key,
                    'compare'   => '=',
                ),
            ),
        ));

        // No field found
        if (empty($query->posts)) {
            return false;
        }

        // Initialize and return field object
        $field_id = (int) array_pop($query->posts);
        return WCCF_Field_Controller::cache($field_id);
    }

    /**
     * Check if WooCommerce post is of field type
     *
     * @access public
     * @param object $post
     * @return bool
     */
    public static function wp_post_is_field($post = null)
    {

        // Check if provided value is WooCommerce post
        if (is_a($post, 'WP_Post')) {

            // Get post type
            $post_type = get_post_type($post);

            // Check if post type is one of our field post types
            if (WCCF_Field_Controller::post_type_exists($post_type)) {

                return true;
            }
        }

        return false;
    }




}
}
