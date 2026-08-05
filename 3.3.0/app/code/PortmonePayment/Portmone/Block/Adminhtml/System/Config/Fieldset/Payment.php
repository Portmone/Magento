<?php

namespace PortmonePayment\Portmone\Block\Adminhtml\System\Config\Fieldset;

use \Magento\Paypal\Block\Adminhtml\System\Config\Fieldset;

class Payment extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    protected $_backendConfig;
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Config\Model\Config $backendConfig,
        array $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }
}
