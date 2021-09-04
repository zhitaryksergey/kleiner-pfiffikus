<?php
/**
 * Stock Manager
 *
 * @package  woocommerce-stock-manager/public/
 * @version  2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Stock_Manager {

	/**
	 * Plugin slug
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'stock-manager';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		add_action( 'init', array( $this, 'output_buffer' ) );
		   
		add_action( 'init', array( $this, 'create_table' ) );

		add_action( 'woocommerce_product_set_stock', array( $this, 'save_stock' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'save_stock' ) );
    
	}                   

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

	}

	/**
	 * Headers allready sent fix
	 *
	 */        
  	public function output_buffer() {
		ob_start();
	}
	  
	/**
     * Create table if not exists
     *
     * @since    2.0.0
     */
    public function create_table() {

		global $wpdb;
	
		$wpdb->hide_errors();
	
		$collate = '';
	
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty($wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		$table = "
            CREATE TABLE {$wpdb->prefix}stock_log (
                ID bigint(255) NOT NULL AUTO_INCREMENT,
                date_created datetime NOT NULL,
                product_id bigint(255) NOT NULL,
                qty int(10) NOT NULL,
                PRIMARY KEY  (`ID`)
            ) $collate;
        ";
		dbDelta( $table );

	}

	/**
     * Save stock change
     *
     * @since    2.0.0
     */
    public function save_stock( $product ) {

		global $wpdb;

		$data                 = array();
		$data['date_created'] = date( 'Y-m-d H:i:s', time() );
		$data['qty']          = $product->get_stock_quantity();
		$data['qty']          = ( empty( $data['qty'] ) ) ? 0 : intval( $data['qty'] );
		$data['product_id']   = $product->get_id();
		
		$wpdb->insert( $wpdb->prefix.'stock_log', $data );

	}

}//End class
