<?php

namespace RZP\Models\DeviceDetail;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'user_device_detail';

    public function fetchByMerchantIdAndUserId(string $merchantId, string $userId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::USER_ID, '=', $userId)
            ->first();
    }
}
