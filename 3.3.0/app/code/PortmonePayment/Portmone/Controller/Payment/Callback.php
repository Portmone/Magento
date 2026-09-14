<?php

namespace PortmonePayment\Portmone\Controller\Payment;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use PortmonePayment\Portmone\Model\ProcessCallback;
use Psr\Log\LoggerInterface;
use Throwable;

class Callback implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(

        private readonly RequestInterface        $request,
        private readonly LoggerInterface         $logger,
        private readonly RedirectFactory         $redirectFactory,
        private readonly MessageManagerInterface $messageManager,
        private readonly ProcessCallback         $processCallback

    )
    {
    }

    public function execute()
    {
        $resultRedirect = $this->redirectFactory->create();

        $shopBillId = $this->request->getParam('SHOPBILLID');
        $shopOrderNumber = $this->request->getParam('SHOPORDERNUMBER');

        if (empty($shopOrderNumber) || empty($shopBillId)) {
            $this->messageManager->addErrorMessage(new Phrase('Не вдалося виконати оплату через платіжну систему Portmone.'));
            return $resultRedirect->setPath('checkout/cart');
        }

        try {
            $this->processCallback->process($shopBillId, $shopOrderNumber);
            return $resultRedirect->setPath('checkout/onepage/success');
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());
            $this->messageManager->addErrorMessage($t->getMessage());
            return $resultRedirect->setPath('checkout/cart');
        }

    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
