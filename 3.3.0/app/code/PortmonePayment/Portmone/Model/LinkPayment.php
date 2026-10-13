<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;
use PortmonePayment\Portmone\Model\Service\HttpClient;
use Throwable;

class LinkPayment
{
    public function __construct(
        private PaymentData $paymentData,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly HttpClient $httpClient
    )
    {
    }

    public function getLinkPayment($orderId, string $paymentType): string
    {
        $order = $this->orderRepository->get($orderId);

        try {
            $paymentData = $this->paymentData->getData($orderId, $paymentType);
            $paymentData->setMethod('createLinkPayment');

            return $this->httpClient->createLinkPayment($paymentData);
        } catch (Throwable $throwable){
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