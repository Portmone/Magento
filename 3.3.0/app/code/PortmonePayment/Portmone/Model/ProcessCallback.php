<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use PortmonePayment\Portmone\Model\Enum\PaymentType;
use PortmonePayment\Portmone\Model\Service\ProcessPayment;
use Throwable;

class ProcessCallback
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProcessPayment $processPayment,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CheckoutSession         $checkoutSession
    )
    {
    }

    public function process($shopBillId, $shopOrderNumber)
    {
        $orderId = (int) $this->processPayment->getOrderId($shopOrderNumber);
        if (empty($orderId)) {
            throw new LocalizedException(
                new Phrase('#22P Некоректний ідентифікатор замовлення.')
            );
        }

        $order = $this->orderRepository->get($orderId);

        try {

            if ($this->processPayment->isCanceled($order)) {
                throw new LocalizedException(
                    new Phrase('#23P Статус цього замовлення %1 його неможливо оплатити', [$order->getState()])
                );
            }

            $payeeIdOrder = $order->getData('payee_id');
            $payeeIdConfig = $this->scopeConfig->getValue(
                'payment/portmone/payee_id',
                ScopeInterface::SCOPE_STORE
            ) ?? '';

            $paymentType = PaymentType::FULL->value;
            if ($payeeIdOrder != $payeeIdConfig) {
                $paymentType = PaymentType::INSTALLMENT->value;
            }

            $portmoneOrderData = $this->processPayment->getPortmoneOrderData($paymentType, '', $shopBillId);

            $this->processPayment->checkAmount($order->getBaseGrandTotal(), $portmoneOrderData['billAmount']);
            $this->processPayment->checkPortmoneOrderStatus($portmoneOrderData['status']);

            $order = $this->processPayment->updateOrder($order, $portmoneOrderData['status'], $shopOrderNumber, $shopBillId);
            $this->orderRepository->save($order);

            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        } catch (Throwable $throwable) {
            $order->addCommentToStatusHistory(
                $throwable->getMessage(),
                $order->getStatus(),
                false
            );
            $this->orderRepository->save($order);

            throw new LocalizedException(new Phrase($throwable->getMessage()));
        }
    }
}