<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Controller\Redirect;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use PortmonePayment\Portmone\Model\Enum\PaymentType;
use PortmonePayment\Portmone\Model\LinkPayment;
use Psr\Log\LoggerInterface;
use Throwable;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface        $request,
        private readonly LoggerInterface         $logger,
        private readonly RedirectFactory         $redirectFactory,
        private readonly MessageManagerInterface $messageManager,
        private readonly LinkPayment             $linkPayment,
    )
    {
    }

    public function execute()
    {
        $resultRedirect = $this->redirectFactory->create();

        $orderId = $this->request->getParam('orderId');
        $paymentType = $this->request->getParam('paymentType');

        if (empty($orderId) || $orderId <= 0) {
            $this->messageManager->addErrorMessage(new Phrase('Некоректний ідентифікатор замовлення.'));
            return $resultRedirect->setPath('checkout/cart');

        }

        if (empty($paymentType) || !in_array($paymentType, [PaymentType::FULL->value, PaymentType::INSTALLMENT->value], true)) {
            $this->messageManager->addErrorMessage(new Phrase('Некоректний тип платежів.'));
            return $resultRedirect->setPath('checkout/cart');
        }

        try {
            $linkPayment = $this->linkPayment->getLinkPayment($orderId, $paymentType);
            return $resultRedirect->setUrl($linkPayment);

        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());
            $this->messageManager->addErrorMessage($t->getMessage());
            return $resultRedirect->setPath('checkout/cart');
        }
    }
}
