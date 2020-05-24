<?php
/**
 * The Template for displaying legal page text for the PDF attachment.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/legal-page/text.php.
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
<?php echo $legal_page->get_content_pdf(); ?>