<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Separator extends Field
{
    protected $_template = 'PortmonePayment_Portmone::system/config/form/field/separator.phtml';

    public function render(AbstractElement $element): string
    {
        $this->setElement($element);

        return $this->toHtml();
    }
}

