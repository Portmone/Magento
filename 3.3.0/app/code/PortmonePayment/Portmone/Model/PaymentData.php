<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Magento\Sales\Api\OrderRepositoryInterface;
use PortmonePayment\Portmone\Exception\InstallmentMinAmountException;
use PortmonePayment\Portmone\Model\DTO\PaymentData as PaymentDataDTO;
use PortmonePayment\Portmone\Model\DTO\InstallmentPlan;
use PortmonePayment\Portmone\Model\DTO\Payee;
use PortmonePayment\Portmone\Model\DTO\Order as OrderDTO;
use PortmonePayment\Portmone\Model\DTO\Payer;
use PortmonePayment\Portmone\Model\DTO\PaymentTypes;
use PortmonePayment\Portmone\Model\Enum\PaymentType;

class PaymentData
{
    public function __construct(
        private readonly ScopeConfigInterface     $scopeConfig,
        private readonly OrderRepositoryInterface $orderRepository,
        private Payee                             $payee,
        private OrderDTO                          $orderDto,
        private PaymentTypes                      $paymentTypes,
        private Payer                             $payer,
        private InstallmentPlan                   $installmentPlan

    )
    {
    }

    public function getData($orderId, string $paymentType): PaymentDataDTO
    {
        $order = $this->orderRepository->get($orderId);

        $installmentMinAmount = $this->scopeConfig->getValue(
            'payment/portmone/installment_min_amount',
            ScopeInterface::SCOPE_STORE
        );

        if ($paymentType === PaymentType::INSTALLMENT->value && !empty($installmentMinAmount) && $installmentMinAmount > $order->getBaseGrandTotal()) {
            throw new InstallmentMinAmountException(__('Сума замовлення має бути більшою "%1".', $installmentMinAmount));
        }

        $this->orderDto->setShopOrderNumber($orderId);
        $this->orderDto->setBillAmount((float)$order->getBaseGrandTotal());
        $this->orderDto->setDescription();
        if ($paymentType === PaymentType::FULL->value) {
            $this->orderDto->setAttribute5($order);
        }

        if ($paymentType === PaymentType::INSTALLMENT->value) {
            $this->paymentTypes->setInstallment('Y');
            $this->installmentPlan->setBanks();
        }

        $this->payer->setEmailAddress($order->getCustomerEmail() ?? '');

        $this->payee->setPaymentType($paymentType);
        $this->payee->setLogin();
        $this->payee->setPayeeId();
        $this->payee->setSignature($this->orderDto->getShopOrderNumber(), $this->orderDto->getBillAmount());


        $paymentDataDto = new PaymentDataDTO(
            $this->payee,
            $this->orderDto,
            $this->paymentTypes,
            $this->payer,
            $this->installmentPlan
        );

        return $paymentDataDto;
    }
}