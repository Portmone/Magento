<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PortmonePayment\Portmone\Model\Enum\PaymentType;

class ResultData implements JsonSerializable
{
    private string $paymentType;
    private string $login;
    private string $password;
    private string $payeeId;
    private string $shopbillId = '';
    private string $shopOrderNumber = '';
    private string $status = 'PAYED';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
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
            // $result['status'] = $this->status;
        }

        return $result;
    }

    public function setPaymentType(string $paymentType): void
    {
        $this->paymentType = $paymentType;
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

    public function setShopbillId(string $shopbillId): void
    {
        $this->shopbillId = $shopbillId;
    }

    public function setShopOrderNumber(string $shopOrderNumber): void
    {
        $this->shopOrderNumber = $shopOrderNumber;
    }


}