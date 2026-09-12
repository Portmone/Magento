<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use PortmonePayment\Portmone\Model\DTO\HttpBody;
use PortmonePayment\Portmone\Model\DTO\ResultData;
use stdClass;

class ProcessPayment
{
    const ORDER_PAYED       = 'PAYED';
    const ORDER_CREATED     = 'CREATED';
    const ORDER_REJECTED    = 'REJECTED';
    const ORDER_PREAUTH     = 'PREAUTH';
    const ORDER_RETURN      = 'RETURN';

    public function __construct(
        private readonly ResultData $resultData,
        private readonly HttpClient $httpClient,
        private HttpBody $httpBody
    )
    {
    }

    public function updateOrder(OrderInterface $order, string $status, string $shopOrderNumber): OrderInterface
    {

        if ($status === self::ORDER_PAYED) {
            $newStatus = Order::STATE_COMPLETE;
            $order->setStatus($newStatus);
            $order->addCommentToStatusHistory(new Phrase('Дякуємо за покупку'), $newStatus, true);
        }

        if ($status === self::ORDER_PREAUTH) {
            $newStatus = Order::STATE_HOLDED;
            $order->setData('shop_order_number', $shopOrderNumber);
            $order->setStatus($newStatus);
            $order->addCommentToStatusHistory(new Phrase('Дякуємо за покупку'), $newStatus, true);
        }

        return $order;
    }
    public function getPortmoneOrderData(string $paymentType, string $shopOrderNumber, string $shopBillId): array
    {
        $this->resultData->setPaymentType($paymentType);
        $this->resultData->setLogin();
        $this->resultData->setPassword();
        $this->resultData->setPayeeId();
        $this->resultData->setShopOrderNumber($shopOrderNumber);
        $this->resultData->setShopBillId($shopBillId);

        $params = new stdClass();
        $params->data = $this->resultData;

        $this->httpBody->setMethod('result');
        $this->httpBody->setParams($params);

        return $this->httpClient->getPortmoneOrderData($this->httpBody);
    }

    public function checkPortmoneOrderStatus(string $portmoneOrderStatus): void
    {
        if (in_array($portmoneOrderStatus, [self::ORDER_CREATED, self::ORDER_REJECTED, self::ORDER_RETURN], true)) {
            throw new LocalizedException(
                new Phrase('#18P Під час здійснення оплати виникла помилка. portmone Order Status: %1', [$portmoneOrderStatus])
            );
        }
    }

    public function checkAmount($baseGrandTotal, $billAmount): void
    {
        if (round((float) $baseGrandTotal, 2)  !== round((float) $billAmount, 2)) {
            throw new LocalizedException(
                new Phrase('#19P Під час здійснення оплати виникла помилка. base Grand Total: %1  bill Amount: %2', [$baseGrandTotal, $billAmount])
            );
        }
    }

    public function isCanceled(OrderInterface $order): bool
    {
        if ($order->isCanceled() || $order->getState() === Order::STATE_CLOSED) {
            return true;
        }

        return false;
    }

    public function getOrderId(string $shop_number): string
    {
        $shop_number_count = strpos($shop_number, "_");
        if ( $shop_number_count === false ) {
            return $shop_number;
        }
        return substr( $shop_number, 0, $shop_number_count );
    }
}