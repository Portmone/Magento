define([
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'portmonePg'
], function (
    ko,
    $,
    Component,
    additionalValidators,
    placeOrderAction,
    fullScreenLoader,
    quote,
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

            if (!config || config.installmentFlag !== '1' || config.paymentMode !== 'redirect') {
                return false;
            }

            return this.checkInstallmentMinAmount();
        },


        // Перевіряє, чи увімкнено Розтермінування Iframe
        isInstallmentIframe: function () {
            var config = window.checkoutConfig.payment.portmone;

            if (!config || config.installmentFlag !== '1' || config.paymentMode !== 'iframe') {
                return false;
            }

            return this.checkInstallmentMinAmount();
        },

        checkInstallmentMinAmount: function () {
            var config = window.checkoutConfig.payment.portmone;
            var installmentMinAmount = parseFloat(config.installmentMinAmount);

            if (installmentMinAmount === 0) {
                return true;
            }

            var totals = quote.totals();
            if (!totals) {
                return false;
            }

            return parseFloat(totals.grand_total) >= installmentMinAmount;
        },

        // Повертає текст кнопки Оплатити через Portmone (Розтермінування)
        getInstallmentButtonText: function () {
            return window.checkoutConfig.payment.portmone.buttonInstallmentText;
        },

        getButtonStyles: function () {
            var config = window.checkoutConfig.payment.portmone;

            if (config.buttonStyleFlag == 1) {
                return config.buttonStyle;
            }

            return {};
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
            var redirectUrl = baseUrl + '?orderId=' + encodeURIComponent(orderId) + '&paymentType=' + encodeURIComponent(paymentType);

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

                        PG.onClose(function () {
                            fullScreenLoader.stopLoader();
                            document.location.reload();
                        });

                        PG.success(function (data) {
                            self.successPortmone(orderId, paymentType, data);
                        });

                        PG.create();

                        window.setTimeout( function () {
                            $hiddenBtn.trigger('click');
                        }, 300);
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
        },

        successPortmone: function (orderId, paymentType, data) {
            var self = this;

            $.ajax({
                url: urlBuilder.build('portmone/iframe/success', {_secure: true}),
                type: 'POST',
                dataType: 'json',
                data: {
                    orderId: orderId,
                    paymentType: paymentType,
                    shopOrderNumber: data.billNumber,
                    shopBillId: data.shopBillId
                }
            }).done(function (response) {


                if (response.success) {
                    fullScreenLoader.stopLoader();

                    var successUrl = urlBuilder.build('checkout/onepage/success');
                    window.location.replace(successUrl);

                    return;
                }

                fullScreenLoader.stopLoader();

                if (!response.success && response.message) {

                    self.messageContainer.addErrorMessage({
                        message: response.message
                    });
                    document.location.reload();
                }

            }).fail(function (jqXHR) {
                fullScreenLoader.stopLoader();
                self.messageContainer.addErrorMessage({
                    message: window.checkoutConfig.payment.portmone.errorMessage.iframeSuccessFail
                });
                console.error('Portmone: Unable to change order status', jqXHR);
                document.location.reload();
            });
        }
    });
});