<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Enum;

enum PaymentMode: string
{
    case REDIRECT = 'redirect';
    case IFRAME = 'iframe';
}