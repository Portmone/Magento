<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class InstallmentBanks extends Field
{
    public function getBanks(): array
    {
        return [
            'privatbank' => 'ПриватБанк',
            'oschadbank' => 'Ощадбанк',
            'monobank' => 'monobank',
            'pumb' => 'ПУМБ',
            'otp' => 'OTP',
            'abank' => 'A-Bank',
        ];
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->setTemplate(
            'PortmonePayment_Portmone::system/config/form/field/installment-banks.phtml'
        )->assign([
            'element' => $element,
        ])->toHtml();
    }
}