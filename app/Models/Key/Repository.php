<?php

namespace RZP\Models\Key;

use RZP\Models\Base;
use RZP\Models\Base\QueryCache\CacheQueries;

class Repository extends Base\Repository
{
    use CacheQueries;

    // Cache TTL defined in minutes
    const CACHE_TTL = 5;

    protected $entity = 'key';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
    ];

    public function getKeysForMerchant($merchantId, $expired = false)
    {
        $query = $this->newQuery()->merchantId($merchantId);

        if ($expired === false)
        {
            $query->notExpired();
        }

        return $query->get();
    }

    public function findNotExpired($keyId)
    {
        return $this->newQuery()->notExpired()->find($keyId);
    }

    public function findByMerchantIdAndKeyId($merchantId, $keyId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $keyId)
                    ->first();
    }
}
