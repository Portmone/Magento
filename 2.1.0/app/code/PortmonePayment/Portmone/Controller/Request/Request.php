<?php

namespace PortmonePayment\Portmone\Controller\Request;

use \Magento\Framework\App\Action\Context;

use \Magento\Framework\App\Request\Http;

use \Magento\Sales\Model\OrderFactory;

use \Magento\Framework\View\Result\PageFactory;

use PortmonePayment\Portmone\Model\Portmone;

class Request extends \Magento\Framework\App\Action\Action
{
    protected $urlBuilder;

    public $request;

    public $storeManager;

    public $objectManager;

    public $baseurl;

    public $order;

    public $orderFactory;

    protected $resultPageFactory;


    public function __construct(

        Context $context,
        Http $request,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderFactory = $orderFactory;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->baseurl = $this->storeManager->getStore()->getBaseUrl();
        $this->request = $request;
        return parent::__construct($context);
    }

    public function execute()
    {
        $paymentMethod = $this->_objectManager->create('PortmonePayment\Portmone\Model\Portmone');
        $request = $this->request->getPost();
        $paymentMethod->process($request);
    }

    public function getPost()
    {
        $this->baseurl . 'portmone/request/request';
        return $this->request->getPost();
    }

}