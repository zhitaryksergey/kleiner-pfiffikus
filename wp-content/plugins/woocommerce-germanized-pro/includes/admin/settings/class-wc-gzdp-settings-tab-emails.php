<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Multistep Checkout settings.
 *
 * @class 		WC_GZDP_Settings_Tab_Multistep_Checkout
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Settings_Tab_Emails extends WC_GZD_Settings_Tab_Emails {

	protected function get_attachment_settings() {
		$helper   = WC_GZDP_Legal_Page_Helper::instance();
		$settings = $helper->pdf_attachment_settings();

		return $settings;
	}

	protected function get_attachment_pdf_settings() {
		$helper   = WC_GZDP_Legal_Page_Helper::instance();
		$settings = $helper->get_settings();

		return $settings;
	}

	protected function before_save( $settings, $current_section = '' ) {
		if( 'attachments' === $current_section ) {
			$helper = WC_GZDP_Legal_Page_Helper::instance();
			$helper->section_before_save( $settings );
		}

		parent::before_save( $settings, $current_section );
	}

	protected function after_save( $settings, $current_section = '' ) {
		if ( 'attachments_pdf' === $current_section ) {
			$helper = WC_GZDP_Legal_Page_Helper::instance();

			$helper->check_pdf_template_version();
			$helper->regenerate_pdfs( $settings );
		}

		parent::after_save( $settings, $current_section );
	}
}