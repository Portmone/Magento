# Плагин Portmone.com для Magento-2.1.0

Creator: Portmone.com   
Tags: Portmone, Magento, payment, payment gateway, credit card, debit card    
Requires at least: Magento-2.1.0    
License: Payment Card Industry Data Security Standard (PCI DSS) 
License URI: [License](https://www.portmone.com.ua/r3/uk/security/) 

Расширение для Magento позволяет клиентам осуществлять платежи с помощью [Portmone.com](https://www.portmone.com.ua/r3/).

### Описание
Этот модуль добавляет Portmone.com в качестве способа оплаты в ваш магазин Magento. 
Portmone.com может безопасно, быстро и легко принять VISA и MasterCard в вашем магазине за считанные минуты.
Простые и понятные цены, первоклассный анализ мошенничества и круглосуточная поддержка.
Для работы модуля необходима регистрация в сервисе.

Регистрация в Portmone.com: [Create Free Portmone Account](https://www.portmone.com.ua/r3/ecommerce/sign-up)    
С нами ваши клиенты могут совершать покупки в UAH.

### Ручная установка
1.  Зайдите в корневую папку своего сайта;
2.  Загрузите папку "app" в корень Вашего сайта. НЕ переживайте , файлы не удалятся, только добавятся новые; 
3.  В консоли перейдите в корень сайта и введите следующие команды:
	php bin/magento module:enable PortmonePayment_Portmone
	php bin/magento setup:upgrade
	php bin/magento setup:di:compile

### Настройка модуля
4.  В админ панели Вашего сайта перейдите во вкладку Store->Configuration->Sales->Payment Methods (Магазин->Конфигурация->Продажи->Методы->Оплаты)

5.  Заполните:
    - «Идентификатор магазина в системе Portmone(Payee ID)»;
    - «Логин Интернет-магазина в системе Portmone»;
    - «Пароль Интернет-магазина в системе Portmone»;    
    Эти параметры предоставляет менеджер Portmone.com; 
    - прочие поля заполните по своему усмотрению.

6. Нажмите кнопку «Сохранить».

Метод активен и появится в списке оплат вашего магазина.    
P.S. Portmone, принимает только Гривны (UAH).   
P.S. Сумма платежа не конвертируется в валюту Гривны(UAH) автоматически. В магазине по умолчанию должна быть валюта Гривны (UAH).
