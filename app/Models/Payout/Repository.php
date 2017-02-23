<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;
use RZP\Models\Payout;

class Repository extends Base\Repository
{
    protected $entity = 'payout';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        Entity::CUSTOMER_ID        => 'sometimes|alpha_num|size:14',
        Entity::METHOD             => 'sometimes|string',
    ];

    public function fetchCreatedPayouts($timestamp, $method)
    {
        return $this->newQuery()
                    ->where(Payout\Entity::CREATED_AT, '<', $timestamp)
                    ->where(Payout\Entity::STATUS, '=', Payout\Status::CREATED)
                    ->where(Payout\Entity::METHOD, '=', $method)
                    ->orderBy(Payout\Entity::ID)
                    ->get();
    }
}
