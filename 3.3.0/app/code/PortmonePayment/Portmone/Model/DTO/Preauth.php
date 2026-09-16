<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;

class Preauth implements JsonSerializable
{
    public function __construct(
        private readonly string $login,
        private readonly string $password,
        private readonly string $payeeId,
        private readonly string $shopBillId,
        private readonly float $postauthAmount = 0.00,
    )
    {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'login' => $this->login,
            'password' => $this->password,
            'payeeId' => $this->payeeId,
            'shopbillId' => $this->shopBillId,
        ];

        if ($this->postauthAmount > 0.00) {
            $data['postauthAmount'] = $this->postauthAmount;
        }

        return $data;
    }
}