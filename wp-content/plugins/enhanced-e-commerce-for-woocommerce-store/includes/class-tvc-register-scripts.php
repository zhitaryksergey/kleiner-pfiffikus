<?php
/**
 * TVC Register Scripts Class.
 *
 * @package TVC Product Feed Manager/Classes
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'TVC_Register_Scripts' ) ) :
    /**
     * Register Scripts Class
     */
    class TVC_Register_Scripts {
        // @private storage of scripts version
        private $_version_stamp;
        public function __construct() {
           $this->_version_stamp = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PLUGIN_TVC_VERSION;

            add_action( 'admin_enqueue_scripts', array( $this, 'tvc_register_required_nonce' ) );
            // only load the next hooks when on the Settings page
            if ( isset($_GET['page']) && $_GET['page'] == 'enhanced-ecommerce-google-analytics-admin-display' ) {
                add_action( 'admin_enqueue_scripts', array( $this, 'tvc_register_required_options_page_scripts' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'tvc_register_required_options_page_nonce' ) );
            }
        }
        
        /**
         * Generate the required nonce's.
         */
        public function tvc_register_required_nonce() {
            // make a unique nonce for all Ajax requests
            wp_localize_script(
                'tvc_script_ajax',
                'myAjaxNonces',
                array(
                    // URL to wp-admin/admin-ajax.php to process the request
                    'ajaxurl'                => admin_url( 'admin-ajax.php' ),
                    // generate the nonce's
                    'campaignCategoryListsNonce'     => wp_create_nonce( 'tvcajax-campaign-category-lists-nonce' ),
                    'campaignStatusNonce'     => wp_create_nonce( 'tvcajax-update-campaign-status-nonce' ),
                    'campaignDeleteNonce'     => wp_create_nonce( 'tvcajax-delete-campaign-nonce' ),
                    'categoryListsNonce'     => wp_create_nonce( 'tvcajax-category-lists-nonce' )
                )
            );
        }
        /**
         * Registers all required java scripts for the feed manager Settings page.
         */
        public function tvc_register_required_options_page_scripts() {
            // enqueue notice handling script
            wp_enqueue_script( 'tvc_data-handling-script', ENHANCAD_PLUGIN_URL . '/includes/data/js/tvc_ajaxdatahandling.js', array( 'jquery' ), $this->_version_stamp, true );
        }
        /**
         * Generate the nonce's for the Settings page.
         */
        public function tvc_register_required_options_page_nonce() {
            // make a unique nonce for all Ajax requests
            wp_localize_script(
                'tvc_data-handling-script',
                'myAjaxNonces',
                array(
                    // URL to wp-admin/admin-ajax.php to process the request
                    'ajaxurl' => admin_url( 'admin-ajax.php' )
                )
            );
        }
    }
// End of TVC_Register_Scripts class
endif;
$my_ajax_registration_class = new TVC_Register_Scripts();
?>