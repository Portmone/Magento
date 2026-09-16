<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;

class PaymentTypes implements JsonSerializable
{
    public function __construct(
        readonly private string $installment
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'installment' => $this->installment,
        ];
    }

    public function getInstallment(): string
    {
        return $this->installment;
    }
}
