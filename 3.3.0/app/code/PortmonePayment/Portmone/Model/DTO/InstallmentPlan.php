<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;

class InstallmentPlan implements JsonSerializable
{
    public function __construct(
       readonly private array $banks
    )
    {
    }

    public function jsonSerialize(): array
    {
        return $this->banks;
    }

    public function getBanks(): array
    {
        return $this->banks;
    }
}
