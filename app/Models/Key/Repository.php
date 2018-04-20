<?php

namespace RZP\Models\Key;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\QueryCache\CacheQueries;

class Repository extends Base\Repository
{
    use CacheQueries;

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

    public function getFirstActiveKeyForMerchantOrFail(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->firstOrFail();
    }

    /**
     * @param  string      $merchantId
     * @return Entity|null
     */
    public function getLatestActiveKeyForMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->latest()
                    ->first();
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
