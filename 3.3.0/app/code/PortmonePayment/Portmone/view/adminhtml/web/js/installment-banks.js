define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        var $container = $('.portmone-installment-banks');

        if (!$container.length) {
            return;
        }

        function updateBankState($bank) {
            var $checkbox = $bank.find('.portmone-installment-bank__checkbox input[type="checkbox"]');
            var $term = $bank.find('.portmone-installment-bank__term input[type="number"]');

            $term.prop('disabled', !$checkbox.prop('checked'));
        }

        $container.find('.portmone-installment-bank').each(function () {
            updateBankState($(this));
        });

        $container.on(
            'change',
            '.portmone-installment-bank__checkbox input[type="checkbox"]',
            function () {
                updateBankState($(this).closest('.portmone-installment-bank'));
            }
        );
    };
});