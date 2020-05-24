<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZDP_WPML_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		
		if ( ! $this->is_activated() ) 
			return;

		add_action( 'init', array( $this, 'init' ), 10 );
	}

	public function init() {
		add_filter( 'woocommerce_gzd_wpml_translatable_options', array( $this, 'get_translatable_options' ), 10, 1 );
		add_action( 'woocommerce_gzd_reload_locale', array( $this, 'reload_locale' ) );

		add_action( 'woocommerce_gzdp_before_invoice_refresh', array( $this, 'add_invoice_translatable' ), 10, 1 );
        add_action( 'woocommerce_gzdp_invoice_maybe_update_language', array( $this, 'maybe_update_languages' ), 10, 2 );

        // Listen to language updates
        add_action( 'woocommerce_gzdp_invoice_language_update', array( $this, 'observe_invoice_language_update' ), 0, 2 );

        // Maybe restore language after refresh
		add_action( 'woocommerce_gzdp_before_pdf_save', array( $this, 'maybe_restore_language' ) );

		add_filter( 'woocommerce_gzd_wpml_email_ids', array( $this, 'register_emails' ), 10 );
		add_filter( 'wcml_emails_text_keys_to_translate', array( $this, 'add_custom_email_strings' ), 20, 2 );

		// Multistep step name refresh after init
		$this->refresh_step_names();
	}

	public function add_custom_email_strings( $keys ) {
		return array_merge( $keys, array( 'subject_no_pdf', 'heading_no_pdf' ) );
	}

	public function register_emails( $emails ) {
		$emails['WC_GZDP_Email_Customer_Invoice_Cancellation'] = 'customer_invoice_cancellation';
		$emails['WC_GZDP_Email_Customer_Order_Confirmation']   = 'customer_order_confirmation';

		return $emails;
	}

	public function get_translatable_options( $options ) {
		$gzdp_options = array(
			'woocommerce_gzdp_contract_helper_email_order_processing_text' => '',
			'woocommerce_gzdp_invoice_address'                             => '',
			'woocommerce_gzdp_invoice_address_detail'                      => '',
			'woocommerce_gzdp_invoice_text_before_table'                   => '',
			'woocommerce_gzdp_invoice_text_after_table'                    => '',
			'woocommerce_gzdp_invoice_number_format'                       => '',
			'woocommerce_gzdp_invoice_cancellation_number_format'          => '',
			'woocommerce_gzdp_invoice_packing_slip_number_format'          => '',
			'woocommerce_gzdp_invoice_cancellation_text_before_table'      => '',
			'woocommerce_gzdp_invoice_cancellation_text_after_table'       => '',
			'woocommerce_gzdp_invoice_packing_slip_text_before_table'      => '',
			'woocommerce_gzdp_invoice_packing_slip_text_after_table'       => '',
			'woocommerce_gzdp_invoice_reverse_charge_text'                 => '',
			'woocommerce_gzdp_checkout_step_title_address'                 => '',
			'woocommerce_gzdp_checkout_step_title_payment'                 => '',
			'woocommerce_gzdp_checkout_step_title_order'                   => '',
			'woocommerce_gzdp_legal_page_text_before_content'              => '',
			'woocommerce_gzdp_legal_page_text_after_content'               => '',
			'woocommerce_gzdp_checkout_privacy_policy_text'                => '',
			'woocommerce_gzdp_invoice_third_party_country_text'            => '',
			'woocommerce_gzdp_invoice_differential_taxation_notice_text'   => '',
			'woocommerce_gzdp_invoice_page_numbers_format'                 => '',
			'woocommerce_gzdp_legal_page_page_numbers_format'              => '',
		);

		return array_merge( $gzdp_options, $options );
	}

	/**
	 * @param $invoice
	 * @param WC_Order $order
	 */
	public function maybe_update_languages( $invoice, $order ) {
		$lang = null;

        if ( ! apply_filters( 'woocommerce_gzdp_wpml_translate_invoice', true ) ) {
            return;
        }

		if ( $lang = $order->get_meta( 'wpml_language', true ) ) {
			update_post_meta( $invoice->id, 'wpml_language', $lang );

			do_action( 'woocommerce_gzdp_invoice_language_update', $invoice, $lang );
		}
	}

	public function refresh_step_names() {
		if ( isset( WC_germanized_pro()->multistep_checkout ) ) {

			$step_names = WC_germanized_pro()->multistep_checkout->get_step_names();
			$steps      = WC_germanized_pro()->multistep_checkout->steps;

			foreach ( $steps as $key => $step ) {
				$step->title = $step_names[ $step->id ];
			}
		}
	}

	public function add_invoice_translatable( $invoice ) {
		global $sitepress;

        if ( ! $this->wpml_translate_invoices() ) {
            return;
        }
		
		if ( function_exists( 'wpml_add_translatable_content' ) ) {
			wpml_add_translatable_content( 'post_invoice', $invoice->id, ( get_post_meta( $invoice->id, 'wpml_language', true ) ) ? get_post_meta( $invoice->id, 'wpml_language', true ) : $sitepress->get_default_language() );
		}
	}

	public function reload_locale() {
        unload_textdomain( 'woocommerce-germanized-pro' );

		WC_germanized_pro()->load_plugin_textdomain();
	}

	public function get_gzd_compatibility() {
	    $gzd = WC_germanized();

	    if ( is_callable( array( $gzd, 'get_compatibility' ) ) ) {
	        return $gzd->get_compatibility( 'wpml' );
        }

        return false;
    }

    public function wpml_translate_invoices() {
        $default = false;

        return apply_filters( 'woocommerce_gzdp_wpml_translate_invoices', $default );
    }

	public function maybe_restore_language() {
        if ( $compatibility = $this->get_gzd_compatibility() ) {
            if ( is_callable( array( $compatibility, 'restore_language' ) ) ) {
                $compatibility->restore_language();
            }
        }
    }

	public function observe_invoice_language_update( $invoice, $language ) {
		$lang = null;

		if ( $lang = get_post_meta( $invoice->id, 'wpml_language', true ) ) {
			if ( $compatibility = $this->get_gzd_compatibility() ) {

                if ( ! $this->wpml_translate_invoices() ) {
                    // Force invoice generation in default language
                    global $sitepress;

                    if ( is_callable( array( $sitepress, 'get_default_language' ) ) ) {
                        $compatibility->set_language( $sitepress->get_default_language() );
                    }
                } else {
                    // Force invoice generation in current invoice language
                    $compatibility->set_language( $lang );
                }
            }
		}
	}

	public function is_activated() {
		return WC_GZDP_Dependencies::instance()->is_wpml_activated();
	}
}

return WC_GZDP_WPML_Helper::instance();