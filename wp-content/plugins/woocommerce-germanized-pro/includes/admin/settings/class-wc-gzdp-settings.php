<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Settings {

	protected static $_instance = null;

	public static function instance() {

	    if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

	    return self::$_instance;
	}

	public function __construct() {
	    add_filter( 'woocommerce_gzd_admin_settings_tabs', array( $this, 'register_tabs' ) );
	}

	public function register_tabs( $tabs ) {
		include_once dirname( __FILE__ ) . '/abstract-wc-gzdp-settings-tab-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-invoices.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-multistep-checkout.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-terms-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-revocation-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-contract.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-emails.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzdp-settings-tab-taxes.php';

		$tabs['invoices']             = 'WC_GZDP_Settings_Tab_Invoices';
		$tabs['multistep_checkout']   = 'WC_GZDP_Settings_Tab_Multistep_Checkout';
		$tabs['terms_generator']      = 'WC_GZDP_Settings_Tab_Terms_Generator';
		$tabs['revocation_generator'] = 'WC_GZDP_Settings_Tab_Revocation_Generator';
		$tabs['contract']             = 'WC_GZDP_Settings_Tab_Contract';
		$tabs['emails']               = 'WC_GZDP_Settings_Tab_Emails';
		$tabs['taxes']                = 'WC_GZDP_Settings_Tab_Taxes';

		return $tabs;
    }
}

return WC_GZDP_Settings::instance();