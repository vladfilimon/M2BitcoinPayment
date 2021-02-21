define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'vladfilimon_m2bitcoinpayment',
                component: 'VladFilimon_M2BitcoinPayment/js/view/payment/method-renderer/bitcoin'
            }
        );
        return Component.extend({});
    }
);
