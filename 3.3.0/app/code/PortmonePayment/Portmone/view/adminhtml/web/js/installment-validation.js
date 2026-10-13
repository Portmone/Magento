define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    return function (config) {
        var $installment = $('#payment_us_portmone_installment');

        if (!$installment.length) {
            return;
        }

        var requiredFields = [
            '#payment_us_portmone_installment_payee_id',
            '#payment_us_portmone_installment_login',
            '#payment_us_portmone_installment_password',
            '#payment_us_portmone_installment_key',
            '#payment_us_portmone_installment_min_amount'
        ];

        var $expTime = $('#payment_us_portmone_exp_time');

        $.validator.addMethod(
            'empty-when-installment',
            function (value) {
                if (String($installment.val()) !== '1') {
                    return true;
                }

                return $.trim(value) === '';
            },
            config.emptyMessage
        );

        function updateValidation() {
            var enabled = String($installment.val()) === '1';

            requiredFields.forEach(function (selector) {
                var $field = $(selector);

                if (!$field.length) {
                    return;
                }

                $field.rules('remove', 'required');

                if (enabled) {
                    $field.rules('add', {
                        required: true,
                        messages: {
                            required: config.requiredMessage
                        }
                    });
                }
            });

            if ($expTime.length) {
                $expTime.rules('remove', 'empty-when-installment');

                if (enabled) {
                    $expTime.rules('add', {
                        'empty-when-installment': true,
                        messages: {
                            'empty-when-installment': config.emptyMessage
                        }
                    });
                }
            }
        }

        setTimeout(function () {
            updateValidation();
        }, 0);

        $installment.on('change', function () {
            updateValidation();
        });
    };
});