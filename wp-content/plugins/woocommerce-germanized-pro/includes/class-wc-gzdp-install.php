<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZDP_Install' ) ) :

/**
 * Installation related functions and hooks
 *
 * @class 		WC_GZD_Install
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Install {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'setup_redirect' ), 10 );
	}

	public static function setup_redirect() {
        if ( get_option( '_wc_gzdp_setup_wizard_redirect' ) ) {

	        // Bail if activating from network, or bulk, or within an iFrame
	        if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
		        return;
	        }

	        if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce-germanized-pro.php' ) ) ) {
		        return;
	        }

            delete_option( '_wc_gzdp_setup_wizard_redirect' );
            wp_safe_redirect( admin_url( 'admin.php?page=wc-gzdp-setup' ) );
            exit();
        }
    }

	/**
	 * check_version function.
	 *
	 * @access public
	 * @return void
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_gzdp_version' ) != WC_germanized_pro()->version || get_option( 'woocommerce_gzdp_db_version' ) != WC_germanized_pro()->version ) ) {
			self::install();
		}
	}

	/**
	 * Install WC_Germanized
	 */
	public static function install() {
		self::create_options();
		
		// Queue upgrades
		$current_version    = get_option( 'woocommerce_gzdp_version', null );
		$current_db_version = get_option( 'woocommerce_gzdp_db_version', null );
		$is_install         = ( ! $current_version ) ? true : false;

		if ( ! $current_version ) {
            update_option( '_wc_gzdp_setup_wizard_redirect', 1 );
        } else {
			self::update();
        }

		WC_germanized_pro()->create_upload_folder();

		update_option( 'woocommerce_gzdp_db_version', WC_germanized_pro()->version );

		// Update version
		update_option( 'woocommerce_gzdp_version', WC_germanized_pro()->version );

		// Update activation date
		update_option( 'woocommerce_gzdp_activation_date', date( 'Y-m-d' ) );

		// Unregister installation
		delete_option( '_wc_gzdp_do_install' );

		if ( $is_install ) {
			do_action( 'woocommerce_gzdp_installed' );
		} else {
			do_action( 'woocommerce_gzdp_updated' );
		}
	}

	/**
	 * Handle updates
	 */
	public static function update() {
		
		$current_db_version = get_option( 'woocommerce_gzdp_db_version' );

		if ( version_compare( $current_db_version, '1.2.0', '<' ) )
			self::upgrade_invoice_path();
		elseif ( version_compare( $current_db_version, '1.4.0', '<' ) )
			self::upgrade_pdf_options();
		elseif ( version_compare( $current_db_version, '1.4.3', '<' ) )
			self::upgrade_1_4_2();
		elseif ( version_compare( $current_db_version, '1.7.0', '<' ) )
			self::upgrade_1_6_3();
		elseif ( version_compare( $current_db_version, '1.8.0', '<' ) )
			self::upgrade_invoice_path_suffix();
		elseif ( version_compare( $current_db_version, '1.8.6', '<' ) )
			self::upgrade_fonts_path_suffix();
		elseif ( version_compare( $current_db_version, '1.9.5', '<' ) )
			self::upgrade_1_9_5();
		elseif ( version_compare( $current_db_version, '1.9.6', '<' ) )
			self::upgrade_1_9_6();
		elseif( version_compare( $current_db_version, '2.0.0', '<' ) ) {
			self::upgrade_2_0_0();
		}
	}

	public static function upgrade_fonts_path_suffix() {
		$upload_dir = wp_upload_dir();
		$gzdp_dir = WC_germanized_pro()->filter_upload_dir( $upload_dir );

		// Cut off the suffix
		$path = substr( $gzdp_dir[ 'basedir' ], 0, -11 ) . '/fonts';
		$new_gzdp_dir = $gzdp_dir[ 'basedir' ] . '/fonts';

		if ( file_exists( $path ) && file_exists( $new_gzdp_dir ) ) {

			$files = @glob( $path . '/*.*' );

			foreach ( $files as $file ) {
				$file_to_go = str_replace( $path, $new_gzdp_dir, $file );
				@rename( $file, $file_to_go );
			}
		}
	}

	public static function upgrade_invoice_path_suffix() {

		$upload_dir = wp_upload_dir();
		$gzdp_dir = WC_germanized_pro()->filter_upload_dir( $upload_dir );

		// Cut off the suffix
		$path = substr( $gzdp_dir[ 'basedir' ], 0, -11 );
		$new_gzdp_dir = $gzdp_dir[ 'basedir' ];

		if ( file_exists( $path ) && ! file_exists( $new_gzdp_dir ) ) {
			// Now try to rename the folder
			if ( ! rename( $path, $new_gzdp_dir ) ) {
				update_option( '_wc_gzdp_invoice_dir_rename_failed', 'yes' );
			}
		}
	}

	public static function upgrade_1_9_6() {
		if ( 'yes' === get_option( 'woocommerce_gzdp_contract_after_confirmation' ) ) {
			update_option( 'woocommerce_gzd_email_order_confirmation_text', __( 'Your order has been processed. We are glad to confirm the order to you. Your order details are shown below for your reference.', 'woocommerce-germanized-pro' ) );
		}
	}

	public static function upgrade_2_0_0() {
		$packing_slip_format = get_option( 'woocommerce_gzdp_invoice_packing_slip_number_format' );

		// Replace {order_number} with {shipment_number} for packing slips
		if ( strpos( $packing_slip_format, '{order_number}' ) !== false ) {
			$packing_slip_format = str_replace( '{order_number}', '{shipment_number}', $packing_slip_format );

			update_option( 'woocommerce_gzdp_invoice_packing_slip_number_format', $packing_slip_format );
		}
	}

	public static function upgrade_1_9_5() {
		delete_transient( 'woocommerce_gzdp_generator_success_widerruf' );
		delete_transient( 'woocommerce_gzdp_generator_success_agbs' );

		delete_option( 'woocommerce_gzdp_generator_settings_widerruf' );
		delete_option( 'woocommerce_gzdp_generator_settings_agbs' );

		delete_option( 'woocommerce_gzdp_generator_widerruf' );
		delete_option( 'woocommerce_gzdp_generator_agbs' );
	}

	public static function upgrade_1_6_3() {

        // Do not allow cancellation auto generation on wc-refunded status (using partial cancellations instead)
	    if ( 'wc-refunded' === get_option( 'woocommerce_gzdp_invoice_cancellation_auto_status' ) ) {
	        update_option( 'woocommerce_gzdp_invoice_cancellation_auto_status', 'wc-cancelled' );
        }
    }

	public static function upgrade_1_4_2() {

		$options = array(
			'margins' => 'first_page_margins',
			'page_numbers_bottom' => 'first_page_page_numbers_bottom',
		);

		$types = array( 'invoice', 'legal_page' );

		foreach ( $options as $org => $option ) {

			foreach ( $types as $type ) {

				// Set Bottom Margin
				if ( $org === 'margins' ) {
					$margins = get_option( 'woocommerce_gzdp_' . $type . '_' . $org );
					if ( ! is_array( $margins ) )
						$margins = array( 15, 15, 15 );
					$margins[3] = 25;

					update_option( 'woocommerce_gzdp_' . $type . '_' . $org, $margins );
				}

				update_option( 'woocommerce_gzdp_' . $type . '_' . $option, get_option( 'woocommerce_gzdp_' . $type . '_' . $org ) );
			}

		}

		$invoices = array(
			'invoice' => _x( 'Invoice', 'invoices', 'woocommerce-germanized-pro' ),
			'invoice_cancellation' => _x( 'Cancellation', 'invoices', 'woocommerce-germanized-pro' ),
		);

		foreach ( $invoices as $invoice_type => $title ) {

			// Invoice email heading
			$invoice_settings = get_option( 'woocommerce_customer_' . $invoice_type . '_settings' );
			
			if ( $invoice_settings && is_array( $invoice_settings ) ) {

				$types = array( 'subject', 'heading' );

				foreach ( $types as $type ) {
					
					if ( isset( $invoice_settings[ $type ] ) ) {
						
						$invoice_settings[ $type ] = str_replace( $title . ' {invoice_number}', '{invoice_number}', $invoice_settings[ $type ] );
						
						if ( $invoice_type == 'invoice_cancellation' ) {
							$invoice_settings[ $type ] = str_replace( 'zur Rechnung {invoice_number_parent}', 'zu {invoice_number_parent}', $invoice_settings[ $type ] );
						}

					}
				}

				update_option( 'woocommerce_customer_' . $invoice_type . '_settings', $invoice_settings );

	 		}

 		}

	}

	public static function upgrade_pdf_options() {

		$rename = array(
			'woocommerce_gzdp_invoice_custom_font_names' => 'woocommerce_gzdp_pdf_custom_font_names',
			'woocommerce_gzdp_invoice_custom_fonts' => 'woocommerce_gzdp_invoice_custom_fonts',
			'woocommerce_gzdp_invoice_text_cancellation_after_table' => 'woocommerce_gzdp_invoice_cancellation_text_after_table',
			'woocommerce_gzdp_invoice_text_cancellation_before_table' => 'woocommerce_gzdp_invoice_cancellation_text_before_table',
			'woocommerce_gzdp_invoice_text_packing_slip_after_table' => 'woocommerce_gzdp_invoice_packing_slip_text_after_table',
			'woocommerce_gzdp_invoice_text_packing_slip_before_table' => 'woocommerce_gzdp_invoice_packing_slip_text_before_table',
		);

		foreach( $rename as $old => $new ) {
			if ( get_option( $old ) )
				update_option( $new, get_option( $old ) );
			delete_option( $old );
		}

	}

	public static function upgrade_invoice_path() {
		
		// Go through invoices
		$invoices = get_posts( array( 'post_type' => 'invoice', 'posts_per_page' => -1, 'post_status' => 'any' ) );
		
		if ( ! empty( $invoices ) ) {
		
			foreach ( $invoices as $invoice ) {
				
				if ( $attachment = get_post_meta( $invoice->ID, '_invoice_attachment', true ) ) {

					$file = get_attached_file( $attachment );

					if ( $file ) {

						$upload_dir = WC_germanized_pro()->get_upload_dir();
						
						WC_germanized_pro()->set_upload_dir_filter();
						$path = str_replace( array( WC_germanized_pro()->plugin_path() . '/uploads', $upload_dir[ 'basedir' ] ), '', get_attached_file( $attachment ) );
						WC_germanized_pro()->unset_upload_dir_filter();
						
						$path = ltrim( $path, '/' );

						update_post_meta( $attachment, '_wp_attached_file', $path );
					}

				}

			}

		}

	}

	/**
	 * Default options
	 *
	 * Sets up the default options used on the settings page
	 *
	 * @access public
	 */
	public static function create_options() {

		include_once( WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php' );
		include_once( WC_germanized_pro()->plugin_path() . '/includes/admin/settings/class-wc-gzdp-settings.php' );
		include_once( WC_germanized()->plugin_path() . '/includes/admin/settings/class-wc-gzd-settings-germanized.php' );
		
		$settings = new WC_GZD_Settings_Germanized();
		$options  = $settings->get_settings();

		foreach ( $options as $value ) {

			if ( isset( $value['id'] ) && strpos( $value['id'], 'gzdp' ) !== false && isset( $value['default'] ) ) {
				$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				add_option( str_replace( '[]', '', $value['id'] ), $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
			}
		}
	}
}

endif;

return new WC_GZDP_Install();
