define([
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/url',
    'portmonePg'
], function (
    ko,
    $,
    Component,
    additionalValidators,
    placeOrderAction,
    fullScreenLoader,
    urlBuilder
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PortmonePayment_Portmone/payment/portmone'
        },

        initialize: function () {
            this._super();

            if ($('#portmone-payment-frame').length === 0) {
                $('<div>', {
                    id: 'portmone-payment-frame'
                }).appendTo('body');
            }

            return this;
        },

        // Перевіряє, чи увімкнено режим редіректу
        isRedirectMode: function () {
            var config = window.checkoutConfig.payment.portmone;

            return config && config.paymentMode === 'redirect';
        },

        // Перевіряє, чи увімкнено режим iframe
        isIframeMode: function () {
            var config = window.checkoutConfig.payment.portmone;

            return config && config.paymentMode === 'iframe';
        },

        // Повертає текст кнопки Оплатити через Portmone
        getButtonText: function () {
            return window.checkoutConfig.payment.portmone.buttonText;
        },

        // Перевіряє, чи увімкнено Розтермінування Redirect
        isInstallmentRedirect: function () {
            var config = window.checkoutConfig.payment.portmone;

            return config && config.installmentFlag === '1' && config.paymentMode === 'redirect';
        },


        // Перевіряє, чи увімкнено Розтермінування Iframe
        isInstallmentIframe: function () {
            var config = window.checkoutConfig.payment.portmone;

            return config && config.installmentFlag === '1' && config.paymentMode === 'iframe';
        },

        // Повертає текст кнопки Оплатити через Portmone (Розтермінування)
        getInstallmentButtonText: function () {
            return window.checkoutConfig.payment.portmone.buttonInstallmentText;
        },

        portmonePlaceOrder: function (paymentType, paymentMode) {
            var self = this;

            if (!additionalValidators.validate()) {
                return false;
            }

            // Блокуємо кнопку, щоб клієнт не натиснув двічі
            this.isPlaceOrderActionAllowed(false);
            fullScreenLoader.startLoader();

            // Викликаємо стандартний екшн створення замовлення Magento
            var placeOrder = placeOrderAction(
                this.getData(),
                this.messageContainer
            );

            $.when(placeOrder)
                .fail(function () {
                    // Сервер повернув помилку
                    self.isPlaceOrderActionAllowed(true);
                    fullScreenLoader.stopLoader();
                })
                .done(function (orderId) {
                    // Замовлення успішно створено

                    if (paymentMode === 'redirect') {
                        fullScreenLoader.stopLoader();
                        self.redirectToPortmone(orderId, paymentType);
                    }

                    if (paymentMode === 'iframe') {
                        self.iframePortmone(orderId, paymentType);
                    }
                });

            return false;
        },

        redirectToPortmone: function (orderId, paymentType) {
           // Отримуємо чистий базовий URL контролера
            var baseUrl = urlBuilder.build('portmone/redirect/index', {_secure: true});
            var redirectUrl = baseUrl + '?order_id=' + encodeURIComponent(orderId) + '&type=' + encodeURIComponent(paymentType);

            // Робимо перехід
            window.location.replace(redirectUrl);
        },

        iframePortmone: function (orderId, paymentType) {

            var self = this;

            $.ajax({
                url: urlBuilder.build('portmone/iframe/getData', {_secure: true}),
                type: 'POST',
                dataType: 'json',
                data: {
                    orderId: orderId,
                    paymentType: paymentType
                }
            }).done(function (response) {

                if (response.success && response.iframeData) {
                    var iframeData = response.iframeData;
                    iframeData.frameHolderId = 'portmone-payment-frame';

                    if (typeof PG !== 'undefined') {
                        var $hiddenBtn = $('#portmone-hidden-trigger-btn');

                        PG.setButtonId('portmone-hidden-trigger-btn');
                        PG.paymentData("gateway", iframeData, "frame");
                        PG.create();

                        fullScreenLoader.stopLoader();
                        window.setTimeout( function () {
                            $hiddenBtn.trigger('click');
                        }, 100);
                    }

                    return;
                }

                if (!response.success && response.message) {
                    fullScreenLoader.stopLoader();
                    self.messageContainer.addErrorMessage({
                        message: response.message
                    });

                    return;
                }

                fullScreenLoader.stopLoader();
                self.messageContainer.addErrorMessage({
                    message: window.checkoutConfig.payment.portmone.errorMessage.iframeGetDataResponse
                });
                console.error('Portmone: invalid payment data response');

            }).fail(function (jqXHR) {
                fullScreenLoader.stopLoader();
                self.messageContainer.addErrorMessage({
                    message: window.checkoutConfig.payment.portmone.errorMessage.iframeGetDataFail
                });
                console.error('Portmone: failed to get payment data', jqXHR);
            });
        }
    });
});