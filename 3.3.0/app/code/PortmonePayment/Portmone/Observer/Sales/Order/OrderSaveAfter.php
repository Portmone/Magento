<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use PortmonePayment\Portmone\Model\Preauth\OrderStatusProcessor;
use Throwable;


class OrderSaveAfter implements ObserverInterface
{
    public function __construct(
        private readonly OrderStatusProcessor $orderStatusProcessor,
        private readonly ManagerInterface $messageManager
    ) {
    }

    public function execute(Observer $observer): void
    {

        try {
            $order = $observer->getEvent()->getOrder();
            $result =  $this->orderStatusProcessor->process($order);

            if ($result) {
                $this->messageManager->addSuccessMessage(
                    new Phrase('ВЗамовлення №%1 успішно оновлено.', [$order->getIncrementId()])
                );
            }
        } catch (Throwable $t) {
            $this->messageManager->addErrorMessage(
                new Phrase('Виникла помилка при обробці замовлення: %1', [$t->getMessage()])
            );
        }
    }
}