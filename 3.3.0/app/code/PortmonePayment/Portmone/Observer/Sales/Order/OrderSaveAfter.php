<?php

namespace PortmonePayment\Portmone\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;
use PortmonePayment\Portmone\Model\Portmone;
use Psr\Log\LoggerInterface;
use Magento\Framework\Message\ManagerInterface;
class OrderSaveAfter  implements ObserverInterface
{

    protected $logger;
    protected $portmone;
    protected $messageManager;

    public function __construct(LoggerInterface$logger, ManagerInterface $messageManager, Portmone $portmone)
    {
        $this->messageManager = $messageManager;
        $this->portmone = $portmone;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!in_array($order->getStatus(), ['complete', 'return', 'canceled'])) {
            return;
        }

        $shopOrderNumber = $order->getShopOrderNumber();
        if (empty($shopOrderNumber)) {
            return;
        }

        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        if ($methodTitle != 'Portmone') {
            return;
        }

        $isHolded = false;
        foreach ($order->getStatusHistoryCollection() as $status) {
            if ($status->getStatus() == 'holded') {
                $isHolded = true;
                break;
            }
        }

        if (!$isHolded) {
            return;
        }

        $preauthResult = $this->portmone->preauthResult($shopOrderNumber);
        if ($preauthResult['status'] == 'PORTMONE_ERROR') {
            throw new \RuntimeException(__($preauthResult['message']));
        }

        if ($order->getStatus() == 'complete') {
            $orderAmount = $order->getGrandTotal();
            if ($orderAmount > $preauthResult['orderData']['bill_amount']) {
                throw new \RuntimeException(__('Сума оплати не може бути більше за суму, на яку проводилась преавторизація'));
            }

            $preauthSetPaid = $this->portmone->preauthSetPaid($preauthResult['orderData']['shop_bill_id'], $orderAmount);
            if ($preauthSetPaid['status'] == 'PORTMONE_ERROR') {
                throw new \RuntimeException(__($preauthSetPaid['message']));
            }

            if ($preauthSetPaid['status'] == 'PAYED') {
                $this->messageManager->addSuccess(__($preauthSetPaid['message']));
            } else {
                throw new \RuntimeException(__('Невідома помилка'));
            }
        }

        if (in_array($order->getStatus(), ['return', 'canceled'])) {
            $preauthReject = $this->portmone->preauthReject($preauthResult['orderData']['shop_bill_id']);
            if ($preauthReject['status'] == 'PORTMONE_ERROR') {
                throw new \RuntimeException(__($preauthReject['message']));
            }

            if ($preauthReject['status'] == 'REJECTED') {
                $this->messageManager->addSuccess(__($preauthReject['message']));
            } else {
                throw new \RuntimeException(__('Невідома помилка'));
            }
        }
    }
}
