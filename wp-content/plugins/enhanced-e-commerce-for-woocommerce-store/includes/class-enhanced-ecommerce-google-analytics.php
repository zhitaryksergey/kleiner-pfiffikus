<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       tatvic.com
 * @since      1.0.0
 *
 * @package    Enhanced_Ecommerce_Google_Analytics
 * @subpackage Enhanced_Ecommerce_Google_Analytics/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Enhanced_Ecommerce_Google_Analytics
 * @subpackage Enhanced_Ecommerce_Google_Analytics/includes
 * @author     Tatvic
 */
class Enhanced_Ecommerce_Google_Analytics {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Enhanced_Ecommerce_Google_Analytics_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'PLUGIN_TVC_VERSION' ) ) {
            $this->version = PLUGIN_TVC_VERSION;
        } else {
            $this->version = '2.0';
        }
        $this->plugin_name = 'enhanced-ecommerce-google-analytics';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->check_dependency();
        add_filter( 'plugin_action_links_' .plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' ), array($this,'tvc_plugin_action_links'),10 );
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Enhanced_Ecommerce_Google_Analytics_Loader. Orchestrates the hooks of the plugin.
     * - Enhanced_Ecommerce_Google_Analytics_i18n. Defines internationalization functionality.
     * - Enhanced_Ecommerce_Google_Analytics_Admin. Defines all hooks for the admin area.
     * - Enhanced_Ecommerce_Google_Analytics_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-enhanced-ecommerce-google-analytics-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-enhanced-ecommerce-google-analytics-i18n.php'; 
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-tvc-admin-db-helper.php';
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/data/class-tvc-ajax-calls.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/data/class-tvc-ajax-file.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/data/class-tvc-taxonomies.php';
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tvc-register-scripts.php';
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-enhanced-ecommerce-google-analytics-admin.php';

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-enhanced-ecommerce-google-analytics-settings.php';
        /**
          * New conversios UI file list
          */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-conversios-onboarding.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helper/class-onboarding-helper.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helper/class-dashboard-helper.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-conversios-admin.php';
        /**
          * End New conversios UI file list
          */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-tvc-admin-auto-product-sync-helper.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-survey.php';
        


        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */

        $TVC_Admin_Helper = new TVC_Admin_Helper();
        $plan_id = $TVC_Admin_Helper->get_plan_id();
        if($plan_id == 1){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-enhanced-ecommerce-google-analytics-public.php';
        }else{
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-enhanced-ecommerce-google-analytics-public-pro.php';
        }
        $this->loader = new Enhanced_Ecommerce_Google_Analytics_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the  Enhanced_Ecommerce_Google_Analytics_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Enhanced_Ecommerce_Google_Analytics_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Enhanced_Ecommerce_Google_Analytics_Admin( $this->get_plugin_name(), $this->get_version() );
        //$this->loader->add_action( 'admin_menu', $plugin_admin, 'display_admin_page' );
        // $this->loader->add_action("admin_menu", $plugin_admin, "add_new_menu");
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'tvc_admin_notice' );
        if ( is_admin() ) {
            new TVC_Survey( "Enhanced ecommerce google analytics plugin for woocommerce", ENHANCAD_PLUGIN_NAME );
        }

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Enhanced_Ecommerce_Google_Analytics_Public( $this->get_plugin_name(), $this->get_version() );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            add_action('woocommerce_init' , function (){
                $this->loader->run();
            });
        }
        else{
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                $this->loader->run();
            }else if( is_plugin_active( 'enhanced-e-commerce-for-woocommerce-store/enhanced-ecommerce-google-analytics.php' ) ){
                printf('<div class="notice notice-error"><p>Hey, It seems WooCommerce plugin is not active on your wp-admin. Enhanced ecommerce plugin can only be activated if you have active WooCommerce plugin in your wp-admin.</p></div>');
            }
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return     Enhanced_Ecommerce_Google_Analytics_Loader    Orchestrates the hooks of the plugin.
     */

    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */

    public function get_version() {
        return $this->version;
    }

    public function tvc_plugin_action_links($links) {
        $deactivate_link = $links['deactivate'];
        unset($links['deactivate']);
        $setting_url = 'admin.php?page=conversios-google-analytics';
        $links[] = '<a href="' . get_admin_url(null, $setting_url) . '">Settings</a>';
        
        $links[] = '<a href="https://wordpress.org/plugins/enhanced-e-commerce-for-woocommerce-store/#faq" target="_blank">FAQ</a>';
        $links[] = '<a href="https://conversios.io/help-center/Installation-Manual.pdf" target="_blank">Documentation</a>';
        $links[] = '<a href="https://conversios.io/pricings/?utm_source=EE+Plugin+User+Interface&utm_medium=Plugins+Listing+Page+Upgrade+to+Premium&utm_campaign=Upsell+at+Conversios" target="_blank"><b>Upgrade to Premium</b></a>';
        $links['deactivate'] = $deactivate_link;
        return $links;
    }

    /**
     * Check Enhance E-commerce Plugin is Activated
     * Free Plugin
     */

    public function check_dependency(){
        if ( function_exists('run_actionable_google_analytics')) {
            _e('<div class="error"><p><strong>'. wp_sprintf( 'Note: ' ) .'</strong>'. wp_sprintf( 'It seems <strong>Actionable Google Analytics Plugin</strong> is active on your store. Kindly deactivate it in order to avoid data duplication in GA.' ) .'</p></div>');
            die();
        }
    }
}