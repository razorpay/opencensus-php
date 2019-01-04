<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const DEVICE_ID     = 'device_id';
    const REFRESHED_AT  = 'refreshed_at';

    public function getRefreshed()
    {
        return $this->getAttribute(self::REFRESHED_AT);
    }

    public function hasMerchant(): bool
    {
        return false;
    }

    public function hasHandle(): bool
    {
        return false;
    }

    public function hasDevice(): bool
    {
        return false;
    }
}
