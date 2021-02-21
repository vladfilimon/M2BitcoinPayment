define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'VladFilimon_M2BitcoinPayment/payment/bitcoin'
            },
            redirectAfterPlaceOrder: false, // Redirect will be performed to bitcoin payment page form here rather than success page
            afterPlaceOrder: function () {
                window.location.replace(url.build('checkout/index/bitcoinPayment'));
            },
        });
    }
);
