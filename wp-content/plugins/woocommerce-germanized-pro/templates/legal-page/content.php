<?php
/**
 * The Template for displaying legal page content for the PDF attachment.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/legal-page/content.php.
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

$newY = $pdf->getY();

?>

<?php if ( $legal_page->get_option( 'title_margin_top' ) ) : ?>
	<?php $pdf->setY( $pdf->getY() + $legal_page->get_option( 'title_margin_top' ) ); ?>
<?php endif; ?>

<?php if ( $legal_page->get_option( 'show_title' ) === 'yes' ) : ?>
	<?php $pdf->writeCustomHTML( '<h1>' . $legal_page->get_title_pdf() . '</h1>', array( 'classes' => array( 'title' ) ) ); ?>
<?php endif; ?>

<?php if ( $legal_page->get_option( 'content_margin_top' ) ) : ?>
	<?php $pdf->setY( $pdf->getY() + $legal_page->get_option( 'content_margin_top' ) ); ?>
<?php endif; ?>

<?php if ( $legal_page->get_static_pdf_text( 'before_content' ) ) : ?>
	<?php $pdf->writeCustomHTML( $legal_page->get_static_pdf_text( 'before_content' ), array( 'classes' => array( 'static' ) ) ); ?>
<?php endif; ?>

<?php $pdf->writeCustomHTML( $legal_page->get_template_content( $legal_page->locate_template( 'text.php' ), $pdf ), array( 'classes' => array( 'content' ) ) ); ?>

<?php if ( $legal_page->get_static_pdf_text( 'after_content' ) ) : ?>
	<?php $pdf->writeCustomHTML( $legal_page->get_static_pdf_text( 'after_content' ), array( 'classes' => array( 'static' ) ) ); ?>
<?php endif; ?>