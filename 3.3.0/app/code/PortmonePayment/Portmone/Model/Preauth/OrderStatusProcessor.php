<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Preauth;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use PortmonePayment\Portmone\Model\DTO\Preauth as PreauthDTO;
use PortmonePayment\Portmone\Model\Enum\PaymentType;
use PortmonePayment\Portmone\Model\Service\HttpClient;
use PortmonePayment\Portmone\Model\Service\ProcessPayment;
use PortmonePayment\Portmone\Model\Payee as PayeeModel;
use PortmonePayment\Portmone\Model\DTO\HttpBody;
use stdClass;

class OrderStatusProcessor
{
    public function __construct(
        private readonly ProcessPayment       $processPayment,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly HttpClient           $httpClient,
        private PayeeModel                    $payeeModel,

    )
    {
    }

    public function process(OrderInterface $order): bool
    {

        $preauthFlag = $this->scopeConfig->getValue(
            'payment/portmone/preauth_flag',
            ScopeInterface::SCOPE_STORE
        ) ?? 0;
        if ($preauthFlag != 1) {
            return false;
        }

        if (!$this->isApplicable($order)) {
            return false;
        }

        if (!$this->hasHoldedStatus($order)) {
            return false;
        }

        $shopOrderNumber = $order->getData('shop_order_number');
        $shopBillId = $order->getData('shop_bill_id');

        if (empty($shopOrderNumber) && empty($shopBillId)) {
            throw new LocalizedException(
                new Phrase('Некоректний ідентифікатор замовлення (shop_order_number або shop_bill_id).')
            );
        }

        $payeeIdOrder = $order->getData('payee_id');
        $payeeIdConfig = $this->scopeConfig->getValue(
            'payment/portmone/payee_id',
            ScopeInterface::SCOPE_STORE
        ) ?? '';

        $paymentType = PaymentType::FULL->value;
        if ($payeeIdOrder != $payeeIdConfig) {
            $paymentType = PaymentType::INSTALLMENT->value;
        }

        $portmoneOrdersData = $this->processPayment->getPortmoneOrderData($paymentType, $shopOrderNumber, '', 'PREAUTH');
        if (empty($shopBillId)) {
            $shopBillId = $portmoneOrdersData['shopBillId'];
        } elseif ($portmoneOrdersData['shopBillId'] != $shopBillId) {
            throw new LocalizedException(
                new Phrase('Некоректний shop_bill_id.')
            );
        }

        $this->payeeModel->setPaymentMode();
        $this->payeeModel->setPaymentType($paymentType);
        $this->payeeModel->setLogin();
        $this->payeeModel->setPassword();
        $this->payeeModel->setPayeeId();

        if ($order->getStatus() == 'complete') {
            $orderAmount = $order->getGrandTotal();

            if ($orderAmount > $portmoneOrdersData['billAmount']) {
                throw new LocalizedException(
                    new Phrase('Сума оплати не може бути більше за суму, на яку проводилась преавторизація.')
                );
            }

            $preauthDTO = new PreauthDTO(
                $this->payeeModel->getLogin(),
                $this->payeeModel->getPassword(),
                $this->payeeModel->getPayeeId(),
                $shopBillId,
                (float)$orderAmount
            );

            $params = new stdClass();
            $params->data = $preauthDTO;

            $httpBody = new HttpBody(
                'confirmPreauth',
                $params
            );

            $result = $this->httpClient->getPortmonePreauth($httpBody);

            if ($result['status'] == 'PAYED') {
                return true;
            }
        }

        if (in_array($order->getStatus(), ['return', 'canceled'])) {
            $preauthDTO = new PreauthDTO(
                $this->payeeModel->getLogin(),
                $this->payeeModel->getPassword(),
                $this->payeeModel->getPayeeId(),
                $shopBillId
            );

            $params = new stdClass();
            $params->data = $preauthDTO;

            $httpBody = new HttpBody(
                'rejectPreauth',
                $params
            );

            $result = $this->httpClient->getPortmonePreauth($httpBody);

            if ($result['status'] == 'REJECTED') {
                return true;
            }

        }

        throw new LocalizedException(
            new Phrase('Невідома помилка.')
        );
    }

    private function isApplicable(OrderInterface $order): bool
    {
        if (!in_array($order->getStatus(), ['complete', 'return', 'canceled'], true)) {
            return false;
        }

        $payment = $order->getPayment();
        if (!$payment || $payment->getMethod() !== 'portmone') {
            return false;
        }

        return true;
    }

    private function hasHoldedStatus(OrderInterface $order): bool
    {
        foreach ($order->getStatusHistoryCollection() as $status) {
            if ($status->getStatus() === 'holded') {
                return true;
            }
        }

        return false;
    }
}
