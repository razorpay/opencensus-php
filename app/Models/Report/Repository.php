<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'report';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'required|alpha_num',
        Entity::ENTITY          => 'sometimes|string'
    ];

    public function fetchReportEntity($start, $end, $entity, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::START_TIME, '=', $start)
                    ->where(Entity::END_TIME, '=', $end)
                    ->where(Entity::ENTITY, '=', $entity)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }
}
