<?php

if ( ! defined( 'ABSPATH' ) )
    exit;

if ( ! class_exists( 'WC_GZDP_Email_Customer_Invoice_Simple' ) ) :

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
class WC_GZDP_Email_Customer_Invoice_Simple extends WC_Email {

    public $send_pdf = true;
    public $helper = null;
    public $invoice;

    public $template_html_no_pdf;
    public $template_plain_no_pdf;

    /**
     * Constructor
     */
    public function __construct() {

        $this->id             		= 'customer_invoice';

        $this->title          		= _x( 'Customer invoice', 'invoices', 'woocommerce-germanized-pro' );
        $this->description    		= _x( 'Customer invoice emails can be sent to the user containing PDF invoice as attachment.', 'invoices', 'woocommerce-germanized-pro' );

        $this->template_html  		= 'emails/customer-invoice-simple.php';
        $this->template_html_no_pdf = 'emails/customer-invoice.php';

        $this->template_plain  		 = 'emails/plain/customer-invoice-simple.php';
        $this->template_plain_no_pdf = 'emails/plain/customer-invoice.php';
        $this->helper                = wc_gzdp_get_email_helper( $this );

        $this->placeholders   = array(
            '{site_title}'   => $this->get_blogname(),
            '{order_number}' => '',
            '{order_date}'   => '',
        );

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

        // Make it an object if not yet
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

            // Look for the actual invoice
            if ( is_a( $object, 'WC_Order' ) ) {
                $this->send_pdf = false;
                $this->object   = $object;

                // Check if there are invoices
                $this->invoice = wc_gzdp_get_order_last_invoice( $object );

                if ( ! is_null( $this->invoice ) ) {
                    $this->send_pdf = true;
                }
            } else {
                $this->send_pdf = true;
                $this->object   = wc_get_order( $object->order );
                $this->invoice  = $object;
            }

            if ( $this->send_pdf ) {
                $recipient 			= $this->invoice->recipient;
                $this->recipient	= $recipient['mail'];
                $order_date         = false;

                if ( is_a( $this->object, 'WC_Order' ) ) {
                    $order_email = $this->object->get_billing_email();
                    $order_date  = $this->object->get_date_created();

                    if ( ! empty( $order_email ) ) {
                        $this->recipient = $order_email;
                    }
                }

                $this->placeholders['{invoice_number}'] = $this->invoice->get_title();
                $this->placeholders['{order_date}']     = $order_date ? wc_format_datetime( $order_date ) : '';
                $this->placeholders['{order_number}']   = $this->invoice->get_order_number();

                $this->invoice->mark_as_sent();
            } else {
                $this->recipient = $this->object->get_billing_email();

                $this->placeholders['{order_date}']     = wc_gzd_get_order_date( $this->object, wc_date_format() );
                $this->placeholders['{order_number}']   = $this->object->get_order_number();
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
        if ( $this->send_pdf ) {
            return apply_filters( 'woocommerce_email_subject_customer_invoice', $this->format_string( $this->get_option( 'subject', $this->get_default_subject( true ) ) ), $this->object, $this );
        } else {
            return apply_filters( 'woocommerce_email_subject_customer_invoice_no_pdf', $this->format_string( $this->get_option( 'subject_no_pdf', $this->get_default_subject( false ) ) ), $this->object, $this );
        }
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject( $pdf = true ) {
        if ( $pdf ) {
            return _x( '{invoice_number} for order {order_number} from {order_date}', 'invoices', 'woocommerce-germanized-pro' );
        } else {
            return _x( 'Invoice for order {order_number} from {order_date}', 'invoices', 'woocommerce-germanized-pro' );
        }
    }

    /**
     * get_heading function.
     *
     * @access public
     * @return string
     */
    public function get_heading() {
        if ( $this->send_pdf ) {
            return apply_filters( 'woocommerce_email_heading_customer_invoice', $this->format_string( $this->get_option( 'heading', $this->get_default_heading( true ) ) ), $this->object, $this );
        } else {
            return apply_filters( 'woocommerce_email_heading_customer_invoice_no_pdf', $this->format_string( $this->get_option( 'heading_no_pdf', $this->get_default_heading( false ) ) ), $this->object, $this );
        }
    }

    public function get_default_heading( $pdf = true ) {
        if ( $pdf ) {
            return _x( '{invoice_number} for order {order_number}', 'invoices', 'woocommerce-germanized-pro' );
        } else {
            return _x( 'Invoice for order {order_number}', 'invoices', 'woocommerce-germanized-pro' );
        }
    }

    public function get_attachments() {
        $attachments = parent::get_attachments();

        if ( $this->invoice ) {
            array_push( $attachments, $this->invoice->get_pdf_path() );
        }

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
        if ( ! $this->send_pdf ) {
            return $this->get_content_html_no_pdf();
        } else {
            return wc_get_template_html( $this->template_html, array(
                'invoice' 		     => $this->invoice,
                'order' 		     => $this->object,
                'show_pay_link'      => $this->get_option( 'show_pay_link' ),
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'			     => $this
            ) );
        }
    }

    public function get_content_html_no_pdf() {
        return wc_get_template_html( $this->template_html_no_pdf, array(
            'order' 		     => $this->object,
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
        if ( ! $this->send_pdf ) {
            return $this->get_content_plain_no_pdf();
        } else {
	        return wc_get_template_html( $this->template_plain, array(
                'invoice' 		     => $this->invoice,
                'order' 		     => $this->object,
                'show_pay_link'      => $this->get_option( 'show_pay_link' ),
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'			     => $this
            ) );
        }
    }

    public function get_content_plain_no_pdf() {
        return wc_get_template_html( $this->template_plain_no_pdf, array(
            'order' 		     => $this->object,
            'email_heading'      => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'sent_to_admin'      => false,
            'plain_text'         => true,
            'email'			     => $this
        ) );
    }

	/**
	 * Default content to show below main email content.
	 *
	 * @since 1.0.1
	 * @return string
	 */
	public function get_default_additional_content() {
		return '';
	}

    /**
     * Initialise settings form fields
     */
    public function init_form_fields() {

	    /* translators: %s: list of placeholders */
	    $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'woocommerce-germanized-pro' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'woocommerce-germanized-pro' ),
                'default' => 'yes'
            ),
            'subject' => array(
                'title'         => __( 'Email Subject', 'woocommerce-germanized-pro' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>{invoice_number}, {order_date}, {order_number}</code>' ),
                'placeholder'   => $this->get_default_subject(),
                'default'       => ''
            ),
            'heading' => array(
                'title'         => __( 'Email Heading', 'woocommerce-germanized-pro' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>{invoice_number}, {order_date}, {order_number}</code>' ),
                'placeholder'   => $this->get_default_heading(),
                'default'       => ''
            ),
            'show_pay_link' => array(
                'title'         => __( 'Show pay link', 'woocommerce-germanized-pro' ),
                'type'          => 'checkbox',
                'label'			=> __( 'Enable pay link in Email', 'woocommerce-germanized-pro' ),
                'description'   => __( 'Show order pay link in invoice PDF Email if order status is set to pending.', 'woocommerce-germanized-pro' ),
                'default'       => 'no'
            ),
            'subject_no_pdf' => array(
                'title'         => __( 'Email Subject (no PDF)', 'woocommerce-germanized-pro' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>{order_date}, {order_number}</code>' ),
                'placeholder'   => $this->get_default_subject( false ),
                'default'       => ''
            ),
            'heading_no_pdf' => array(
                'title'         => __( 'Email Heading (no PDF)', 'woocommerce-germanized-pro' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized-pro' ), '<code>{order_date}, {order_number}</code>' ),
                'placeholder'   => $this->get_default_heading( false ),
                'default'       => ''
            ),
            'additional_content' => array(
	            'title'       => __( 'Additional content', 'woocommerce-germanized-pro' ),
	            'description' => __( 'Text to appear below the main email content.', 'woocommerce-germanized-pro' ) . ' ' . $placeholder_text,
	            'css'         => 'width:400px; height: 75px;',
	            'placeholder' => __( 'N/A', 'woocommerce-germanized-pro' ),
	            'type'        => 'textarea',
	            'default'     => $this->get_default_additional_content(),
	            'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'woocommerce-germanized-pro' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'woocommerce-germanized-pro' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true
            )
        );

    }

}

endif;

return new WC_GZDP_Email_Customer_Invoice_Simple();