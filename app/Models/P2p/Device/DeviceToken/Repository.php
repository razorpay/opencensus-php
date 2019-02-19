<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_device_token';

    public function fetchVerifiedTokens()
    {
        return $this->newP2pQuery()
                    ->verified()
                    ->get();
    }
}
