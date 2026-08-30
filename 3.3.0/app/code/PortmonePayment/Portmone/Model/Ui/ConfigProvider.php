<?php
namespace PortmonePayment\Portmone\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {

        $paymentMode = $this->scopeConfig->getValue(
            'payment/portmone/payment_mode',
            ScopeInterface::SCOPE_STORE
        );

        $buttonText = $this->scopeConfig->getValue(
            'payment/portmone/title',
            ScopeInterface::SCOPE_STORE
        );

        $buttonInstallmentText = $this->scopeConfig->getValue(
            'payment/portmone/installment_title',
            ScopeInterface::SCOPE_STORE
        );

        $installmentFlag = $this->scopeConfig->getValue(
            'payment/portmone/installment',
            ScopeInterface::SCOPE_STORE
        );

        return [
            'payment' => [
                'portmone' => [
                    'paymentMode' => $paymentMode,
                    'buttonText'  => $buttonText ?: __('Оплатити через Portmone'),
                    'installmentFlag' => $installmentFlag,
                    'buttonInstallmentText'  => $buttonInstallmentText ?: __('Оплатити через Portmone (Розтермінування)'),
                ]
            ]
        ];
    }
}
