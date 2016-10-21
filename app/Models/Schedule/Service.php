<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Schedule as MerchantSchedule;

class Service extends Base\Service
{
    public function createSchedule($input)
    {
        $schedule = (new Core)->createSchedule($input);

        return $schedule->toArrayPublic();
    }

    public function getScheduleById($id)
    {
        $schedule = $this->repo->schedule->getScheduleByIdAndOwnerId($id, $this->merchant->getId());

        return $schedule->toArrayPublic();
    }

    public function editSchedule($id, $input)
    {
        $schedule = $this->repo->schedule->getScheduleByIdAndOwnerId($id, $this->merchant->getId());

        $schedule = (new Schedule\Core)->editSchedule($schedule, $input);

        return $schedule->toArrayPublic();
    }

    public function assignSchedule($input)
    {
        if (isset($input[MerchantSchedule\Entity::SCHEDULE_ID]) === true)
        {
            $data = $this->createOrUpdateMerchantSchedule($input);
        }
        else
        {
            if(isset($input['schedule']) === true)
            {
                $schedule = (new Schedule\Core)->createSchedule($input['schedule']);

                $merchantScheduleAttributes = [
                    MerchantSchedule\Entity::MERCHANT_ID => $input[MerchantSchedule\Entity::MERCHANT_ID],
                    MerchantSchedule\Entity::SCHEDULE_ID => $schedule->getId(),
                ];

                $data = $this->createOrUpdateMerchantSchedule($merchantScheduleAttributes);
            }
        }

        return $data;
    }

    protected function createOrUpdateMerchantSchedule($attributes)
    {
        $merchantSchedule = (new MerchantSchedule\Repository)
                                    ->findByMerchantId($attributes[MerchantSchedule\Entity::MERCHANT_ID]);

        if($merchantSchedule === null)
        {
            $merchantSchedule = (new MercantSchedule\Core)->createSchedule($attributes);
        }
        else
        {
            $merchantSchedule->fill($attributes);

            $merchantSchedule = $this->repo->saveOrFail($merchantSchedule);
        }

        $merchantSchedule->toArrayPublic();
    }
}
