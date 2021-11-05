<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://weslink.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/admin
 * @author     Weslink <kontakt@weslink.de>
 */
require_once plugin_dir_path(__FILE__) . '/class-weslink-payjoe-opbeleg-orders.php';
require_once plugin_dir_path(__FILE__) . '/partials/constants.php';

class Weslink_Payjoe_Opbeleg_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */

    // set args to null as default so that in case wanna invoking public static function of this class,
    // we don't need to defiine these args
    public function __construct($plugin_name = null, $version = null)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_notices', [$this, 'sample_admin_notice__success']);
    }

    function sample_admin_notice__success()
    {
        $zugangs_id = get_option('payjoe_zugangsid');
        $username = get_option('payjoe_username');
        $apikey = get_option('payjoe_apikey');

        $show_warning = false;
        if (empty($apikey) || empty($zugangs_id) || empty($username)) {
            $show_warning = true;
        }

        if ($show_warning) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    _e('One of the following PayJoe Settings is missing: ', 'weslink-payjoe-opbeleg');
                    echo '<ul style="list-style: disc; margin-left: 20px;">';
                    if (empty($apikey)) {
                        echo '<li>' . __('API Key', 'weslink-payjoe-opbeleg') . '</li>';
                    }
                    if (empty($username)) {
                        echo '<li>' . __('Username', 'weslink-payjoe-opbeleg') . '</li>';
                    }
                    if (empty($zugangs_id)) {
                        echo '<li>' . __('Account ID', 'weslink-payjoe-opbeleg') . '</li>';
                    }
                    echo '</ul>';

                    ?></p>
            </div>
            <?php
        }
    }


    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Weslink_Payjoe_Opbeleg_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Weslink_Payjoe_Opbeleg_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/weslink-payjoe-opbeleg-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Weslink_Payjoe_Opbeleg_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Weslink_Payjoe_Opbeleg_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/weslink-payjoe-opbeleg-admin.js', array('jquery'), $this->version, false);

    }

    /**
     * Adds a settings page link to a menu
     *
     * @return        void
     * @since        1.0.0
     */

    /**
     * Adds a settings page link to a menu
     *
     * @return        void
     * @since        1.0.0
     */
    public function add_menu()
    {

        add_submenu_page(
            'woocommerce',
            __('PayJoe Settings', 'weslink-payjoe-opbeleg'),
            __('PayJoe', 'weslink-payjoe-opbeleg'),
            'manage_woocommerce',
            $this->plugin_name,
            array($this, 'get_settings_page')
        );

        /*
                add_menu_page(   'PayJoe Einstellungen', // Titel der Seite
                                    'PayJoe', // Menu Titel
                                    'manage_options', //Menü Bereich
                                    $this->plugin_name, //id
                                    array($this,'payjoe_settings_page_callback'),
                                    'dashicons-welcome-widgets-menus', //plugins_url( '/images/logo.png', __FILE__ ), //https://developer.wordpress.org/reference/functions/add_menu_page/
                                    3
                );
        */
        $this->createTxtField('payjoe_username', 'payjoe_section_basis'
            , __('Username', 'weslink-payjoe-opbeleg')
            , __('Your E-Mail which your regular login at PayJoe', 'weslink-payjoe-opbeleg')
        );
        $this->createTxtField('payjoe_apikey', 'payjoe_section_basis'
            , __('API Key', 'weslink-payjoe-opbeleg')
            , __('Secret key for API authentication', 'weslink-payjoe-opbeleg')
        );
        $this->createTxtField('payjoe_zugangsid', 'payjoe_section_basis'
            , __('Account ID', 'weslink-payjoe-opbeleg')
            , __('The PayJoe account ID', 'weslink-payjoe-opbeleg')
        );

        // key/value pair ~ value/label
        $interval_options = array(
            '0' =>  __('Disable', 'weslink-payjoe-opbeleg'),
            '0.5' => '0,5',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '6' => '6',
            '6' => '6',
            '8' => '8',
            '10' => '10',
            '12' => '12',
        );
        $default_interval = '0';

        $this->createSelectField('payjoe_interval', 'payjoe_section_basis'
            , __('Interval', 'weslink-payjoe-opbeleg')
            , __('How often should the documents be transferred (hours)', 'weslink-payjoe-opbeleg')
            , $interval_options
            , $default_interval
        );
        /*
        $this->createTxtField('payjoe_startrenr', 'payjoe_section_basis'
            , __('Start Invoice Number', 'weslink-payjoe-opbeleg')
            , __('Which invoice number is the first you want to upload?', 'weslink-payjoe-opbeleg')
        );
        */
        $this->createDateField('payjoe_start_order_date', 'payjoe_section_basis'
            , __('Start Order Date', 'weslink-payjoe-opbeleg')
            , __('Orders before this date will not be uploaded', 'weslink-payjoe-opbeleg')
        );

        $this->createTxtField('payjoe_transfer_count', 'payjoe_section_basis'
            , __('Upload size', 'weslink-payjoe-opbeleg')
            , __('Number of documents to transfer in a single API upload. (max: 500, default: 100)', 'weslink-payjoe-opbeleg'),
                100
        );

        $logging_options = array(
          '0' => __('Disable', 'weslink-payjoe-opbeleg'),
          '1' => __('Enable', 'weslink-payjoe-opbeleg')
        );
        $default_logging_option = '0';

        $loggin_description_more = '';
        if (get_option('payjoe_log')) {
            $payjoe_log_file_url = sprintf('admin.php?page=%s&payjoe_download_log_file=1', $this->plugin_name);
            $loggin_description_more = sprintf(__('<a href="%s" target="_blank">Download Logging File</a>', 'weslink-payjoe-opbeleg'), $payjoe_log_file_url);
        }

        $this->createSelectField('payjoe_log', 'payjoe_section_basis'
            , __('Logging', 'weslink-payjoe-opbeleg')
            , __('Enable/Disable logging. (Logfiles are stored at uploads/payjoe/)', 'weslink-payjoe-opbeleg') //. $loggin_description_more
            , $logging_options
            , $default_logging_option
        );

        $invoice_options = array(
            '0' => 'WP Overnight WooCommerce PDF Invoices & Packing Slips',
            '1' => 'WooCommerce Germanized',
            '2' => 'German Market',
        );
        $default_invoice_option = '0';

        $this->createSelectField('payjoe_invoice_options', 'payjoe_section_basis'
            , __('Invoice System', 'weslink-payjoe-opbeleg')
            , __('Select the System used to generate the invoices', 'weslink-payjoe-opbeleg') //. $loggin_description_more
            , $invoice_options
            , $default_invoice_option
        );

        add_settings_section('payjoe_section_basis', __('General Settings', 'weslink-payjoe-opbeleg'), null, $this->plugin_name);

    } // add_menu()

    function createTxtField($id, $section, $title, $info = '', $default = '')
    {
        $args = array(
            'info' => $info,
            'id' => $id,
            'default' => $default
        );
        add_settings_field($id, $title, array($this, 'display_txt_field'), $this->plugin_name, $section, $args);
        register_setting($section, $id);
    }

    function createDateField($id, $section, $title, $info = '', $default = '')
    {
        $args = array(
            'info' => $info,
            'id' => $id,
            'default' => $default
        );
        add_settings_field($id, $title, array($this, 'display_date_field'), $this->plugin_name, $section, $args);
        register_setting($section, $id);
    }

    function createSelectField($id, $section, $title, $info = '', $options = array(), $default_option = null)
    {
        $args = array(
            'info' => $info
        , 'id' => $id
        , 'options' => $options
        , 'default_option' => $default_option
        );
        add_settings_field($id, $title, array($this, "display_select_field"), $this->plugin_name, $section, $args);
        register_setting($section, $id);
    }


    function display_txt_field($args)
    {
        $value = !empty(get_option($args['id'])) ?get_option($args['id']) : $args['default'];
        ?>

        <input type="text" name="<?= $args['id'] ?>" id="<?= $args['id'] ?>"
               value="<?php echo $value ?>" size="65"/>
        <br><span class="description"> <?= $args['info']; ?> </span>

        <?php
    }

    function display_date_field($args)
    {
        $value = !empty(get_option($args['id'])) ?get_option($args['id']) : $args['default'];
        ?>

        <input type="date" name="<?= $args['id'] ?>" id="<?= $args['id'] ?>"
               value="<?php echo $value ?>" size="65"/>
        <br><span class="description"> <?= $args['info']; ?> </span>

        <?php
    }

    function display_select_field($args)
    {
        ?>
        <select name="<?= $args['id'] ?>" id="<?= $args['id'] ?>">
            <?php
            foreach ($args['options'] as $key => $val) {
                $compared_val = get_option($args['id']) ? get_option($args['id']) : $args['default_option'];
                $compared_val = ($compared_val) ? $compared_val : null;
                ?>
                <option value="<?php echo $key; ?>" <?php echo ($compared_val == $key) ? 'selected' : ''; ?> >
                    <?php echo $val; ?>
                </option>
                <?php
            }
            ?>
        </select>
        <br><span class="description"> <?= $args['info']; ?> </span>

        <?php
    }

    /*
     * ********************************************* START TEMPLATE ************************************************
    */


    /**
     * Creates the settings page
     *
     * @return        void
     * @since        1.0.0
     */
    public function get_settings_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'weslink-payjoe-opbeleg'));
        }

        ?>
        <div class="wrap">
            <h1><?php _e('PayJoe order upload', 'weslink-payjoe-opbeleg'); ?></h1>
            <span>
				<?php _e('PayJoe_info-text', 'weslink-payjoe-opbeleg'); ?>
			</span>
            <form method="post" action="options.php" id="<?= $this->plugin_name; ?>">
                <?php
                settings_fields("payjoe_section_basis");
                do_settings_sections($this->plugin_name);
                submit_button();
                ?>
            </form>

            <hr>
            <h1><?php _e('Manually invoice upload', 'weslink-payjoe-opbeleg'); ?></h1>
            <form method="post" action="admin.php?page=<?= $this->plugin_name; ?>" id="payjoe_testapi_form">
                <input type="hidden" id="payjoe_testapi" name="payjoe_testapi">
                <?php
                submit_button(__("Upload new invoices now", 'weslink-payjoe-opbeleg'));
                ?>
            </form>

            <hr>
            <h1><?php _e('Resend all invoices', 'weslink-payjoe-opbeleg'); ?></h1>
            <form method="post" action="admin.php?page=<?= $this->plugin_name; ?>" id="payjoe_testapi_form">
                <input type="hidden" id="payjoe_resend_invoices" name="payjoe_resend_invoices">
                <?php
                submit_button(__("Resend all invoices now", 'weslink-payjoe-opbeleg'));
                ?>
            </form>


        </div>
        <p class="wl-copyright">WordPress Support und Entwicklung durch <a href="https://weslink.de"
                                                                           title="Weslink - Let's web | Site Shop App"
                                                                           target="_blank">Weslink.de</a></p>
        <?php
    } // options_page()

    function init() {
        $this->register_cronjob('old', 'new');
    }

    function create_custom_schedule($schedules)
    {
        $payjoe_interval = get_option('payjoe_interval');

        if ($payjoe_interval) {
            $schedules['weslink-payjoe-opbeleg-custom-schedule'] = array(
                'interval' => $payjoe_interval * 60 * 60,
                'display' => __('Weslink-PayJoe-Opbeleg Custom Scheduled CronJob', 'weslink-payjoe-opbeleg')
            );
        }

        return $schedules;
    }

    function register_cronjob($old_value, $value, $option='')
    {
        if ($old_value != $value) {
            // remove existing cronjob
            wp_clear_scheduled_hook('weslink-payjoe-opbeleg-create-cronjob');
        }

        $is_settings_set = !!get_option('payjoe_zugangsid') && !!get_option('payjoe_apikey') && !!get_option('payjoe_username');

        if ($value && $is_settings_set) {
            $timestamp = wp_next_scheduled('weslink-payjoe-opbeleg-create-cronjob');

            if ($timestamp == false) {
                // create new one
                wp_schedule_event(time(), 'weslink-payjoe-opbeleg-custom-schedule', 'weslink-payjoe-opbeleg-create-cronjob');
            }
        }
    }

    // this one will be invoked by hook 'weslink-payjoe-opbeleg-create-cronjob'
    public function submit_order_to_api()
    {
        $enable_log = get_option('payjoe_log');
        if ($enable_log) {
            $file_log = get_payjoe_log_path();

            ob_start();

            $payjoe_orders = new Weslink_Payjoe_Opbeleg_Orders();
            $log_json_data = true;
            try {
                $payjoe_orders->getOrders($log_json_data);
            } catch (Exception $e) {
                echo 'Exception: ',  $e->getMessage(), "\n";
            }

            // Append a new info to the file
            $log_info = "\n\n" . str_repeat("-", 15) . "\n" . ob_get_contents();

            //$file_log = fopen( $file_log, "a" ) or die("Unable to open file!");
            $file_log = fopen($file_log, "a");
            fwrite($file_log, $log_info);
            fclose($file_log);

            ob_end_flush();
        } else {
            $payjoe_orders = new Weslink_Payjoe_Opbeleg_Orders();
            $log_json_data = false;
            try {
                $payjoe_orders->getOrders($log_json_data);
            } catch (Exception $e) {
                echo 'Exception: ',  $e->getMessage(), "\n";
            }
        }
    }

    // update option about latest processed invoice number
    function update_latest_processed_invoice_number($result_msg, $post_id, $invoice_number)
    {
        update_option('payjoe_startrenr', $invoice_number);
    }

    // update payjoe status
    function update_payjoe_status($result_msg, $post_id)
    {
        $transfer_ok = ($result_msg['error'] ? false : true);

        // update payjoe status
        if ($transfer_ok === true) {
            update_post_meta($post_id, '_payjoe_status', PAYJOE_STATUS_OK);
            update_post_meta($post_id, '_payjoe_error', '');
        } else if ($result_msg['error'] === 'Duplicate') {
            update_post_meta($post_id, '_payjoe_status', PAYJOE_STATUS_OK);
            update_post_meta($post_id, '_payjoe_error', '');
        } else {
            update_post_meta($post_id, '_payjoe_status', PAYJOE_STATUS_ERROR);
            update_post_meta($post_id, '_payjoe_error', $result_msg['error']);
        }
    }

    /**
     * Create additional PayJoe Status column for API SUBMISSION STATUS
     * @param array $columns shop order columns
     * @return array
     */
    public function add_payjoe_status_column($columns)
    {
        // put the column after the Status column
        $new_columns = array_slice($columns, 0, 2, true) +
            array('payjoe_status' => __('PayJoe Status', 'weslink-payjoe-opbeleg')) +
            array_slice($columns, 2, count($columns) - 1, true);
        return $new_columns;
    }

    /**
     * Display PayJoe Status in Shop Order column
     * @param string $column column slug
     */
    public function payjoe_status_column_data($column)
    {
        global $post;

        if ($column == 'payjoe_status') {
            $pj_status = get_post_meta($post->ID, '_payjoe_status', true);
            $pj_error = get_post_meta($post->ID, '_payjoe_error', true);
            $arr_status_msg = get_payjoe_status_list();

            $msg = $arr_status_msg[PAYJOE_STATUS_PENDING];

            if (array_key_exists($pj_status, $arr_status_msg)) {
                $msg = $arr_status_msg[$pj_status];

                if ($pj_status == PAYJOE_STATUS_ERROR) {
                    $msg = sprintf($msg, $pj_error);
                }
            }

            echo $msg;
        }
    }
}


// quick submitting invoices to API
if (isset($_POST['payjoe_testapi'])) {
    add_action('admin_notices', 'my_API_test_msg');
} else if (isset($_POST['payjoe_resend_invoices'])) {
    add_action('admin_notices', 'my_resend_invoices_msg');
}

function my_API_test_msg()
{
    $payjoe_admins = new Weslink_Payjoe_Opbeleg_Admin();

    ?>
    <div class="updated">
        <h3><?php _e('Result of order upload', 'weslink-payjoe-opbeleg'); ?></h3>
        <pre style="overflow: scroll; height: 565px; word-wrap: break-word;"><?php $payjoe_admins->submit_order_to_api(); ?></pre>
    </div>
    <?php
}

function my_resend_invoices_msg()
{
    $payjoe_admins = new Weslink_Payjoe_Opbeleg_Admin();
    $payjoe_orders = new Weslink_Payjoe_Opbeleg_Orders();

    ?>
    <div class="updated">
        <h3><?php _e('Result of order upload', 'weslink-payjoe-opbeleg'); ?></h3>
        <pre style="overflow: scroll; height: 565px; word-wrap: break-word;"><?php $payjoe_orders->setResendStatus(); //$payjoe_admins->submit_order_to_api();
            ?></pre>
    </div>
    <?php
}

// download log file
if (isset($_GET['payjoe_download_log_file'])) {
    // get file url path
    $full_path = get_payjoe_log_path();

    // flush file
    if (file_exists($full_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit();
    }
}
