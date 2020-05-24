<?php

use Vendidero\Germanized\Shipments\Admin\BulkActionHandler;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Admin_Packing_Slip_Bulk_Handler extends BulkActionHandler {

	protected $path = '';

	public function get_action() {
		return 'packing_slips';
	}

	public function get_limit() {
		return 1;
	}

	public function get_title() {
		return __( 'Generating packing slips...', 'woocommerce-germanizd-pro' );
	}

	public function get_file() {
		$file = get_user_option( $this->get_file_option_name() );

		if ( $file ) {
			$uploads  = WC_germanized_pro()->get_upload_dir();
			$path     = trailingslashit( $uploads['basedir'] ) . $file;

			return $path;
		}

		return '';
	}

	protected function update_file( $path ) {
		update_user_option( get_current_user_id(), $this->get_file_option_name(), $path );
	}

	protected function get_file_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_path";
	}

	protected function get_files_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_files";
	}

	protected function get_files() {
		$files = get_user_option( $this->get_files_option_name() );

		if ( empty( $files ) || ! is_array( $files ) ) {
			$files = array();
		}

		return $files;
	}

	protected function add_file( $path ) {
		$files   = $this->get_files();
		$files[] = $path;

		update_user_option( get_current_user_id(), $this->get_files_option_name(), $files );
	}

	public function reset( $is_new = false ) {
		parent::reset( $is_new );

		if ( $is_new ) {
			delete_user_option( get_current_user_id(), $this->get_file_option_name() );
			delete_user_option( get_current_user_id(), $this->get_files_option_name() );
		}
	}

	public function get_filename() {
		if ( $file = $this->get_file() ) {
			return basename( $file );
		}

		return '';
	}

	protected function get_download_button() {
		$download_button = '';

		if ( ( $path = $this->get_file() ) && file_exists( $path ) ) {

			$download_url = add_query_arg( array(
				'action'   => 'wc-gzdp-download-packing-slip-export',
				'force'    => 'no'
			), wp_nonce_url( admin_url(), 'wc-gzdp-download' ) );

			$download_button = '<a class="button button-primary bulk-download-button" style="margin-left: 1em;" href="' . $download_url . '" target="_blank">' . __( 'Download packing slips', 'woocommerce-germanized-pro' ) . '</a>';
		}

		return $download_button;
	}

	public function get_success_message() {
		$download_button = $this->get_download_button();

		return sprintf( __( 'Successfully generated packing slips. %s', 'woocommerce-germanized-pro' ), $download_button );
	}

	public function admin_after_error() {
		$download_button = $this->get_download_button();

		if ( ! empty( $download_button ) ) {
			echo '<div class="notice"><p>' . sprintf( __( 'Packing slips partially generated. %s', 'woocommerce-germanized-pro' ), $download_button ) . '</p></div>';
		}
	}

	public function is_last_step() {
		$current_step = (int) $this->get_step();
		$max_step     = (int) $this->get_max_step();

		if ( $max_step === $current_step ) {
			return true;
		}

		return false;
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach( $current as $shipment_id ) {
				$packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment_id );

				if ( ! $packing_slip ) {
					if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

						try {
							// Generate packing slip
							$args         = apply_filters( 'woocommerce_gzdp_packing_slips_defaults', array( 'invoice_status' => 'wc-gzdp-pending' ) );
							$packing_slip = wc_gzdp_get_invoice( false, 'packing_slip' );

							$packing_slip->refresh( $args, $shipment );

						} catch( Exception $e ) {
							$this->add_notice( sprintf( __( 'Error while creating packing slip for %s.', 'woocommerce-germanized-pro' ), '<a href="' . $shipment->get_edit_shipment_url() .'" target="_blank">' . sprintf( __( 'shipment #%d', 'woocommerce-germanized-dhl' ), $shipment_id ) . '</a>' ), 'error' );
						}
					}
				}

				// Merge to bulk print/download
				if ( $packing_slip ) {
					$this->add_file( $packing_slip->get_pdf_path() );
				}
			}
		}

		if ( $this->is_last_step() ) {

			try {
				require_once( WC_germanized_pro()->plugin_path() . '/includes/class-wc-gzdp-pdf-merger.php' );

				$pdf_merger = new WC_GZDP_PDF_Merger();
				$filename   = apply_filters( 'woocommerce_gzdp_packing_slip_bulk_filename', 'packing-slip-export.pdf', $this );

				foreach( $this->get_files() as $file ) {

					if ( ! file_exists( $file ) ) {
						continue;
					}

					$pdf_merger->add_pdf( $file, 'all' );
				}

				if ( $file = $pdf_merger->merge( 'string', $filename ) ) {

					if ( $path = wc_gzdp_upload_file( $filename, $file, true, true ) ) {
						$this->update_file( $path );
					}
				}

			} catch( Exception $e ) {}
		}

		$this->update_notices();
	}
}