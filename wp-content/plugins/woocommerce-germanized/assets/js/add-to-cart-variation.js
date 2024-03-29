/*global wc_gzd_add_to_cart_variation_params */
;(function ( $, window, document, undefined ) {
    /**
     * VariationForm class which handles variation forms and attributes.
     */
    var GermanizedVariationForm = function( $form ) {
        var self = this;

        self.$form                = $form;
        self.$wrapper             = $form.closest( wc_gzd_add_to_cart_variation_params.wrapper );
        self.$product             = $form.closest( '.product' );
        self.variationData        = $form.data( 'product_variations' );
        self.$singleVariation     = $form.find( '.single_variation' );
        self.$singleVariationWrap = $form.find( '.single_variation_wrap' );
        self.$resetVariations     = $form.find( '.reset_variations' );
        self.$button              = $form.find( '.single_add_to_cart_button' );

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        self.replacePrice = self.$wrapper.hasClass( 'bundled_product' ) ? false : wc_gzd_add_to_cart_variation_params.replace_price;

        $form.on( 'click', '.reset_variations', { GermanizedvariationForm: self }, self.onReset );
        $form.on( 'reset_data', { GermanizedvariationForm: self }, self.onReset );
        $form.on( 'show_variation', { GermanizedvariationForm: self }, self.onShowVariation );
    };

    GermanizedVariationForm.prototype.getPriceElement = function( self ) {
        var $wrapper = self.$wrapper;

        /**
         * Ignore the price wrapper inside the variation form to make sure the right
         * price is being replaced even if the price element is located beneath the form.
         */
        return $wrapper.find( wc_gzd_add_to_cart_variation_params.price_selector + ':not(.price-unit):visible' ).not( '.variations_form .price' ).first();
    };

    /**
     * Reset all fields.
     */
    GermanizedVariationForm.prototype.onReset = function( event ) {
        var form     = event.data.GermanizedvariationForm,
            $wrapper = form.$wrapper;

        if ( $wrapper.find( '.org_price' ).length > 0 && form.replacePrice ) {
            $wrapper.find( wc_gzd_add_to_cart_variation_params.price_selector + '.variation_modified:not(.price-unit)' ).html( $wrapper.find( '.org_price' ).html() ).removeClass( 'variation_modified' ).show();
        }

        if ( $wrapper.find( '.org_delivery_time' ).length > 0 ) {
            $wrapper.find( '.delivery-time-info:first' ).html( $wrapper.find( '.org_delivery_time' ).html() ).removeClass( 'variation_modified' ).show();

            if ( $wrapper.find( '.delivery-time-info:first' ).is( ':empty' ) ) {
                $wrapper.find( '.delivery-time-info:first' ).addClass( 'wc-gzd-additional-info-placeholder' ).hide();
            }
        }

        if ( $wrapper.find( '.org_unit_price' ).length > 0 ) {
            $wrapper.find( '.price-unit:first' ).html( $wrapper.find( '.org_unit_price' ).html() ).removeClass( 'variation_modified' ).show();

            if ( $wrapper.find( '.price-unit:first' ).is( ':empty' ) ) {
                $wrapper.find( '.price-unit:first' ).addClass( 'wc-gzd-additional-info-placeholder' ).hide();
            }
        }

        if ( $wrapper.find( '.org_tax_info' ).length > 0 ) {
            $wrapper.find( '.tax-info:first' ).html( $wrapper.find( '.org_tax_info' ).html() ).removeClass( 'variation_modified' ).show();

            if ( $wrapper.find( '.tax-info:first' ).is( ':empty' ) ) {
                $wrapper.find( '.tax-info:first' ).addClass( 'wc-gzd-additional-info-placeholder' ).hide();
            }
        }

        if ( $wrapper.find( '.org_defect_description' ).length > 0 ) {
            $wrapper.find( '.defect-description:first' ).html( $wrapper.find( '.org_defect_description' ).html() ).removeClass( 'variation_modified' ).show();

            if ( $wrapper.find( '.defect-description:first' ).is( ':empty' ) ) {
                $wrapper.find( '.defect-description:first' ).addClass( 'wc-gzd-additional-info-placeholder' ).hide();
            }
        }

        if ( $wrapper.find( '.org_shipping_costs_info' ).length > 0 ) {
            $wrapper.find( '.shipping-costs-info:first' ).html( $wrapper.find( '.org_shipping_costs_info' ).html() ).removeClass( 'variation_modified' ).show();

            if ( $wrapper.find( '.shipping-costs-info:first' ).is( ':empty' ) ) {
                $wrapper.find( '.shipping-costs-info:first' ).addClass( 'wc-gzd-additional-info-placeholder' ).hide();
            }
        }

        if ( $wrapper.find( '.org_product_units' ).length > 0 ) {
            $wrapper.find( '.product-units:first' ).html( $wrapper.find( '.org_product_units' ).html() ).removeClass( 'variation_modified' ).show();

            if ( $wrapper.find( '.product-units:first' ).is( ':empty' ) ) {
                $wrapper.find( '.product-units:first' ).addClass( 'wc-gzd-additional-info-placeholder' ).hide();
            }
        }

        $wrapper.find( '.org_product_info' ).remove();
        $wrapper.find( '.variation_modified' ).remove();

        event.data.GermanizedvariationForm.$form.trigger( 'germanized_reset_data' );
    };

    GermanizedVariationForm.prototype.onUpdate = function( event ) {

        setTimeout( function() {
            if( typeof event.data === 'undefined' || ! event.data.hasOwnProperty( 'GermanizedvariationForm' ) ) {
                return;
            } else if ( typeof event.data.GermanizedvariationForm === 'undefined' ) {
                return;
            }

            // If the button is diabled (or has disabled class) no variation can be added to the cart - reset has been triggered
            if ( event.data.GermanizedvariationForm.$button.is( '[disabled]' ) || event.data.GermanizedvariationForm.$button.hasClass( 'disabled' ) ) {
                event.data.GermanizedvariationForm.onReset( event );
            }
        }, 250);
    };

    GermanizedVariationForm.prototype.onShowVariation = function( event, variation, purchasable ) {
        var form            = event.data.GermanizedvariationForm,
            $wrapper        = form.$wrapper,
            hasCustomPrice  = variation.hasOwnProperty( 'price_html' ) && variation.price_html !== '',
            hasDisplayPrice = variation.hasOwnProperty( 'display_price' ) && variation.display_price !== '';
        
        if ( $wrapper.find( '.org_product_info' ).length <= 0 ) {

            $wrapper.append( '<div class="org_price org_product_info">' + form.getPriceElement( form ).html() + '</div>' );

            if ( $wrapper.find( '.delivery-time-info:first' ).length > 0 ) {
                $wrapper.append( '<div class="org_delivery_time org_product_info">' + $wrapper.find( '.delivery-time-info:first' ).html() + '</div>' );
            }

            if ( $wrapper.find( '.defect-description:first' ).length > 0 ) {
                $wrapper.append( '<div class="org_defect_description org_product_info">' + $wrapper.find( '.defect-description:first' ).html() + '</div>' );
            }

            if ( $wrapper.find( '.tax-info:first' ).length > 0 ) {
                $wrapper.append( '<div class="org_tax_info org_product_info">' + $wrapper.find( '.tax-info:first' ).html() + '</div>' );
            }

            if ( $wrapper.find( '.shipping-costs-info:first' ).length > 0 ) {
                $wrapper.append( '<div class="org_shipping_costs_info org_product_info">' + $wrapper.find( '.shipping-costs-info:first' ).html() + '</div>' );
            }

            if ( $wrapper.find( '.price-unit:first' ).length > 0 ) {
                $wrapper.append( '<div class="org_unit_price org_product_info">' + $wrapper.find( '.price-unit:first' ).html() + '</div>' );
            }

            if ( $wrapper.find( '.product-units:first' ).length > 0 ) {
                $wrapper.append( '<div class="org_product_units org_product_info">' + $wrapper.find( '.product-units:first' ).html() + '</div>' );
            }

            $wrapper.find( '.org_product_info' ).hide();
        }

        if ( hasCustomPrice && form.replacePrice ) {
            var $priceElement = form.getPriceElement( form );

            form.$singleVariation.find( '.price' ).hide();

            $priceElement.html( variation.price_html ).addClass( 'variation_modified' );
            $priceElement.find( '.price' ).contents().unwrap();
        }

        $wrapper.find( '.delivery-time-info:first' ).hide();
        $wrapper.find( '.defect-description:first' ).hide();
        $wrapper.find( '.price-unit:first' ).hide();
        $wrapper.find( '.tax-info:first' ).hide();
        $wrapper.find( '.shipping-costs-info:first' ).hide();
        $wrapper.find( '.product-units:first' ).hide();

        if ( variation.delivery_time !== '' ) {
            $wrapper.find( 'p.delivery-time-info:first' ).html( variation.delivery_time ).addClass( 'variation_modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();
        }

        if ( variation.defect_description !== '' ) {
            $wrapper.find( '.defect-description:first' ).html( variation.defect_description ).addClass( 'variation_modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();
        }

        if ( variation.tax_info !== '' && hasDisplayPrice ) {
            $wrapper.find( '.tax-info:first' ).html( variation.tax_info ).addClass('variation_modified').removeClass( 'wc-gzd-additional-info-placeholder' ).show();
        }

        if ( variation.shipping_costs_info !== '' && hasDisplayPrice ) {
            $wrapper.find( '.shipping-costs-info:first' ).html( variation.shipping_costs_info ).addClass( 'variation_modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();
        }

        if ( variation.unit_price !== '' && hasDisplayPrice ) {
            // Check if unit price for variable product exists and replace instead of insert
            if ( $wrapper.find( '.price-unit:first' ).length ) {
                $wrapper.find( '.price-unit:first' ).html( variation.unit_price ).addClass( 'variation-modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();
            } else {
                $wrapper.find( '.price-unit:first' ).remove();
                $wrapper.find( 'p.price:first' ).after( '<p class="price price-unit smaller variation_modified">' + variation.unit_price + '</p>' ).show();
            }
        }

        if ( variation.product_units !== '' ) {
            // Check if product units for variable product exist and replace instead of insert
            if ( $wrapper.find( '.product-units:first' ).length ) {
                $wrapper.find( '.product-units:first' ).html( variation.product_units ).addClass( 'variation-modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();
            } else {
                $wrapper.find( '.product-units:first' ).remove();
                $wrapper.find( '.product_meta:first' ).prepend( '<p class="wc-gzd-additional-info product-units-wrapper product-units variation_modified">' + variation.product_units + '</p>' ).show();
            }
        }

        form.$form.trigger( 'germanized_variation_data', variation, $wrapper );
    };

    /**
     * Function to call wc_gzd_variation_form on jquery selector.
     */
    $.fn.wc_germanized_variation_form = function() {
        new GermanizedVariationForm( this );
        return this;
    };

    $( function() {
        if ( typeof wc_gzd_add_to_cart_variation_params !== 'undefined' ) {
            $( '.variations_form' ).each( function() {
                $( this ).wc_germanized_variation_form();
            });
        }
    });

})( jQuery, window, document );