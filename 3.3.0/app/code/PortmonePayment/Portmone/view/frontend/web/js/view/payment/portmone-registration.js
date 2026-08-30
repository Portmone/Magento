
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
                type: 'portmone',
                component: 'PortmonePayment_Portmone/js/view/payment/method-renderer/portmone-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);