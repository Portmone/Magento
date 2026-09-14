<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;
use PortmonePayment\Portmone\Model\Service\ProcessPayment;
use Throwable;

class IframeSuccess
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProcessPayment $processPayment
    )
    {
    }

    public function process($orderId, string $paymentType, string $shopOrderNumber, string $shopBillId): void
    {
        $order = $this->orderRepository->get($orderId);
        try {

            if ($this->processPayment->isCanceled($order)) {
                throw new LocalizedException(
                    new Phrase('#13P Статус цього замовлення %1 його неможливо оплатити', [$order->getState()])
                );
            }

            $portmoneOrderData = $this->processPayment->getPortmoneOrderData($paymentType, '', $shopBillId);

            $this->processPayment->checkAmount($order->getBaseGrandTotal(), $portmoneOrderData['billAmount']);
            $this->processPayment->checkPortmoneOrderStatus($portmoneOrderData['status']);

            $order = $this->processPayment->updateOrder($order, $portmoneOrderData['status'], $shopOrderNumber, $shopBillId);
            $this->orderRepository->save($order);
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