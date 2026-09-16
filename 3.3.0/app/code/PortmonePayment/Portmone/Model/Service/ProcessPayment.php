<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use PortmonePayment\Portmone\Model\DTO\HttpBody;
use PortmonePayment\Portmone\Model\DTO\ResultData;
use PortmonePayment\Portmone\Model\Payee as PayeeModel;
use stdClass;

class ProcessPayment
{
    const ORDER_PAYED = 'PAYED';
    const ORDER_CREATED = 'CREATED';
    const ORDER_REJECTED = 'REJECTED';
    const ORDER_PREAUTH = 'PREAUTH';
    const ORDER_RETURN = 'RETURN';

    public function __construct(
        private readonly ResolverInterface $localeResolver,
        private readonly HttpClient        $httpClient,
        private PayeeModel                 $payeeModel
    )
    {
    }

    public function updateOrder(OrderInterface $order, string $status, string $shopOrderNumber, string $shopBillId): OrderInterface
    {

        if ($status === self::ORDER_PAYED) {
            $newStatus = Order::STATE_COMPLETE;
            $order->setStatus($newStatus);
            $order->addCommentToStatusHistory(new Phrase('Дякуємо за покупку'), $newStatus, true);
        }

        if ($status === self::ORDER_PREAUTH) {
            $newStatus = Order::STATE_HOLDED;
            $order->setStatus($newStatus);
            $order->addCommentToStatusHistory(new Phrase('Дякуємо за покупку'), $newStatus, true);
        }

        $order->setData('shop_order_number', $shopOrderNumber);
        $order->setData('shop_bill_id', $shopBillId);

        return $order;
    }

    public function getPortmoneOrderData(string $paymentType, string $shopOrderNumber, string $shopBillId, string $status): array
    {
        $this->payeeModel->setPaymentMode();
        $this->payeeModel->setPaymentType($paymentType);
        $this->payeeModel->setLogin();
        $this->payeeModel->setPassword();
        $this->payeeModel->setPayeeId();

        $resultData = new ResultData(
            $this->payeeModel->getPaymentType(),
            $this->payeeModel->getLogin(),
            $this->payeeModel->getPassword(),
            $this->payeeModel->getPayeeId(),
            $shopBillId,
            $shopOrderNumber,
            $status
        );


        $params = new stdClass();
        $params->data = $resultData;

        $httpBody  = new HttpBody(
            'result',
            $params
        );

        return $this->httpClient->getPortmoneOrderData($httpBody);
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
        if (round((float)$baseGrandTotal, 2) !== round((float)$billAmount, 2)) {
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
        if ($shop_number_count === false) {
            return $shop_number;
        }
        return substr($shop_number, 0, $shop_number_count);
    }

    public function getLang(): string
    {
        $fullLocale = $this->localeResolver->getLocale(); // 'uk_UA'
        $languageCode = strstr($fullLocale, '_', true);   // 'uk'

        $lang = 'uk';
        if (in_array($languageCode, ['ru', 'en', 'uk'])) {
            $lang = $languageCode;
        }

        return $lang;
    }
}