<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use Magento\Store\Model\ScopeInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentMode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentType;

class Payee implements JsonSerializable
{
    private string $paymentMode;
    private string $paymentType;
    private string $payeeId;
    private string $login;
    private string $dt;
    private string $appleMerchantName;
    private string $appleMerchantLabel;
    private string $signature = '';
    private string $key;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    )
    {
        $this->setPaymentMode();
        $this->setDt();
        $this->setAppleMerchantName();
        $this->setAppleMerchantLabel();
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

    public function setSignature(string $shopOrderNumber, float $billAmount): void
    {
        $signature = $this->payeeId . $this->dt . bin2hex($shopOrderNumber) . $billAmount;
        $signature = strtoupper($signature) . strtoupper(bin2hex($this->login));

        $this->setKey();

        $this->signature = strtoupper(hash_hmac('sha256', $signature, $this->key));
    }

    public function setPaymentType(string $paymentType): void
    {
        $this->paymentType = $paymentType;
    }

    public function setPayeeId(): void
    {
        if ($this->paymentType === PaymentType::FULL->value) {
            $this->payeeId = $this->scopeConfig->getValue(
                'payment/portmone/payee_id',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }

        if ($this->paymentType === PaymentType::INSTALLMENT->value) {
            $this->payeeId = $this->scopeConfig->getValue(
                'payment/portmone/installment_payee_id',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }
    }

    public function setLogin(): void
    {

        if ($this->paymentType === PaymentType::FULL->value) {
            $this->login = $this->scopeConfig->getValue(
                'payment/portmone/login',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }

        if ($this->paymentType === PaymentType::INSTALLMENT->value) {
            $this->login = $this->scopeConfig->getValue(
                'payment/portmone/installment_login',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }
    }

    private function setKey(): void
    {

        if ($this->paymentType === PaymentType::FULL->value) {
            $this->key = $this->scopeConfig->getValue(
                'payment/portmone/key',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }

        if ($this->paymentType === PaymentType::INSTALLMENT->value) {
            $this->key = $this->scopeConfig->getValue(
                'payment/portmone/installment_key',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }
    }

    private function setPaymentMode(): void
    {
        $this->paymentMode = $this->scopeConfig->getValue(
            'payment/portmone/payment_mode',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    private function setAppleMerchantLabel(): void
    {
        $this->appleMerchantLabel = $this->scopeConfig->getValue(
            'payment/portmone/apple_merchant_label',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    private function setAppleMerchantName(): void
    {
        $this->appleMerchantName = $this->scopeConfig->getValue(
            'payment/portmone/apple_merchant_name',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    private function setDt(): void
    {
        $this->dt = date('Ymdhis');
    }
}
