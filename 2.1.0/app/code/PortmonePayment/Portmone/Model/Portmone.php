<?php

namespace PortmonePayment\Portmone\Model;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;



class Portmone extends AbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_code = 'portmone';
    protected $_isOffline = true;
    protected $_formBlockType = 'PortmonePayment\Portmone\Block\Form\portmone';
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';
    protected $_actionUrl = "https://www.portmone.com.ua/gateway/";
    protected $_test;
    protected $orderFactory;

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {

        $this->orderFactory = $orderFactory;

        parent::__construct($context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data);
    }


    public function getAmount($orderId)
    {
        $orderFactory = $this->orderFactory;
        $order = $orderFactory->create()->loadByIncrementId($orderId);
        return $order->getGrandTotal();
    }

    protected function getOrder($orderId)
    {
        $orderFactory = $this->orderFactory;
        return $orderFactory->create()->loadByIncrementId($orderId);

    }

    public function initialize($paymentAction, $stateObject)
    {
        $this->_actionUrl = $this->getConfigData('action_url');
        $this->_test = $this->getConfigData('test');
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus(Order::STATE_NEW);
        $stateObject->setIsNotified(false);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

    public function getActionUrl()
    {
        return $this->_actionUrl;
    }

    protected function isCarrierAllowed($shippingMethod)
    {
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
    }

    public function getCurrencyCode($orderId)
    {
        return $this->getOrder($orderId)->getBaseCurrencyCode();
    }

    public function getPostData($orderId)
    {
        $FormData = [];
        $FormData['payee_id']           = $this->getConfigData('payee_id');
        $FormData['shop_order_number']  = $orderId.'_'.time();
        $FormData['bill_amount']        = round($this->getAmount($orderId), 2);
        $FormData['description']        = "Payment for order #" . $orderId;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseurl = $storeManager->getStore()->getBaseUrl();
        $FormData['success_url']        = $baseurl . 'checkout/onepage/success';
        $FormData['failure_url']        = $baseurl;
        $FormData['lang']               = 'ru';

        return $FormData;
    }

    public function process($response)
    {

    }

    protected function _processOrder(\Magento\Sales\Model\Order $order, $response)
    {

    }

}
