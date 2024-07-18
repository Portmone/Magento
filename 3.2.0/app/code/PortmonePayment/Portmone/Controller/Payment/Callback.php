<?php

namespace PortmonePayment\Portmone\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http;

class Callback extends Action implements CsrfAwareActionInterface
{
    protected $resultRedirect;
    protected $request;

    public function __construct(Context $context, Http $request)
    {
        parent::__construct($context);
        $this->resultRedirect = $context->getResultFactory();
        $this->request = $request;

    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');
        $post = $this->request->getPost();

        if (empty($post)) {
            $this->messageManager->addErrorMessage(__('Немає інформації про платіж'));
            return $resultRedirect;
        }

        $paymentMethod = $this->_objectManager->create('PortmonePayment\Portmone\Model\Portmone');
        $paymentInfo = $paymentMethod->isPaymentValid($post);
        $paymentMethod->updateOrder($paymentInfo, $post->SHOPORDERNUMBER);

        if ($paymentInfo['status'] == 'PORTMONE_ERROR') {
            $this->messageManager->addErrorMessage(__($paymentInfo['message']));
        }

        if ($paymentInfo['status'] == 'PAYED' || $paymentInfo['status'] == 'PREAUTH') {
            $this->messageManager->addSuccessMessage(__($paymentInfo['message']));
        }

        return $resultRedirect;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
