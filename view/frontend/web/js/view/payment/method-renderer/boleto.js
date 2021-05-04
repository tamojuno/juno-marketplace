define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'jquery',
        'mage/translate'
    ],
    function (Component, quote, priceUtils, $, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'DigitalHub_Juno/payment/boleto'
            },

            initialize: function(){
                var self = this;
                this._super();
            },

            // initObservable: function () {
            //     this._super()
            //         .observe([
            //             // 'documentNumber'
            //         ]);
            //     return this;
            // },

            getData: function() {
                return {
                    method: this.getCode(),
                    additional_data: {
                        // 'document_number': this.documentNumber()
                    }
                };
            },

            isActive: function(){
                return true;
            },

            getGlobalConfig: function() {
                return window.checkoutConfig.payment.digitalhub_juno_global
            },

            getMethodConfig: function() {
                return window.checkoutConfig.payment.digitalhub_juno_boleto
            },

            beforePlaceOrder: function(){
                this.placeOrder();
            }
        });
    }
);
