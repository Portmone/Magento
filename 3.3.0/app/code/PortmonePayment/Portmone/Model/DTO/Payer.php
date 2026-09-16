<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;

class Payer implements JsonSerializable
{
    private const string SHOW_EMAIL = 'Y';

    public function __construct(
        readonly private string $lang,
        readonly private string $emailAddress,
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'lang' => $this->lang,
            'email' => $this->emailAddress,
            'showEmail' => self::SHOW_EMAIL,
        ];
    }
}

