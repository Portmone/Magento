<?php
namespace PortmonePayment\Portmone\Controller\Redirect;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface
{
    private $request;
    private $orderRepository;
    private $redirectFactory;
    private $checkoutSession;
    private $logger;

    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        RedirectFactory $redirectFactory,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->redirectFactory = $redirectFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultRedirect = $this->redirectFactory->create();

        $orderId = $this->request->getParam('order_id');
        $paymentType = $this->request->getParam('type');

        if (!$orderId) {
            return $resultRedirect->setPath('checkout/cart');
        }

        try {
            // 1. Завантажуємо замовлення
            $order = $this->orderRepository->get($orderId);

            $amount = $order->getGrandTotal();
            $currency = $order->getOrderCurrencyCode();
            $incrementId = $order->getIncrementId();

            // 2. Змінюємо статус замовлення на "Очікує оплати"
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $this->orderRepository->save($order);

            // 3. Формуємо параметри для Portmone
            $payeeId = "1111"; // Тимчасовий ID для тесту
            $gatewayUrl = "https://portmone.com.ua";

            $paymentParams = [
                'payee_id'          => $payeeId,
                'shop_order_number' => $incrementId,
                'bill_amount'       => (float)$amount,
                'description'       => 'Order #' . $incrementId,
                'success_url'       => 'http://127.0.0',
                'failure_url'       => 'http://127.0.0',
                'lang'              => 'uk'
            ];

            if ($paymentType === 'installment') {
                $paymentParams['EXP_TIME'] = '3';
            }

            $finalBankUrl = $gatewayUrl . '?' . http_build_query($paymentParams);

            // 4. ОЧИЩЕННЯ КОШИКА РОБИМО ТУТ (КОЛИ ВСЕ ІНШЕ ПРОЙШЛО УСПІШНО)
            $this->checkoutSession->clearQuote();

            // 5. Перенаправлення на Portmone
            return $resultRedirect->setUrl($finalBankUrl);

        } catch (\Exception $e) {
            $this->logger->critical('Portmone Redirect Exception: ' . $e->getMessage());
            return $resultRedirect->setPath('checkout/cart');


           /*
            // Записуємо помилку в лог
            $this->logger->critical('Portmone Error: ' . $e->getMessage());

            // Якщо сталася помилка — зупиняємо виконання і виводимо її на екран!
            // Це завадить редіректу в порожній кошик і покаже проблему
            echo "<h2>Критична помилка в контролері:</h2>";
            echo "<pre>" . $e->getMessage() . "</pre>";
            echo "<h3>Стек трейс:</h3>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            exit;*/
        }
    }
}
