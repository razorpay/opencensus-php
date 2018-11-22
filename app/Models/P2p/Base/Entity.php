<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const REFRESHED_AT  = 'refreshed_at';

    public function getRefreshed()
    {
        return $this->getAttribute(self::REFRESHED_AT);
    }
}
