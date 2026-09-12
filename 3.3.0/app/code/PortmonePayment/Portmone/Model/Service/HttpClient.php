<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PortmonePayment\Portmone\Model\DTO\HttpBody;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class HttpClient
{
    private const URL = 'https://www.portmone.com.ua/gateway/';

    public function __construct(
        private Curl            $curl,
        private Json            $json,
        private LoggerInterface $logger
    )
    {
    }

    public function getPortmoneOrderData(HttpBody $body): array
    {
        $result = $this->curlRequest($body);
        $data = $this->getData($result);

        if ($data['errorCode'] != '0') {
            throw new LocalizedException(
                new Phrase('#14P ' . (string)$result['error'])
            );
        }

        return $data;
    }

    private function getData(array $result): array
    {
        if (count($result) == 0) {
            throw new LocalizedException(
                new Phrase('#15P У системі Portmone.com цього платежу немає, він повернутий чи створений некоректно')
            );
        }

        if (isset($result['errorCode']) && $result['errorCode'] != '0') {
            throw new LocalizedException(
                new Phrase('#16P ' . (string)$result['error'])
            );
        }

        return $result[0];
    }

    private function curlRequest(HttpBody $data): array
    {
        $this->curl->addHeader("Content-Type", "application/json");

        $payload = $this->json->serialize($data);

        $this->curl->post(self::URL, $payload);

        $status = $this->curl->getStatus();
        $responseBody = $this->curl->getBody();

        if ($status !== 200) {
            $this->logger->critical("Portmone API cURL request failed: " . $responseBody . ' status code:' . $status);
            throw new LocalizedException(
                new Phrase('17P Помилка при надсиланні запиту  status code: %1', [$status])
            );
        }

        return $this->json->unserialize($responseBody);
    }
}