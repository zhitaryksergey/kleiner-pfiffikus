<?php
/**
 * The Template for displaying invoice download buttons on the myaccount page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/invoice/download.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/dokument/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $invoices;
?>
<div class="woocommerce-gzdp-invoice">
	<h3><?php echo _x( 'Download Invoices', 'invoices', 'woocommerce-germanized-pro' );?></h3>
	<?php foreach ( $invoices as $invoice ) : ?>
		<a class="button button-invoice-download" href="<?php echo wc_gzdp_get_invoice_download_url( $invoice->id );?>" target="_blank"><?php printf( _x( 'Download %s', 'invoices', 'woocommerce-germanized-pro' ), apply_filters( 'woocommerce_gzdp_invoice_download_title', $invoice->get_title(), $invoice ) ); ?></a>
	<?php endforeach; ?>
</div>