<?php

namespace PortmonePayment\Portmone\Block\Form;

abstract class Portmone extends \Magento\Payment\Block\Form
{
    protected $_instructions;
    protected $_template = 'form/portmone.phtml';

    public function getInstructions()
    {
        if ($this->_instructions === null) {
            $method = $this->getMethod();
            $this->_instructions = $method->getConfigData('instructions');
        }
        return $this->_instructions;
    }
}
