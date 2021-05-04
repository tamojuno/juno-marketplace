define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'digitalhub_juno_creditcard',
                component: 'DigitalHub_Juno/js/view/payment/method-renderer/creditcard'
            },
            {
                type: 'digitalhub_juno_boleto',
                component: 'DigitalHub_Juno/js/view/payment/method-renderer/boleto'
            }
        );
        return Component.extend({});
    }
);
