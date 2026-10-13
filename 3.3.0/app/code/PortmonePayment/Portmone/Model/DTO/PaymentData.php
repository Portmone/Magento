<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use PortmonePayment\Portmone\Model\DTO\Order as OrderDTO;

class PaymentData implements JsonSerializable
{
    private string $method = '';

    public function __construct(
        private readonly Payee           $payee,
        private readonly OrderDTO        $orderDto,
        private readonly PaymentTypes    $paymentTypes,
        private readonly Payer           $payer,
        private readonly InstallmentPlan $installmentPlan
    )
    {
    }

    public function jsonSerialize(): array
    {
        $result = [
            'payee' => $this->payee,
            'order' => $this->orderDto,
            'paymentTypes' => $this->paymentTypes,
            'payer' => $this->payer,
        ];

        if ($this->paymentTypes->getInstallment() === 'Y' && count($this->installmentPlan->getBanks()) > 0) {
            $result['installmentPlan'] = $this->installmentPlan;
        }

        if ($this->method !== '') {
            $result['method'] = $this->method;
        }

        return $result;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}

