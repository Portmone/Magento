<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Controller\Iframe;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PortmonePayment\Portmone\Model\Enum\PaymentType;
use PortmonePayment\Portmone\Model\PaymentData;

use Psr\Log\LoggerInterface;
use Throwable;

class GetData implements HttpPostActionInterface
{
    public function __construct(
        private readonly LoggerInterface  $logger,
        private readonly PaymentData      $paymentData,
        private readonly RequestInterface $request,
        private readonly JsonFactory      $resultJsonFactory
    )
    {
    }

    public function execute(): Json
    {
        $orderId = $this->request->getParam('orderId');
        $paymentType = $this->request->getParam('paymentType');


        if (empty($orderId) || $orderId <= 0) {
            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => false,
                    'message' => __('Некорректный идентификатор заказа.'),
                ]);
        }

        if (empty($paymentType) || !in_array($paymentType, [PaymentType::FULL->value, PaymentType::INSTALLMENT->value], true)) {
            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => false,
                    'message' => __('Некорректный тип платежа.'),
                ]);
        }


        try {

            $iframeData = $this->paymentData->getData($orderId, $paymentType);

            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => true,
                    'iframeData' => $iframeData,
                ]);

        } catch (Throwable $t) {

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
