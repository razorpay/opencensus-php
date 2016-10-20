<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'merchant_schedule';

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }
}
