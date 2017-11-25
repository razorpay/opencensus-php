<?php

namespace RZP\Models\Key;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'key';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
    );

    public function find($id, $columns = array('*'))
    {
        return $this->newQuery()->remember(5)->find($id, $columns);
    }

    public function getKeysForMerchant($merchantId, $expired = false)
    {
        $query = $this->newQuery()->merchantId($merchantId);

        if ($expired === false)
        {
            $query->notExpired();
        }

        return $query->remember(5)->get();
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
                    ->remember(5)
                    ->first();
    }
}