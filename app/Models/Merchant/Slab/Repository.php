<?php

namespace RZP\Models\Merchant\Slab;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_slabs';

    public function findByMerchantIdAndType($merchantId, $type)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::DELETED_AT, '=', null)
            ->first();
    }

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::DELETED_AT, '=', null);
    }
}
