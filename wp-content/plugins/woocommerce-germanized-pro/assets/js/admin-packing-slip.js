window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.packing_slip = {

        params: {},

        init: function () {
            var self    = germanized.admin.packing_slip;
            self.params = wc_gzdp_admin_packing_slip_params;

            $( document )
                .on( 'click', '#panel-order-shipments .create-packing-slip:not(.disabled)', self.onCreatePackingSlip )
                .on( 'click', '#panel-order-shipments .remove-packing-slip', self.onRemovePackingSlip );

            $( document.body )
                .on( 'woocommerce_gzd_shipments_needs_saving', self.onShipmentsNeedsSavingChange )
                .on( 'init_tooltips', self.initTip );

            self.initTip();
        },

        initTip: function() {
            $( '.create-packing-slip' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            } );
        },

        onShipmentsNeedsSavingChange: function( e, needsSaving, currentShipmentId ) {
            var self      = germanized.admin.packing_slip,
                $shipment = self.getShipment( currentShipmentId );

            if ( needsSaving ) {
                self.disableCreatePackingSlip( $shipment );
            } else {
                self.enableCreatePackingSlip( $shipment );
            }
        },

        disableCreatePackingSlip: function( $shipment ) {
            var self    = germanized.admin.packing_slip,
                $button =  $shipment.find( '.create-packing-slip' );

            $button.addClass( 'disabled button-disabled' );
            $button.prop( 'title', self.params.i18n_create_packing_slip_disabled );

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );
        },

        enableCreatePackingSlip: function( $shipment ) {
            var self    = germanized.admin.packing_slip,
                $button =  $shipment.find( '.create-packing-slip' );

            $button.removeClass( 'disabled button-disabled' );
            $button.prop( 'title', self.params.i18n_create_packing_slip_enabled );

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );
        },

        getShipmentWrapperByPackingSlip: function( packingSlipId ) {
            var self       = germanized.admin.packing_slip,
                $wrapper   = $( '.wc-gzd-shipment-packing-slip[data-packing_slip="' + packingSlipId + '"]' );

            if ( $wrapper.length > 0 ) {
                return $wrapper.parents( '.order-shipment' );
            }

            return false;
        },

        getShipmentIdByPackingSlip: function( packingSlipId ) {
            var self       = germanized.admin.packing_slip,
                $wrapper   = $( '.wc-gzd-shipment-packing-slip[data-packing-slip="' + packingSlipId + '"]' );

            if ( $wrapper.length > 0 ) {
                return $wrapper.parents( '.order-shipment' ).data( 'shipment' );
            }

            return false;
        },

        removePackingSlip: function( packingSlipId ) {
            var self       = germanized.admin.packing_slip,
                $wrapper   = self.getShipmentWrapperByPackingSlip( packingSlipId );

            var params = {
                'action'      : 'woocommerce_gzdp_remove_packing_slip',
                'packing_slip': packingSlipId,
                'security'    : self.params.remove_packing_slip_nonce
            };

            if ( $wrapper ) {
                self.doAjax( params, $wrapper );
            }
        },

        onRemovePackingSlip: function() {
            var self          = germanized.admin.packing_slip,
                packingSlipId = $( this ).data( 'packing_slip' );

            var answer = window.confirm( self.params.i18n_remove_packing_slip_notice );

            if ( answer ) {
                self.removePackingSlip( packingSlipId );
            }

            return false;
        },

        doAjax: function( params, $wrapper, cSuccess, cError  ) {
            var self       = germanized.admin.packing_slip,
                shipments  = germanized.admin.shipments,
                $shipment  = $wrapper.hasClass( 'order-shipment' ) ? $wrapper : $wrapper.parents( '.order-shipment' ),
                shipmentId = $shipment.data( 'shipment' );

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.refresh_packing_slip_nonce;
            }

            if ( ! params.hasOwnProperty( 'shipment_id' ) ) {
                params['shipment_id'] = shipmentId;
            }

            $shipment.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $shipment.find( '.notice-wrapper' ).empty();

            $.ajax({
                type: "POST",
                url:  self.params.ajax_url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        $shipment.unblock();

                        if ( data.fragments ) {
                            $.each( data.fragments, function ( key, value ) {
                                $( key ).replaceWith( value );
                            });
                        }

                        cSuccess.apply( $shipment, [ data ] );
                    } else {
                        cError.apply( $shipment, [ data ] );

                        $shipment.unblock();

                        if ( data.hasOwnProperty( 'message' ) ) {
                            shipments.addNotice( data.message, 'error' );
                        } else if( data.hasOwnProperty( 'messages' ) ) {
                            $.each( data.messages, function( i, message ) {
                                shipments.addNotice( message, 'error' );
                            });
                        }
                    }
                },
                error: function( data ) {},
                dataType: 'json'
            });
        },

        onAjaxSuccess: function( data ) {},

        onAjaxError: function( data ) {},

        getShipment: function( id ) {
            return $( '#panel-order-shipments' ).find( '#shipment-' + id );
        },

        onCreatePackingSlip: function() {
            var self       = germanized.admin.packing_slip,
                shipmentId = $( this ).parents( '.order-shipment' ).data( 'shipment' );

            self.refreshPackingSlip( shipmentId );

            return false;
        },

        refreshPackingSlip: function( shipmentId ) {
            var self     = germanized.admin.packing_slip,
                $wrapper = self.getShipment( shipmentId );

            var params = {
                'action'      : 'woocommerce_gzdp_refresh_packing_slip',
                'shipment_id' : shipmentId,
                'security'    : self.params.refresh_packing_slip_nonce
            };

            if ( $wrapper ) {
                self.doAjax( params, $wrapper );
            }
        }
    };

    $( document ).ready( function() {
        germanized.admin.packing_slip.init();
    });

})( jQuery, window.germanized.admin );
