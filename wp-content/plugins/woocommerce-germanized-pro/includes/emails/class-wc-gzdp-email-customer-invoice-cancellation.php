<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'WC_GZDP_Email_Customer_Invoice_Cancellation' ) ) :

/**
 * Customer Invoice
 *
 * An email sent to the customer via admin.
 *
 * @class 		WC_Email_Customer_Invoice
 * @version		2.0.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 	WC_Email
 */
class WC_GZDP_Email_Customer_Invoice_Cancellation extends WC_Email {

	public $invoice;

	public $helper = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->id             = 'customer_invoice_cancellation';
		$this->title          = _x( 'Customer invoice cancellation', 'invoices', 'woocommerce-germanized-pro' );
		$this->description    = _x( 'Email contains the cancellation to an invoice/order.', 'invoices', 'woocommerce-germanized-pro' );

		$this->template_html  = 'emails/customer-invoice-cancellation.php';
		$this->template_plain = 'emails/plain/customer-invoice-cancellation.php';

		$this->placeholders   = array(
			'{site_title}'            => $this->get_blogname(),
			'{invoice_number_parent}' => '',
			'{invoice_number}'        => '',
		);

		$this->helper = wc_gzdp_get_email_helper( $this );

		// Call parent constructor
		parent::__construct();

		$this->customer_email = true;
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $object_id, $object = false ) {
		$this->helper->setup_locale();

		if ( false === $object ) {
			$object = is_numeric( $object_id ) ? get_post( $object_id ) : $object_id;

			if ( $object ) {
				if ( $object->post_type == 'shop_order' ) {
					$object = wc_get_order( $object->ID );
				} elseif ( $object->post_type == 'invoice' ) {
					$object = wc_gzdp_get_invoice( $object->ID );
				}
			}
		}

		if ( is_object( $object ) ) {
			
			$this->object = $object;
			
			// Look for the actual invoice
			if ( $object instanceof WC_Order ) {
				if ( $object->invoices ) {
					foreach ( $object->invoices as $invoice ) {
						$invoice = wc_gzdp_get_invoice( $invoice );
						if ( $invoice->is_type( 'cancellation' ) ) {
							$this->object = $invoice;
							break;
						}
					}
				}
			}

			if ( $this->object instanceof WC_GZDP_Invoice ) {
				$recipient 			= $this->object->recipient;
				$this->recipient	= $recipient['mail'];

				$this->placeholders['{invoice_number_parent}'] = $this->object->parent->get_title();
				$this->placeholders['{invoice_number}']        = $this->object->get_title();

				$this->object->mark_as_sent();
			}
		}

		$this->helper->setup_email_locale();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->helper->restore_email_locale();
		$this->helper->restore_locale();
	}

	/**
	 * get_subject function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'woocommerce_email_subject_customer_invoice_cancellation', $this->format_string( $this->get_option( 'subject', $this->get_default_subject() ) ), $this->object, $this );
	}

	public function get_default_subject() {
		return _x( '{invoice_number} to {invoice_number_parent}', 'invoices', 'woocommerce-germanized-pro' );
	}

	/**
	 * get_heading function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'woocommerce_email_heading_customer_invoice_cancellation', $this->format_string( $this->get_option( 'heading', $this->get_default_heading() ) ), $this->object, $this );
	}

	public function get_default_heading() {
		return _x( '{invoice_number} to {invoice_number_parent}', 'invoices', 'woocommerce-germanized-pro' );
	}

	public function get_attachments() {
		$attachments = parent::get_attachments();
		array_push( $attachments, $this->object->get_pdf_path() );
		return $attachments;
	}

	/**
	 * Return content from the additional_content field.
	 *
	 * Displayed above the footer.
	 *
	 * @since 2.0.4
	 * @return string
	 */
	public function get_additional_content() {
		if ( is_callable( 'parent::get_additional_content' ) ) {
			return parent::get_additional_content();
		}

		return '';
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'invoice' 		     => $this->object,
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'			     => $this
		) );
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'invoice' 		     => $this->object,
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'			     => $this
		) );
	}

	public function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields[ 'subject' ][ 'description' ] = sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>{invoice_number}, {invoice_number_parent}</code>' );
		$this->form_fields[ 'heading' ][ 'description' ] = sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>{invoice_number}, {invoice_number_parent}</code>' );
	}
}

endif;

return new WC_GZDP_Email_Customer_Invoice_Cancellation();