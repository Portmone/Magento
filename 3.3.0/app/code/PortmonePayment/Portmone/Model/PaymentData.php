<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use PortmonePayment\Portmone\Model\DTO\PaymentData as PaymentDataDTO;
use PortmonePayment\Portmone\Model\DTO\InstallmentPlan as InstallmentPlanDTO;
use PortmonePayment\Portmone\Model\DTO\Payee as PayeeeDTO;
use PortmonePayment\Portmone\Model\DTO\Order as OrderDTO;
use PortmonePayment\Portmone\Model\DTO\Payer as PayerDTO;
use PortmonePayment\Portmone\Model\DTO\PaymentTypes as PaymentTypesDTO;
use PortmonePayment\Portmone\Model\Order as OrderModel;
use PortmonePayment\Portmone\Model\InstallmentPlan as InstallmentPlanModel;
use PortmonePayment\Portmone\Model\Payee as PayeeModel;
use PortmonePayment\Portmone\Model\Enum\PaymentType;
use PortmonePayment\Portmone\Model\Service\ProcessPayment;

class PaymentData
{
    public function __construct(
        private readonly ScopeConfigInterface     $scopeConfig,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProcessPayment           $processPayment,
        private PayeeModel                        $payeeModel,
        private OrderModel                        $orderModel,
        private InstallmentPlanModel              $installmentPlanModel

    )
    {
    }

    public function getData($orderId, string $paymentType)
    {
        $order = $this->orderRepository->get($orderId);

        $installmentMinAmount = $this->scopeConfig->getValue(
            'payment/portmone/installment_min_amount',
            ScopeInterface::SCOPE_STORE
        );

        if ($paymentType === PaymentType::INSTALLMENT->value && !empty($installmentMinAmount) && $installmentMinAmount > $order->getBaseGrandTotal()) {
            throw new LocalizedException(
                new Phrase('Сума замовлення має бути більшою "%1".', $installmentMinAmount)
            );
        }

        $this->orderModel->setPaymentMode();
        $this->orderModel->setShopOrderNumber($orderId);;
        $this->orderModel->setDescription();
        $this->orderModel->setCallbackUrl();
        $this->orderModel->setPreauthFlag();
        $this->orderModel->setExpTime();

        $this->orderModel->setBillAmount((float)$order->getBaseGrandTotal());
        $this->orderModel->setDescription();
        if ($paymentType === PaymentType::FULL->value) {
            $this->orderModel->setAttribute5($order);
        }

        if ($paymentType === PaymentType::INSTALLMENT->value) {
            $paymentTypesDTO = new PaymentTypesDTO('Y');
            $this->installmentPlanModel->setBanks();
        } else {
            $paymentTypesDTO = new PaymentTypesDTO('N');
        }

        $this->payeeModel->setPaymentMode();
        $this->payeeModel->setPaymentType($paymentType);

        $this->payeeModel->setLogin();
        $this->payeeModel->setPayeeId();
        $this->payeeModel->setDt();
        $this->payeeModel->setKey();
        $this->payeeModel->setSignature($this->orderModel->getShopOrderNumber(), $this->orderModel->getBillAmount());
        $this->payeeModel->setAppleMerchantLabel();
        $this->payeeModel->setAppleMerchantName();

        $order->setData('payee_id', $this->payeeModel->getPayeeId());
        $this->orderRepository->save($order);

        $orderDTO = new OrderDTO(
            $this->orderModel->getPaymentMode(),
            $this->orderModel->getDescription(),
            $this->orderModel->getShopOrderNumber(),
            $this->orderModel->getBillAmount(),
            $this->orderModel->getCallbackUrl(),
            $this->orderModel->getPreauthFlag(),
            $this->orderModel->getExpTime(),
            $this->orderModel->getAttribute5(),
        );



        $payerDTO = new PayerDTO(
            $this->processPayment->getLang(),
            $order->getCustomerEmail() ?? ''
        );

        $installmentPlanDTO = new InstallmentPlanDTO($this->installmentPlanModel->getBanks());

        $payeeDTO = new PayeeeDTO(
            $this->payeeModel->getPaymentMode(),
            $this->payeeModel->getPayeeId(),
            $this->payeeModel->getLogin(),
            $this->payeeModel->getDt(),
            $this->payeeModel->getAppleMerchantName(),
            $this->payeeModel->getAppleMerchantLabel(),
            $this->payeeModel->getSignature()
        );

        $paymentDataDto = new PaymentDataDTO(
            $payeeDTO,
            $orderDTO,
            $paymentTypesDTO,
            $payerDTO,
            $installmentPlanDTO
        );

        return $paymentDataDto;
    }
}