<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Schedule as MerchantSchedule;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_schedule';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID             => 'sometimes|alpha_dash|max:20',
        Entity::METHOD                  => 'sometimes|string|max:20',
        Entity::SCHEDULE_ID             => 'sometimes|alpha_dash|max:20',
    );

    public function fetchDuplicate(MerchantSchedule\Entity $merchantSchedule)
    {
        $query = $this->newQuery()
                      ->merchantId($merchantSchedule->getMerchantId());

        $method = $merchantSchedule->getMethod();

        if ($method === null)
        {
            $query->whereNull(MerchantSchedule\Entity::METHOD);
        }
        else
        {
            $query->where(MerchantSchedule\Entity::METHOD, $method);
        }

        return $query->first();
    }

    public function fetchScheduleCountById(string $scheduleId)
    {
        return $this->newQuery()
                    ->where(MerchantSchedule\Entity::SCHEDULE_ID, $scheduleId)
                    ->count();
    }
}
