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

    private const PATH_COLOR = 'payment/portmone/button_color';
    private const PATH_TEXT_COLOR = 'payment/portmone/button_text_color';
    private const PATH_HEIGHT = 'payment/portmone/button_height';
    private const PATH_WIDTH = 'payment/portmone/button_width';
    private const PATH_FONT_FAMILY = 'payment/portmone/button_font_family';
    private const PATH_FONT_SIZE = 'payment/portmone/button_font_size';
    private const PATH_PADDING = 'payment/portmone/button_padding';
    private const PATH_BORDER = 'payment/portmone/button_border';
    private const PATH_BORDER_RADIUS = 'payment/portmone/button_border_radius';

    public function getConfig(): array
    {

        $storeScope = ScopeInterface::SCOPE_STORE;

        $paymentMode = $this->scopeConfig->getValue(
            'payment/portmone/payment_mode',
            $storeScope
        );

        $buttonText = $this->scopeConfig->getValue(
            'payment/portmone/title_btn',
            $storeScope
        );

        $buttonInstallmentText = $this->scopeConfig->getValue(
            'payment/portmone/installment_title_btn',
            $storeScope
        );

        $installmentFlag = $this->scopeConfig->getValue(
            'payment/portmone/installment',
            $storeScope
        );

        $installmentMinAmount = $this->scopeConfig->getValue(
            'payment/portmone/installment_min_amount',
            $storeScope
        ) ?? 0;

        $buttonStyleFlag = $this->scopeConfig->getValue(
            'payment/portmone/button_style_flag',
            $storeScope
        ) ?? 0;

        $buttonStyle = [];
        if ($buttonStyleFlag == 1) {
            if (!empty($this->scopeConfig->getValue(self::PATH_COLOR, $storeScope))) {
                $buttonStyle['background-color'] = $this->scopeConfig->getValue(self::PATH_COLOR, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_TEXT_COLOR, $storeScope))) {
                $buttonStyle['color'] = $this->scopeConfig->getValue(self::PATH_TEXT_COLOR, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_HEIGHT, $storeScope))) {
                $buttonStyle['height'] = $this->scopeConfig->getValue(self::PATH_HEIGHT, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_WIDTH, $storeScope))) {
                $buttonStyle['width'] = $this->scopeConfig->getValue(self::PATH_WIDTH, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_FONT_FAMILY, $storeScope))) {
                $buttonStyle['font-family'] = $this->scopeConfig->getValue(self::PATH_FONT_FAMILY, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_FONT_SIZE, $storeScope))) {
                $buttonStyle['font-size'] = $this->scopeConfig->getValue(self::PATH_FONT_SIZE, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_PADDING, $storeScope))) {
                $buttonStyle['padding'] = $this->scopeConfig->getValue(self::PATH_PADDING, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_BORDER, $storeScope))) {
                $buttonStyle['border'] = $this->scopeConfig->getValue(self::PATH_BORDER, $storeScope);
            }

            if (!empty($this->scopeConfig->getValue(self::PATH_BORDER_RADIUS, $storeScope))) {
                $buttonStyle['border-radius'] = $this->scopeConfig->getValue(self::PATH_BORDER_RADIUS, $storeScope);
            }
        }

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
                    'buttonStyleFlag' => $buttonStyleFlag,
                    'buttonStyle' => $buttonStyle,
                ]
            ]
        ];
    }
}
