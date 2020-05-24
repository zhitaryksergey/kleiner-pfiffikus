jQuery( function( $ ) {

    if ( window.wc_gzdp_multistep_amazon_sca_helper !== undefined ) {

        /**
         * After refreshing the step, make sure we are resetting SCA to allow order placement.
         */
        $( 'body' ).bind( 'wc_gzdp_step_changed', function() {
            if ( $( '.step-wrapper-active' ).attr( 'id' ) === 'step-wrapper-order' ) {
                amazon_payments_advanced_params.is_sca = wc_gzdp_multistep_amazon_sca_helper.is_sca;
            }
        });

        /**
         * On refreshing step (when submitting) set SCA value to false in amazon payment params to prevent form submit.
         */
        $( document ).on( 'refresh', '.step-wrapper', function() {
            if ( $( this ).attr( 'id' ) === 'step-wrapper-order' ) {
                // Do nothing
            } else {
                amazon_payments_advanced_params.is_sca = false;
            }
        });
    }
});