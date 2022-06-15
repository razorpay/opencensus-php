<?php

namespace RZP\Models\Merchant\Merchant1ccComments;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_1cc_comments';

    public function findByMerchantAndFlowType($merchantId, $flow)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::FLOW, '=', $flow)
            ->first();
    }

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::DELETED_AT, '=', null);
    }
}
