define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var $field = $('#payment_us_portmone_payment_mode');

        if (!$field.length) {
            return;
        }

        var $comment = $field.closest('td.value').find('.note span');

        if (!$comment.length) {
            return;
        }

        var $description = $('<div>', {
            'class': 'portmone-payment-mode-description'
        });

        $comment.closest('.note').after($description);

        function updateComment() {
            var mode = $field.val();

            if (config.comments && config.comments[mode]) {
                $description.html(config.comments[mode]);
            } else {
                $description.empty();
            }
        }

        $field.on('change', updateComment);

        updateComment();
    };
});