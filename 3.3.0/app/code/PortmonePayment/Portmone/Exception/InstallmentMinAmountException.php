<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class InstallmentMinAmountException  extends LocalizedException
{
    /**
     *
     * @param Phrase $phrase
     * @param \Exception|null $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase, \Exception $cause = null, $code = 0)
    {
        parent::__construct($phrase, $cause, $code);
    }
}