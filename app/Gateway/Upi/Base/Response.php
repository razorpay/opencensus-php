<?php

namespace RZP\Gateway\Upi\Base;

use Illuminate\Support\Collection;

class Response extends Collection
{
    const MANDATE = 'mandate';
    const UPI     = 'upi';

    const VERSION = 'version';

    const V2 = 'v2';

    public function isV2(): bool
    {
        return ($this->get(self::VERSION) === self::V2);
    }

    public function getUpi(): array
    {
        if ($this->isV2() === true)
        {
            $attributes = $this->get(self::UPI);
        }
        else
        {
            $attributes = $this->toArray();
        }

        return $attributes;
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

    public function toArrayTrace(): array
    {
       return $this->toArray();
    }
}
