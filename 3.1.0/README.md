# Плагін Portmone.com для Magento-3.1.0

Creator: Portmone.com   
Tags: Portmone, Magento, payment, payment gateway, credit card, debit card    
Requires at least: Magento-3.1.0    
License: Payment Card Industry Data Security Standard (PCI DSS) 
License URI: [License](https://www.portmone.com.ua/r3/uk/security/) 

Розширення для Magento дозволяє клієнтам здійснювати платежі за допомогою [Portmone.com](https://www.portmone.com.ua/).

### Опис
Цей модуль дає змогу додати у ваш магазин Magento спосіб оплати через Portmone. 
Portmone.com може безпечно, швидко та легко прийняти до оплати картки VISA та Mastercard у вашому магазині за лічені хвилини.
Прості та зрозумілі ціни, першокласний аналіз шахрайства та цілодобова підтримка.
Для роботи модуля необхідна реєстрація в сервісі.

Реєстрація в Portmone.com: [Create Free Portmone Account](https://business.portmone.com.ua/signup)    
З нами ваші клієнти можуть робити покупки у валюті UAH.

### Ручне встановлення
1.  Зайдіть до кореневої папки свого сайту;
2.  Завантажте папку "app" у корінь Вашого сайту. НЕ хвилюйтеся, файли не видаляться, а будуть додані нові для роботи системи Portmone.com; 
3.  У консолі перейдіть у корінь сайту та введіть такі команди:
	php bin/magento module:enable PortmonePayment_Portmone
	php bin/magento setup:upgrade
	php bin/magento setup:di:compile

### Налаштування модуля
4.  В адмін панелі Вашого сайту перейдіть у вкладку Store->Configuration->Sales->Payment Methods (Магазин->Конфігурація->Продажі->Методи->Оплати)

5.  Заповніть:
    - «Ідентифікатор магазину в системі Portmone.com (Payee ID)»;
    - «Логін Інтернет-магазину в системі Portmone.com»;
    - «Пароль Інтернет-магазину в системі Portmone.com»;
    - «Ключ компанії, наданий менеджером Portmone.com»;    
    Ці параметри надає менеджер; 
    - «Обмеження часу життя рахунки на оплату» - встановлює інтервал, протягом якого замовлення може бути оплачене. Заповнюється в секундах;

6. Натисніть кнопку «Зберегти».

Метод активний і з'явиться у списку оплати вашого магазину.    
P.S. Portmone.com, приймає лише валюту гривню (UAH).   
P.S. Сума платежу не конвертується у валюту гривню (UAH) автоматично. У магазині за замовчуванням має бути валюта гривня (UAH).

