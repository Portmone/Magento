<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use InvalidArgumentException;
use JsonSerializable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\UrlInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentMode;

class Order implements JsonSerializable
{
    private string $paymentMode;
    private string $description;
    private string $shopOrderNumber;
    private float $billAmount;
    private string $callbackUrl;
    private string $preauthFlag;
    private string $expTime;
    private $encoding = 'UTF-8';
    private string $attribute5 = '';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly UrlInterface         $urlBuilder
    )
    {
        $this->setPaymentMode();
        $this->setCallbackUrl();
        $this->setPreauthFlag();
        $this->setExpTime();
    }

    public function jsonSerialize(): array
    {
        $result = [
            'description' => $this->description,
            'shopOrderNumber' => $this->shopOrderNumber,
            'billAmount' => $this->billAmount,
            'preauthFlag' => $this->preauthFlag,
            'expTime' => $this->expTime,
            'encoding' => $this->encoding,
            'attribute5' => $this->attribute5,
        ];

        if ($this->paymentMode === PaymentMode::REDIRECT->value) {
            $result['successUrl'] = $this->callbackUrl;
            $result['failureUrl'] = $this->callbackUrl;
        }


        return $result;
    }

    public function setShopOrderNumber($orderId): void
    {
        $testModeFlag = $this->scopeConfig->getValue(
            'payment/portmone/test_mode_flag',
            ScopeInterface::SCOPE_STORE
        ) ?? 0;

        $this->shopOrderNumber = (string)$orderId;
        if ($testModeFlag == 1) {
            $this->shopOrderNumber = $orderId . '_' . time();
        }
    }

    public function getShopOrderNumber(): string
    {
        return $this->shopOrderNumber;
    }

    public function setBillAmount(float $billAmount): void
    {
        $this->billAmount = $billAmount;
    }

    public function getBillAmount(): float
    {
        return $this->billAmount;
    }

    public function setAttribute5(OrderInterface $order): void
    {
        $splitPaymentFlag = $this->scopeConfig->getValue(
            'payment/portmone/split_payment_flag',
            ScopeInterface::SCOPE_STORE
        ) ?? 0;

        if ($splitPaymentFlag != 1) {
            return;
        }

        $mainPayeeId = $this->scopeConfig->getValue(
            'payment/portmone/payee_id',
            ScopeInterface::SCOPE_STORE
        ) ?? '';

        $splitPayments = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $payeeId = $item->getProduct()->getData('portmone_payee_id');

            if (empty($payeeId)) {
                $payeeId = $mainPayeeId;
            }

            $amount = (int)round((float)$item->getBaseRowTotal() * 100);

            if (!isset($splitPayments[$payeeId])) {
                $splitPayments[$payeeId] = 0;
            }

            $splitPayments[$payeeId] += $amount;
        }

        $attribute5 = '';

        foreach ($splitPayments as $payeeId => $amount) {
            $attribute5 .= $payeeId . ';' . ($amount / 100) . ';';
        }

        if (strlen($attribute5) > 500) {
            throw new InvalidArgumentException((string)__('Параметр attribute5 перевищує допустимі 500 символів.'));
        }

        $this->attribute5 = $attribute5;
    }

    public function setDescription(): void
    {
        $description = $this->scopeConfig->getValue(
            'payment/portmone/order_description',
            ScopeInterface::SCOPE_STORE
        ) ?? '';


        $this->description = str_replace('%order_number', $this->shopOrderNumber, $description);;
    }

    private function setCallbackUrl(): void
    {
        $this->callbackUrl = $this->urlBuilder->getUrl('portmone/payment/callback');
    }

    private function setPaymentMode(): void
    {
        $this->paymentMode = $this->scopeConfig->getValue(
            'payment/portmone/payment_mode',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    private function setPreauthFlag(): void
    {
        $preauthFlag = $this->scopeConfig->getValue(
            'payment/portmone/preauth_flag',
            ScopeInterface::SCOPE_STORE
        ) ?? 0;

        $this->preauthFlag = 'N';
        if ($preauthFlag == 1) {
            $this->preauthFlag = 'Y';
        }
    }

    private function setExpTime(): void
    {
        $this->expTime = $this->scopeConfig->getValue(
            'payment/portmone/exp_time',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }
}
