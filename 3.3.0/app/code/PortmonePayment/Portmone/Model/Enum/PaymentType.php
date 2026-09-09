<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Enum;

enum PaymentType: string
{
    case FULL = 'full';
    case INSTALLMENT = 'installment';
}