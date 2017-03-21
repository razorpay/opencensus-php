<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Merchant\Schedule as MerchantSchedule;

class Core extends Base\Core
{
    /**
     * Create a merchant schedule entity and deletes the existing entity if any
     */
    public function createOrUpdate(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        return $this->repo->transaction(function() use ($merchant, $entity, $input)
        {
            $merchantSchedule = $this->create($merchant, $entity, $input);

            $currentSchedule = $this->repo->merchant_schedule
                                    ->fetchDuplicate($merchantSchedule);

            if ($currentSchedule !== null)
            {
                $this->repo->deleteOrFail($currentSchedule);
            }

            $this->repo->saveOrFail($merchantSchedule);

            return $merchantSchedule;
        });
    }

    /**
     * Creates merchant schedule entity
     */
    public function create(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        $merchantSchedule = (new MerchantSchedule\Entity)->build($input);

        $merchantSchedule->merchant()->associate($merchant);

        $merchantSchedule->entity()->associate($entity);

        $scheduleId = $input[MerchantSchedule\Entity::SCHEDULE_ID];

        $merchantId = Merchant\Account::SHARED_ACCOUNT;

        $schedule = $this->repo->schedule->findByIdAndMerchantId($scheduleId, $merchantId);

        $merchantSchedule->schedule()->associate($schedule);

        return $merchantSchedule;
    }

    /**
     * Get All Settlement schedules assigned to merchant for payment method
     */
    public function getMerchantSettlementSchedule(Merchant\Entity $merchant, $method)
    {
        $merchantSchedules = $this->repo->merchant_schedule
                                  ->fetchByMerchant($merchant, Type::SETTLEMENT);

        if ($merchantSchedules->count() > 0)
        {
            $schedule = $this->filterAndGetScheduleByMethodOrDefault(
                                    $merchantSchedules, $method);
        }
        else
        {
            $schedule = $merchant->schedule;
        }

        return $schedule;
    }

    /**
     * Filter a schedule by method or default
     */
    protected function filterAndGetScheduleByMethodOrDefault(
        $merchantSchedules,
        $method)
    {
        $schedule = null;

        foreach ($merchantSchedules as $merchantSchedule)
        {
            $scheduleMethod = $merchantSchedule->getMethod();

            if ($scheduleMethod === $method)
            {
                $schedule = $merchantSchedule->schedule;

                break;
            }
            else if ($scheduleMethod === null)
            {
                $schedule = $merchantSchedule->schedule;
            }
        }

        return $schedule;
    }
}
