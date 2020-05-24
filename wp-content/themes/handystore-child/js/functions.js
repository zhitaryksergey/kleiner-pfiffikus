jQuery(document).ready(function ($) {
    $('.wc-proceed-to-checkout').clone().insertAfter('.woocommerce-notices-wrapper');

    var ean = $('.sku').text();
    console.log(ean);
    $('.wpcf7-form #ean').val(ean);
});
