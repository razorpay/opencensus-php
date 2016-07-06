<?php

namespace RZP\Models\Key;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Key';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
    );

    public function getKeysForMerchant($merchantId, $expired = false)
    {
        $repo = $this->repo;

        $query = $repo::MerchantId($merchantId);

        $query = ($expired === true) ?: $query->notExpired();

        return $query->get();
    }

    public function findNotExpired($keyId)
    {
        $repo = $this->repo;

        return $repo::notExpired()->find($keyId);
    }

    public function findByMerchantIdAndKeyId($merchantId, $keyId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $keyId)
                    ->first();
    }
}