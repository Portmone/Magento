<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use PortmonePayment\Portmone\Model\Enum\PaymentType;

class ResultData implements JsonSerializable
{
    public function __construct(
        readonly private string $paymentType,
        readonly private string $login,
        readonly private string $password,
        readonly private string $payeeId,
        readonly private string $shopbillId,
        readonly private string $shopOrderNumber,
        readonly private string $status
    )
    {
    }

    public function jsonSerialize(): array
    {
        $result = [
            'login' => $this->login,
            'password' => $this->password,
        ];

        if ($this->paymentType === PaymentType::FULL->value) {
            $result['payeeId'] = $this->payeeId;
        }

        if (!empty($this->shopbillId)) {
            $result['shopbillId'] = $this->shopbillId;
            return $result;
        }

        if (!empty($this->shopOrderNumber)) {
            $result['shopOrderNumber'] = $this->shopOrderNumber;
            $result['status'] = $this->status;
        }

        return $result;
    }
}
