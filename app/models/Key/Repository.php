<?php

namespace Models\Key;

use Models\Base;

class Repository extends Base\Repository
{
    public static function getKeysForMerchant($merchantId, $expired = false)
    {
        $repo = $this->repo;

        $query = $repo::MerchantId($merchantId);

        $query = ($expired === true) ?: $query->notExpired();

        return $query->get();
    }

    public static function findNotExpired($keyId)
    {
        $repo = $this->repo;

        return $repo::notExpired()->find($keyId);
    }
}