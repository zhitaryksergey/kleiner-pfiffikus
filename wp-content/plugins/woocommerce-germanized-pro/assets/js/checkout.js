window.germanized = window.germanized || {};

( function( $, germanized ) {

    germanized.pro_checkout = {

        init: function() {

            $( document )
                .on( 'change', '#billing_vat_id, #shipping_vat_id', this.onChangeVatID )
                .on( 'change', '#ship-to-different-address-checkbox', this.onChangeShipToDifferentAddress );

            $( document.body ).on( 'updated_checkout', this.onUpdatedCheckout );

            this.showOrHideVatIdField();
        },

        onUpdatedCheckout: function( e, data ) {
            var $field      = $( '.woocommerce-checkout' ).find( '#billing_vat_id:visible, #shipping_vat_id:visible' ),
                $errors     = $( '.woocommerce-checkout' ).find( '.woocommerce-error' ),
                hasVatError = false;

            if ( $errors.length > 0 ) {
                $vatIdError     = $errors.find( '[data-id$="vat_id"]' );

                if ( $vatIdError.length > 0 ) {
                    var fieldId = $vatIdError.data( 'id' );
                    $field      = $( '.woocommerce-checkout' ).find( '#' + fieldId );
                    hasVatError = true;
                }
            }

            if ( $field.length > 0 && $field.is( ':input' ) ) {
                var $parent = $field.closest( '.form-row' );

                $parent.removeClass( 'woocommerce-validated woocommerce-invalid' );

                if ( hasVatError ) {
                    $parent.addClass( 'woocommerce-invalid' );
                } else {
                    $parent.addClass( 'woocommerce-validated' );
                }
            }
        },

        validateField: function( $field ) {
            var self = germanized.pro_checkout;

            if ( $field.length > 0 && $field.is( ':input' ) ) {
                var vatId   = $field.val(),
                    $parent = $field.closest( '.form-row' );

                if ( vatId.length > 0 ) {
                    if ( self.validateId( vatId ) ) {
                        $parent.removeClass( 'woocommerce-invalid' );
                    } else {
                        $parent.removeClass( 'woocommerce-validated' );
                    }
                }
            }
        },

        validateId: function( vatId ) {
            return /^(ATU[0-9]{8}|BE[01][0-9]{9}|BG[0-9]{9,10}|HR[0-9]{11}|CY[A-Z0-9]{9}|CZ[0-9]{8,10}|DK[0-9]{8}|EE[0-9]{9}|FI[0-9]{8}|FR[0-9A-Z]{2}[0-9]{9}|DE[0-9]{9}|EL[0-9]{9}|HU[0-9]{8}|IE([0-9]{7}[A-Z]{1,2}|[0-9][A-Z][0-9]{5}[A-Z])|IT[0-9]{11}|LV[0-9]{11}|LT([0-9]{9}|[0-9]{12})|LU[0-9]{8}|MT[0-9]{8}|NL[0-9]{9}B[0-9]{2}|PL[0-9]{10}|PT[0-9]{9}|RO[0-9]{2,10}|SK[0-9]{10}|SI[0-9]{8}|ES[A-Z]([0-9]{8}|[0-9]{7}[A-Z])|SE[0-9]{12}|GB([0-9]{9}|[0-9]{12}|GD[0-4][0-9]{2}|HA[5-9][0-9]{2}))$/.test( vatId );
        },

        onChangeVatID: function() {
            var self = germanized.pro_checkout;

            $( '.woocommerce-error, .woocommerce-message' ).remove();

            self.validateField( $( this ) );

            $( 'body' ).trigger( 'update_checkout' );
        },

        onChangeShipToDifferentAddress: function() {
            var self = germanized.pro_checkout;

            self.showOrHideVatIdField();
        },

        showOrHideVatIdField: function() {
            var self            = germanized.pro_checkout,
                $checkbox       = $( '#ship-to-different-address-checkbox' ),
                $billing_vat_id = $( '#billing_vat_id' );

            if ( $checkbox.is( ':checked' ) ) {
                // Backup real value
                $billing_vat_id.data( 'field-value', $billing_vat_id.val() );

                // Use placeholder value to make sure billing vat id wont throw empty errors
                $billing_vat_id.val( '1' ).parents( '.form-row' ).hide();

                self.onChangeVatID();
            } else {
                if ( ! $billing_vat_id.val() || $billing_vat_id.val() === '1' ) {
                    var oldVal = $billing_vat_id.data( 'field-value' );

                    $billing_vat_id.val( oldVal );
                }

                $billing_vat_id.parents( '.form-row' ).hide();

                var $wrapper    = $('.woocommerce-billing-fields');
                var country     = $( '#billing_country' ).val();

                $( document.body ).trigger( 'country_to_state_changing', [ country, $wrapper ] );
            }
        },
    };

    $( document ).ready( function() {
        germanized.pro_checkout.init();
    });

})( jQuery, window.germanized );