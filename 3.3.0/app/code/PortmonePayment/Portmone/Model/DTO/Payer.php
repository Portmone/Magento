<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use Magento\Framework\Locale\ResolverInterface;

class Payer implements JsonSerializable
{
    private string $lang;
    private string $emailAddress;
    private string $showEmail = 'Y';

    public function __construct(
        private readonly ResolverInterface $localeResolver
    )
    {
        $this->setLang();
    }

    public function jsonSerialize(): array
    {
        return [
            'lang' => $this->lang,
            'email' => $this->emailAddress,
            'showEmail' => $this->showEmail,
        ];
    }

    public function setEmailAddress($emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    private function setLang(): void
    {
        $fullLocale = $this->localeResolver->getLocale(); // 'uk_UA'
        $languageCode = strstr($fullLocale, '_', true);   // 'uk'

        if (in_array($languageCode, ['ru', 'en', 'uk'])) {
            $this->lang = $languageCode;
        } else {
            $this->lang = 'uk';
        }
    }
}
