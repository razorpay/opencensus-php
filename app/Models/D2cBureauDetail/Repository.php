<?php

namespace RZP\Models\D2cBureauDetail;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'd2c_bureau_detail';

    public function findByUserIdAndMerchant($userId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::USER_ID, $userId)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->where(Entity::ID, $id)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function findByIdMerchantIdAndPan($id, $merchantId, $pan)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->whereRaw("UPPER(".Entity::PAN.")"."="."?",strtoupper($pan))
                    ->merchantId($merchantId)
                    ->first();
    }
}
