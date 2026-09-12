<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Controller\Iframe;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Phrase;
use PortmonePayment\Portmone\Model\Enum\PaymentType;
use PortmonePayment\Portmone\Model\IframeSuccess;

use Psr\Log\LoggerInterface;
use Throwable;


class Success implements HttpPostActionInterface
{
    public function __construct(
        private readonly LoggerInterface  $logger,
        private readonly IframeSuccess    $iframeSuccess,
        private readonly RequestInterface $request,
        private readonly JsonFactory      $resultJsonFactory
    )
    {
    }

    public function execute(): Json
    {
        $orderId = $this->request->getParam('orderId');
        $paymentType = $this->request->getParam('paymentType');
        $shopOrderNumber = $this->request->getParam('shopOrderNumber');
        $shopBillId = $this->request->getParam('shopBillId');


        if (empty($orderId) || $orderId <= 0 || empty($shopOrderNumber) || empty($shopBillId)) {
            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => false,
                    'message' => new Phrase('Некоректний ідентифікатор замовлення.') . ' ' . new Phrase('Будь ласка, зв\'яжіться з нами, щоб отримати допомогу.'),
                ]);
        }

        if (empty($paymentType) || !in_array($paymentType, [PaymentType::FULL->value, PaymentType::INSTALLMENT->value], true)) {
            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => false,
                    'message' => new Phrase('Некоректний тип платежів.') . ' ' . new Phrase('Будь ласка, зв\'яжіться з нами, щоб отримати допомогу.'),
                ]);
        }

        try {

            $this->iframeSuccess->process($orderId, $paymentType, $shopOrderNumber, $shopBillId);

            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => true,
                ]);

        } catch (Throwable $t) {

            // Запис у var/log/support.log
            $this->logger->error($t->getMessage());

            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => false,
                    'message' => $t->getMessage(),
                ]);
        }

    }
}
