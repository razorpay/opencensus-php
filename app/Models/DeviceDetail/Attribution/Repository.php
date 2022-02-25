<?php


namespace RZP\Models\DeviceDetail\Attribution;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'app_attribution_detail';

    public function fetchByAppsflyerId(string $appsflyerId)
    {
        return $this->newQuery()
            ->where(Entity::APPSFLYER_ID, '=', $appsflyerId)
            ->first();
    }

    public function fetchAppAttributionForMerchantsCreatedBetween($startTime, $endTime)
    {
        return $this->newQuery()
            ->whereBetween(Entity::CREATED_AT, [$startTime, $endTime])
            ->get()
            ->toArray();
    }
}
