<?php

namespace PortmonePayment\Portmone\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Portmone extends AbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_code = 'portmone';
    protected $_isOffline = true;
    protected $_formBlockType = 'PortmonePayment\Portmone\Block\Form\portmone';
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';
    protected $orderFactory;

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

}
