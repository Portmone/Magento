<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use stdClass;

class HttpBody implements JsonSerializable
{
    private string $id = '1';

    public function __construct(
        readonly private string $method,
        readonly private stdClass $params,
    )
    {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

}