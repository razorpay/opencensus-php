<?php

namespace RZP\Models\Key;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    // Cache TTL defined in minutes
    const CACHE_TTL = 5;

    protected $entity = 'key';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
    );

    protected function newQuery()
    {
        $query = parent::newQuery();

        if ($this->app->environment('testing') === false)
        {
            $query->cacheDriver('query_cache');
        }

        return $query->prefix('rememberable:v1');
    }

    public function find($id, $columns = ['*'])
    {
        return $this->newQuery()
                    ->remember(self::CACHE_TTL)
                    ->cacheTags('keys_' . $id)
                    ->find($id, $columns);
    }

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
