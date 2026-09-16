<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentType;

class Payee
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

    private string $password;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    )
    {
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

    public function getPayeeId(): string
    {
        return $this->payeeId;
    }

    public function setKey(): void
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

    public function setPaymentMode(): void
    {
        $this->paymentMode = $this->scopeConfig->getValue(
            'payment/portmone/payment_mode',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function setAppleMerchantLabel(): void
    {
        $this->appleMerchantLabel = $this->scopeConfig->getValue(
            'payment/portmone/apple_merchant_label',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function setAppleMerchantName(): void
    {
        $this->appleMerchantName = $this->scopeConfig->getValue(
            'payment/portmone/apple_merchant_name',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function setDt(): void
    {
        $this->dt = date('Ymdhis');
    }

    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    public function getAppleMerchantName(): string
    {
        return $this->appleMerchantName;
    }

    public function getAppleMerchantLabel(): string
    {
        return $this->appleMerchantLabel;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getDt(): string
    {
        return $this->dt;
    }

    public function setPassword(): void
    {

        if ($this->paymentType === PaymentType::FULL->value) {
            $this->password = $this->scopeConfig->getValue(
                'payment/portmone/password',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }

        if ($this->paymentType === PaymentType::INSTALLMENT->value) {
            $this->password = $this->scopeConfig->getValue(
                'payment/portmone/installment_password',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
        }
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }
}