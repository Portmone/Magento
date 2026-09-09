<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;

class PaymentTypes implements JsonSerializable
{
    private $installment = 'N';

    public function jsonSerialize(): array
    {
        return [
            'installment' => $this->installment,
        ];
    }

    public function setInstallment(string $installment): void
    {
        $this->installment = $installment;
    }

    public function getInstallment(): string
    {
        return $this->installment;
    }
}
