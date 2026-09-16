<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use PortmonePayment\Portmone\Model\Enum\PaymentMode;

class Payee implements JsonSerializable
{
    public function __construct(
        readonly private string $paymentMode,
        readonly private string $payeeId,
        readonly private string $login,
        readonly private string $dt,
        readonly private string $appleMerchantName = '',
        readonly private string $appleMerchantLabel = '',
        readonly private string $signature = '',
    )
    {
    }

    public function jsonSerialize(): array
    {
        $result = [
            'payeeId' => $this->payeeId,
            'login' => $this->login,
            'dt' => $this->dt,
            'signature' => $this->signature,
        ];

        if ($this->paymentMode === PaymentMode::IFRAME->value) {
            $result['appleMerchantLabel'] = $this->appleMerchantLabel;
            $result['appleMerchantName'] = $this->appleMerchantName;
        }

        return $result;
    }

}
