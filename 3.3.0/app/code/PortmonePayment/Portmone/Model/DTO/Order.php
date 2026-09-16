<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentMode;

class Order implements JsonSerializable
{
    private const string ENCODING = 'UTF-8';

    public function __construct(
        readonly string         $paymentMode,
        readonly private string $description,
        readonly private string $shopOrderNumber,
        readonly private float  $billAmount,
        readonly private string $callbackUrl,
        readonly private string $preauthFlag,
        readonly private string $expTime,
        readonly private string $attribute5 = ''
    )
    {
    }

    public function jsonSerialize(): array
    {
        $result = [
            'description' => $this->description,
            'shopOrderNumber' => $this->shopOrderNumber,
            'billAmount' => $this->billAmount,
            'preauthFlag' => $this->preauthFlag,
            'expTime' => $this->expTime,
            'encoding' => self::ENCODING,
            'attribute5' => $this->attribute5,
        ];

        if ($this->paymentMode === PaymentMode::REDIRECT->value) {
            $result['successUrl'] = $this->callbackUrl;
            $result['failureUrl'] = $this->callbackUrl;
        }


        return $result;
    }
}
