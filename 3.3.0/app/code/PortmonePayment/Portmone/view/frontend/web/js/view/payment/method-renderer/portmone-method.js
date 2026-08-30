define([
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/url' // 7-й елемент у масиві define
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
            var config = window.checkoutConfig.payment.portmone;

            return (config && config.buttonText)
                ? config.buttonText
                : 'Оплатити';
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
            var config = window.checkoutConfig.payment.portmone;

            return (config && config.buttonInstallmentText)
                ? config.buttonInstallmentText
                : 'Оплатити (Розтермінування)';
        },

        portmonePlaceOrder: function (paymentType) {
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
                    self.redirectToPortmone(orderId, paymentType);
                });

            return false;
        },

        redirectToPortmone: function (orderId, paymentType) {
           // Отримуємо чистий базовий URL контролера
            var baseUrl = urlBuilder.build('portmone/redirect/index', {_secure: true});
            var redirectUrl = baseUrl + '?order_id=' + encodeURIComponent(orderId) + '&type=' + encodeURIComponent(paymentType);

            // Робимо перехід
            window.location.replace(redirectUrl);
        }
    });
});