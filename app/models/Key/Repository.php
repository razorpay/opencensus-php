<?php

namespace Models\Key;

use Models\Base;

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
}