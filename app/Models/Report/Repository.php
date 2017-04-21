<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'report';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_dash',
        Entity::TYPE            => 'sometimes|string'
    ];

    /**
     * @param   $start
     * @param   $end
     * @param   $type
     * @return  Report\Entity
     */
    public function fetchReportEntity($start, $end, $type, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::START_TIME, '=', $start)
                    ->where(Entity::END_TIME, '=', $end)
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }
}
