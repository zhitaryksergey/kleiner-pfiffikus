<?php
/**
 * The Template for sending the customer a PDF invoice cancellation by email.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/emails/customer-invoice-cancellation.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/dokument/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo wpautop( apply_filters( 'wc_gzdp_email_invoice_cancellation_text', sprintf( __( 'Hi there. An invoice to your order has been cancelled. For your reference please see %s to %s which we attached to this email.', 'woocommerce-germanized-pro' ), $invoice->get_title(), $invoice->parent->get_title() ), $invoice ) ); ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>