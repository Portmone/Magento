<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentMode as PaymentModeEnum;

class PaymentMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => PaymentModeEnum::REDIRECT->value,
                'label' => __('Перенаправлення на сторінку Portmone'),
            ],
            [
                'value' => PaymentModeEnum::IFRAME->value,
                'label' => __('Платіжна форма на сайті (фрейм)'),
            ],
        ];
    }
}