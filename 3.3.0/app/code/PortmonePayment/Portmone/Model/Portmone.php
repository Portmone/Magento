<?php

namespace PortmonePayment\Portmone\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use mysql_xdevapi\TableUpdate;

class Portmone extends AbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_code = 'portmone';
    protected $_isOffline = true;
    protected $_formBlockType = 'PortmonePayment\Portmone\Block\Form\portmone';
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';
    protected $_actionUrl = "https://www.portmone.com.ua/gateway/";
    protected $_gatawayUrl = "https://www.portmone.com.ua/gateway/";
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
        $FormData['payee_id'] = $this->getConfigData('payee_id');
        $FormData['shop_order_number'] = $orderId . '_' . time();
        $FormData['bill_amount'] = round($this->getAmount($orderId), 2);
        $FormData['description'] = "Payment for order #" . $orderId;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseurl = $storeManager->getStore()->getBaseUrl();
        $FormData['success_url'] = $baseurl . 'portmone/Payment/Callback';
        $FormData['failure_url'] = $baseurl . 'portmone/Payment/Callback';
        $FormData['lang'] = 'uk';
        $FormData['exp_time'] = $this->getConfigData('exp_time');;
        $FormData['dt'] = date("YmdHis");
        $FormData['encoding'] = 'UTF-8';
        $FormData['preauth_flag'] = $this->getConfigData('preauth_flag') == 1 ? 'Y' : 'N';;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $FormData['cms_module_name'] = json_encode(['name' => 'Magento2', 'v' => $productMetadata->getVersion()]);

        $strToSignature = $FormData['payee_id'] . $FormData['dt'] . bin2hex($FormData['shop_order_number']) . $FormData['bill_amount'];
        $strToSignature = strtoupper($strToSignature) . strtoupper(bin2hex($this->getConfigData('secret_key')));
        $FormData['signature'] = strtoupper(hash_hmac('sha256', $strToSignature, $this->getConfigData('user_key')));

        return $FormData;
    }

    public function process()
    {

    }

    /**
     * @return array|string[]|void
     */
    public function isPaymentValid($post)
    {
        if (empty($post->SHOPORDERNUMBER)) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Немає інформації про номер замовлення'
            ];
        }

        $data = [
            "method" => "result",
            "payee_id" => $this->getConfigData('payee_id'),
            "login" => $this->getConfigData('secret_key'),
            "password" => $this->getConfigData('test_key'),
            "shop_order_number" => $post->SHOPORDERNUMBER,
        ];

        $result = $this->curlRequest($this->_gatawayUrl, $data);
        $parseXml = $this->parseXml($result);

        if ($parseXml === false) {
            if ($post->RESULT == '0') {
                return [
                    'status' => 'PAYED',
                    'message' => 'Дякуємо за покупку'
                ];
            } else {
                return [
                    'status' => 'PORTMONE_ERROR',
                    'message' => 'Помилка авторизації. Введено неправильний логін або пароль'
                ];
            }
        }

        $payeeIdReturn = (array)$parseXml->request->payee_id;
        $orderData = (array)$parseXml->orders->order;

        if ($post->RESULT !== '0') {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => $post->RESULT
            ];
        }


        if ($payeeIdReturn[0] != $this->getConfigData('payee_id')) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Під час здійснення оплати виникла помилка. Дані Інтернет-магазину некоректні'
            ];
        }

        if (count($parseXml->orders->order) == 0) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'У системі Portmone цього платежу немає, він повернутий або створений некоректно'
            ];
        } elseif (count($parseXml->orders->order) > 1) {

            $isPay = false;
            foreach ($parseXml->orders->order as $order) {
                $status = (array)$order->status;
                if ($status[0] == 'PREAUTH') {
                    return [
                        'status' => 'PREAUTH',
                        'message' => 'Дякуємо за покупку'
                    ];
                }
                if ($status[0] == 'PAYED') {
                   return [
                        'status' => 'PAYED',
                        'message' => 'Дякуємо за покупку'
                    ];
                }
            }

            if ($isPay == false) {
                return [
                    'status' => 'PORTMONE_ERROR',
                    'message' => 'У системі Portmone цього платежу немає, він повернутий або створений некоректно'
                ];
            }
        }

        if ($orderData['status'] == 'REJECTED' || $orderData['status'] == 'CREATED') {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Під час здійснення оплати виникла помилка. Перевірте дані вашої картки та спробуйте здійснити оплату ще раз'
            ];
        }

        if ($orderData['status'] == 'PREAUTH') {
            return [
                'status' => 'PREAUTH',
                'message' => 'Дякуємо за покупку'
            ];
        }

        if ($orderData['status'] == 'PAYED') {
            return [
                'status' => 'PAYED',
                'message' => 'Дякуємо за покупку'
            ];
        }
    }

    public function updateOrder(&$paymentInfo, string $shopOrderNumber)
    {
        $order = $this->getOrder($this->getOrderId($shopOrderNumber));
        if (empty($order)) {
            $paymentInfo = [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Замовлення не знайдено'
            ];
            return;
        }

        if ($paymentInfo['status'] == 'PORTMONE_ERROR') {
            $order->cancel();
            $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, __($paymentInfo['message']));
            $order->save();
        }

        if ($paymentInfo['status'] == 'PAYED') {
            //$order->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_COMPLETE));
            $order->setData('state', \Magento\Sales\Model\Order::STATE_COMPLETE);
            $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_COMPLETE, __($paymentInfo['message']));
            $order->save();
        }

        if ($paymentInfo['status'] == 'PREAUTH') {
            $order->hold();
            $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_HOLDED, __($paymentInfo['message']));
            $order->setShopOrderNumber($shopOrderNumber);
            $order->save();
        }

    }

    public function preauthResult(string $shopOrderNumber): array
    {
        $data = [
            "method" => "result",
            "payee_id" => $this->getConfigData('payee_id'),
            "login" => $this->getConfigData('secret_key'),
            "password" => $this->getConfigData('test_key'),
            "shop_order_number" => $shopOrderNumber,
        ];

        $result = $this->curlRequest($this->_gatawayUrl, $data);
        $parseXml = $this->parseXml($result);
        if ($parseXml === false) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Помилка авторизації. Введено неправильний логін або пароль',
                'orderData' => [],
            ];
        }

        $payeeIdReturn = (array)$parseXml->request->payee_id;
        $orderData = (array)$parseXml->orders->order;

        if ($payeeIdReturn[0] != $this->getConfigData('payee_id')) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Під час здійснення підтвердження / скасування виникла помилка. Дані Інтернет-магазину некоректні',
                'orderData' => [],
            ];
        }

        if (count($parseXml->orders->order) == 0) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'У системі Portmone цього платежу немає, він повернутий або створений некоректно',
                'orderData' => [],
            ];
        } elseif (count($parseXml->orders->order) > 1) {

            $isPay = false;
            foreach ($parseXml->orders->order as $order) {
                $status = (array)$order->status;
                if ($status[0] == 'PREAUTH') {
                    return [
                        'status' => 'PREAUTH',
                        'message' => 'Дякуємо за покупку',
                        'orderData' => (array)$order,
                    ];
                }
            }

            if ($isPay == false) {
                return [
                    'status' => 'PORTMONE_ERROR',
                    'message' => 'У системі Portmone цього платежу немає, він повернутий або створений некоректно',
                    'orderData' => [],
                ];
            }
        }

        if ($orderData['status'] == 'PREAUTH') {
            return [
                'status' => 'PREAUTH',
                'message' => 'Дякуємо за покупку',
                'orderData' => $orderData,
            ];
        }

        return [
            'status' => 'PORTMONE_ERROR',
            'message' => 'У системі Portmone цього платежу немає, він повернутий або створений некоректно',
            'orderData' => [],
        ];

    }

    public function preauthSetPaid(string $shopBillId, string $postauthAmount): array
    {
        $data = [
            "method" => "preauth",
            "action" => "set_paid",
            "login" => $this->getConfigData('secret_key'),
            "password" => $this->getConfigData('test_key'),
            "shop_bill_id" => $shopBillId,
            "postauth_amount" => $postauthAmount,
            "encoding" => "utf-8",
            "lang" => "uk",
        ];

        $result = $this->curlRequest($this->_gatawayUrl, $data);
        $parseXml = $this->parseXml($result);
        if ($parseXml === false) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Помилка авторизації. Введено неправильний логін або пароль',
            ];
        }

        $orderData = $parseXml->order;
        if ($orderData->error_code != 0 ) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => $orderData['error_message'],
            ];
        }

        if ($orderData->status == 'PAYED') {
            return [
                'status' => 'PAYED',
                'message' => 'Оплату підтверджено на суму ' . $orderData->bill_amount,
            ];
        }

        return [
            'status' => 'PORTMONE_ERROR',
            'message' => 'Невідома помилка',
        ];
    }

    public function preauthReject(string $shopBillId)
    {
        $data = [
            "method" => "preauth",
            "action" => "reject",
            "login" => $this->getConfigData('secret_key'),
            "password" => $this->getConfigData('test_key'),
            "shop_bill_id" => $shopBillId,
            "encoding" => "utf-8",
            "lang" => "uk",
        ];

        $result = $this->curlRequest($this->_gatawayUrl, $data);
        $parseXml = $this->parseXml($result);
        if ($parseXml === false) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => 'Помилка авторизації. Введено неправильний логін або пароль',
            ];
        }

        $orderData = $parseXml->order;
        if ($orderData->error_code != 0 ) {
            return [
                'status' => 'PORTMONE_ERROR',
                'message' => $orderData['error_message'],
            ];
        }

        if ($orderData->status == 'REJECTED') {
            return [
                'status' => 'REJECTED',
                'message' => 'Оплату скасовано',
            ];
        }

        return [
            'status' => 'PORTMONE_ERROR',
            'message' => 'Невідома помилка',
        ];
    }

    protected function _processOrder(\Magento\Sales\Model\Order $order, $response)
    {

    }

    /**
     * @param string $shopnumber
     * @return string
     */
    private function getOrderId(string $shopnumber): string
    {
        $shopnumbercount = strpos($shopnumber, "_");
        if ($shopnumbercount == false) {
            return $shopnumber;
        }
        return substr($shopnumber, 0, $shopnumbercount);
    }


    /**
     * @param string $url
     * @param array $data
     * @return bool|string
     */
    private function curlRequest(string $url, array $data)
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (200 !== intval($httpCode)) {
            return false;
        }
        return $response;
    }

    private function parseXml($string)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false !== $xml) {
            return $xml;
        } else {
            return false;
        }
    }
}
