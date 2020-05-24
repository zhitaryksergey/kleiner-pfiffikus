<?php
/**
 * The Template for displaying invoice footer on the PDF invoice.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/invoice/footer.php.
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

?>

<table class="footer">
	<tr>
		<td colspan="2"></td>
	</tr>
	<tr>
		<td>
			<?php echo implode( '<br/>', $invoice->get_sender_address() ); ?>
		</td>
		<td>
			<?php echo do_shortcode( implode( '<br/>', $invoice->get_sender_address( 'detail' ) ) ); ?>
		</td>
	</tr>
</table>