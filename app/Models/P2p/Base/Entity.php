<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const DEVICE_ID     = 'device_id';
    const REFRESHED_AT  = 'refreshed_at';

    protected static $doesEntityHasMerchant = false;
    protected static $doesEntityHasDevice   = false;
    protected static $doesEntityHasHandle   = false;


    public function getRefreshed()
    {
        return $this->getAttribute(self::REFRESHED_AT);
    }

    public function hasDevice(): bool
    {
        return self::$doesEntityHasDevice;
    }

    public function hasMerchant(): bool
    {
        return self::$doesEntityHasMerchant;
    }

    public function hasHandle(): bool
    {
        return self::$doesEntityHasHandle;
    }
}
