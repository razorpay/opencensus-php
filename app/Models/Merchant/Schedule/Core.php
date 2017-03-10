<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Merchant\Schedule as MerchantSchedule;

class Core extends Base\Core
{
    public function createOrUpdate(Merchant\Entity $merchant, $input)
    {
        return $this->repo->transaction(function() use ($merchant, $input)
        {
            $merchantSchedule = $this->create($merchant, $input);

            $currentSchedule = $this->repo->merchant_schedule->fetchDuplicate($merchantSchedule);

            if ($currentSchedule !== null)
            {
                $this->repo->deleteOrFail($currentSchedule);
            }

            $this->repo->saveOrFail($merchantSchedule);

            return $merchantSchedule;
        });

    }

    public function create(Merchant\Entity $merchant, $input)
    {
        $merchantSchedule = (new MerchantSchedule\Entity)->build($input);

        $merchantSchedule->merchant()->associate($merchant);

        $scheduleId = $input[MerchantSchedule\Entity::SCHEDULE_ID];

        $merchantId = Merchant\Account::SHARED_ACCOUNT;

        $schedule = $this->repo->schedule->findByIdAndMerchantId($scheduleId, $merchantId);

        $merchantSchedule->schedule()->associate($schedule);

        return $merchantSchedule;
    }
}