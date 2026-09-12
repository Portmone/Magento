<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;

class HttpBody implements JsonSerializable
{
    private string $method;

    private $params;

    private string $id = '1';

    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars( $this );
    }

}