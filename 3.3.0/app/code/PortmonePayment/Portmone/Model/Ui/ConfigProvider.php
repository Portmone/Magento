<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private readonly ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig(): array
    {

        $paymentMode = $this->scopeConfig->getValue(
            'payment/portmone/payment_mode',
            ScopeInterface::SCOPE_STORE
        );

        $buttonText = $this->scopeConfig->getValue(
            'payment/portmone/title_btn',
            ScopeInterface::SCOPE_STORE
        );

        $buttonInstallmentText = $this->scopeConfig->getValue(
            'payment/portmone/installment_title_btn',
            ScopeInterface::SCOPE_STORE
        );

        $installmentFlag = $this->scopeConfig->getValue(
            'payment/portmone/installment',
            ScopeInterface::SCOPE_STORE
        );

        $installmentMinAmount = $this->scopeConfig->getValue(
            'payment/portmone/installment_min_amount',
            ScopeInterface::SCOPE_STORE
        ) ?? 0;

        return [
            'payment' => [
                'portmone' => [
                    'paymentMode' => $paymentMode,
                    'buttonText' => $buttonText ?: new Phrase('Оформити'),
                    'installmentFlag' => $installmentFlag,
                    'buttonInstallmentText' => $buttonInstallmentText ?: new Phrase('Оформити розтермінування'),
                    'installmentMinAmount' => $installmentMinAmount,
                    'errorMessage' => [
                        'iframeGetDataResponse' => new Phrase('Не вдалося підготувати оплату через Portmone.'),
                        'iframeGetDataFail' => new Phrase('Не вдалося отримати дані для оплати через Portmone.'),
                        'iframeSuccessFail' => new Phrase('Не вдалося змінити статус замовлення.') . ' ' . new Phrase('Будь ласка, зв\'яжіться з нами, щоб отримати допомогу.'),
                    ],
                ]
            ]
        ];
    }
}
