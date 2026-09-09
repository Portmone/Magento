<?php

declare(strict_types=1);

namespace PortmonePayment\Portmone\Model\DTO;

use JsonSerializable;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class InstallmentPlan implements JsonSerializable
{
    private array $banks = [];
    private array $mapperBanks = [
        'privatbank' => 'privat24',
        'oschadbank' => 'oschad',
        'monobank' => 'monobank',
        'pumb' => 'pumb',
        'otp' => 'otp',
        'abank' => 'abank',
    ];


    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) { }

    public function getBanks(): array
    {
        return $this->banks;
    }

    public function setBanks(): void
    {
        $installmentBanks = $this->scopeConfig->getValue(
            'payment/portmone/installment_banks',
            ScopeInterface::SCOPE_STORE
        ) ?? '{}';

        $banks = json_decode($installmentBanks, true);

        foreach ($banks as $bankName => $bankSettings ) {
            if (!isset($this->mapperBanks[$bankName])) {
                continue;
            }

            if ($bankSettings['enabled'] == 0) {
                continue;
            }

            $this->banks[$this->mapperBanks[$bankName]] = (object)[];
            if (isset($bankSettings['term']) && (int) $bankSettings['term'] > 0) {
                $this->banks[$this->mapperBanks[$bankName]] = (object)['parts' => $bankSettings['term']];
            }
        }
    }

    public function jsonSerialize(): array
    {
        return $this->banks;
    }

}
