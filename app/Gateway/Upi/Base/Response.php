<?php

namespace RZP\Gateway\Upi\Base;

use Illuminate\Support\Collection;

class Response extends Collection
{
    const MANDATE       = 'mandate';
    const UPI           = 'upi';
    const TERMINAL      = 'terminal';
    const PAYMENT       = 'payment';
    const META          = 'meta';
    const INTENT_URL    = 'intent_url';

    const VERSION = 'version';

    const V2 = 'v2';

    protected $allowedUpiKeys = [
        Entity::VPA,
        Entity::IFSC,
        Entity::RECEIVED,
        Entity::NPCI_TXN_ID,
        Entity::GATEWAY_DATA,
        Entity::STATUS_CODE,
        Entity::ACCOUNT_NUMBER,
        Entity::MERCHANT_REFERENCE,
        Entity::GATEWAY_MERCHANT_ID,
        Entity::GATEWAY_PAYMENT_ID,
        Entity::NPCI_REFERENCE_ID,
    ];

    public function isV2(): bool
    {
        return ($this->get(self::VERSION) === self::V2);
    }

    public function getUpi(): array
    {
        if ($this->isV2() === true)
        {
            $attributes = $this->get(self::UPI, []);
        }
        else
        {
            $attributes = $this->toArray();
        }

        return $attributes;
    }

    public function getTerminal(): array
    {
        if ($this->isV2() === true)
        {
            return $this->get(self::TERMINAL, []);
        }

        return $this->toArray();
    }

    public function getPayment(): array
    {
        if ($this->isV2() === true)
        {
            return $this->get(self::PAYMENT, []);
        }

        return $this->toArray();
    }

    public function getFilteredUpi()
    {
        $upi = $this->getUpi();

        return array_only($upi, $this->allowedUpiKeys);
    }

    public function getMandate(): array
    {
        if ($this->isV2() === true)
        {
            $attributes = $this->get(self::MANDATE);
        }
        else
        {
            $attributes = $this->toArray();
        }

        return $attributes;
    }

    /**
     * @return string
     */
    public function getIntentUrl(): string
    {
        if ($this->isV2() === true)
        {
            return $this->get(self::INTENT_URL, '');
        }

        return '';
    }

    public function toArrayTrace(): array
    {
       $response = clone $this;

       $response->forget(self::META);

       return $response->toArray();
    }
}
