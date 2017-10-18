<?php

namespace RZP\Models\Schedule\Task;

use Config;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Exception\LogicException;

class Core extends Base\Core
{
    /**
     * Create a default settlement schedule for merchant
     *
     * @param Merchant\Entity $merchant
     */
    public function createDefaultSettlementSchedule(Merchant\Entity $merchant)
    {
        $schedule = $this->getDefaultMerchantSchedule($merchant);

        $input = [
            Entity::METHOD      => null,
            Entity::TYPE        => Type::SETTLEMENT,
            Entity::SCHEDULE_ID => $schedule->getId()
        ];

        $this->createOrUpdate($merchant, $merchant, $input);
    }

    /**
     * Create a merchant schedule task entity and deletes the existing entity if any
     *
     * @param Merchant\Entity $merchant
     * @param Base\Entity     $entity
     * @param                 $input
     *
     * @return Entity
     * @throws \Exception
     */
    public function createOrUpdate(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        $scheduleTask = $this->create($merchant, $entity, $input);

        $this->app['workflow']->setEntityId($merchant->getId());

        $this->repo->transactionOnLiveAndTest(function() use ($scheduleTask)
        {
            // for settlements, we want to keep schedules in sync in test and live
            if ($scheduleTask->isTypeSettlement() === true)
            {
                $this->createOrUpdateInMode($scheduleTask, Mode::LIVE);
                $this->createOrUpdateInMode($scheduleTask, Mode::TEST);

                // Notify slack only in the case of settlement schedule_task
                $this->traceAndNotifyScheduleAssignment($scheduleTask);
            }
            else
            {
                $this->createOrUpdateInMode($scheduleTask, $this->mode);
            }
        });

        return $scheduleTask;
    }

    /**
     * Creates merchant schedule entity
     *
     * @param Merchant\Entity $merchant
     * @param Base\Entity     $entity
     * @param                 $input
     *
     * @return Entity
     */
    public function create(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        $scheduleTask = (new Entity)->build($input);

        $scheduleTask->merchant()->associate($merchant);

        $scheduleTask->entity()->associate($entity);

        $scheduleId = $input[Entity::SCHEDULE_ID];

        $merchantId = Merchant\Account::SHARED_ACCOUNT;

        $schedule = $this->repo->schedule->findByIdAndMerchantId($scheduleId, $merchantId);

        $scheduleTask->schedule()->associate($schedule);

        $scheduleTask->updateNextRunAt($scheduleTask->getNextRunAt());

        return $scheduleTask;
    }

    /**
     * Get All Settlement schedules assigned to merchant for payment method
     *
     * @param Merchant\Entity $merchant
     * @param                 $method
     *
     * @return null|Entity
     */
    public function getMerchantSettlementSchedule(Merchant\Entity $merchant, $method)
    {
        $scheduleTasks = $this->repo
                              ->schedule_task
                              ->fetchByMerchant($merchant, Type::SETTLEMENT);

        $scheduleTask = $this->filterAndGetScheduleByMethodOrDefault(
                                    $scheduleTasks,
                                    $method);

        return $scheduleTask;
    }

    /**
     * Get the next applicable time for a method, based
     * on the schedule assigned to a merchant
     *
     * @param int             $startTime The next applicable time is computed from
     *                                   this value
     * @param Merchant\Entity $merchant
     * @param string|null     $method    Can be a specific method - `card`, `upi`;
     *                                   else `null` for the generic schedule
     *
     * @return int
     * @throws LogicException
     */
    public function getNextApplicableTimeForMerchant(
        int $startTime,
        Merchant\Entity $merchant,
        string $method = null): int
    {
        // Fetch the merchant's schedule_task for the method
        $scheduleTask = $this->getMerchantSettlementSchedule($merchant, $method);

        //
        // Null check for legacy reasons:
        // The `getMerchantSettlementSchedule()` should now
        // return Schedule\Task\Entity always.
        //
        if ($scheduleTask === null)
        {
            $data = [
                'merchant_id' => $merchant->getId(),
                'method'      => $method,
            ];

            throw new LogicException('schedule_task not found for merchant settlement', null, $data);
        }

        $schedule = $scheduleTask->schedule;

        $nextRunAt = $scheduleTask->getNextRunAt();

        return Schedule\Library::getNextApplicableTime($startTime, $schedule, $nextRunAt);
    }

    protected function createOrUpdateInMode(Entity $scheduleTask, string $mode)
    {
        $entity = clone $scheduleTask;

        $entity->setConnection($mode);

        $currentScheduleTask = $this->repo
                                    ->schedule_task
                                    ->connection($mode)
                                    ->fetchExistingScheduleTask($entity);

        $originalData = [];

        if ($currentScheduleTask !== null)
        {
            $entity->updateNextRunAt($currentScheduleTask->getNextRunAt());

            $originalData = [
                'type' => $currentScheduleTask->getType(),
                'schedule' => $currentScheduleTask->schedule->getName(),
                'next_run_at' => $currentScheduleTask->getNextRunAt(),
                'method' => $currentScheduleTask->getMethod(),
            ];

            $this->repo->deleteOrFail($currentScheduleTask);
        }

        $dirtyData = [
            'type' => $entity->getType(),
            'schedule' => $entity->schedule->getName(),
            'next_run_at' => $entity->getNextRunAt(),
            'method' => $entity->getMethod(),
        ];

        $this->app['workflow']
             ->setEntity($entity->getEntity())
             ->handle($originalData, $dirtyData);

        $this->repo->saveOrFail($entity);
    }

    /**
     * Fetch schedule to assign for a new merchant
     *
     * @param Merchant\Entity $merchant
     * @return Schedule\Entity
     */
    protected function getDefaultMerchantSchedule(Merchant\Entity $merchant)
    {
        $schedule = null;

        //
        // For marketplace linked accounts, use the parent merchants
        // schedule, if available
        //
        if ($merchant->isLinkedAccount() === true)
        {
            $parentMerchant = $merchant->parent;

            $scheduleTask = $this->repo
                                 ->schedule_task
                                 ->findByMerchantAndMethod($parentMerchant, null);

            $schedule = $scheduleTask->schedule;
        }

        if ($schedule === null)
        {
            $defaultDelay = Merchant\Entity::SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

            $schedule = (new Schedule\Core)->getOrCreateDefaultSchedule($defaultDelay);
        }

        return $schedule;
    }

    /**
     * Filter a schedule by method or default
     *
     * @param $scheduleTasks
     * @param $method
     *
     * @return null|Entity
     */
    protected function filterAndGetScheduleByMethodOrDefault(
        $scheduleTasks,
        $method)
    {
        $defaultScheduleTask = null;

        foreach ($scheduleTasks as $scheduleTask)
        {
            $scheduleMethod = $scheduleTask->getMethod();

            if ($scheduleMethod === $method)
            {
                return $scheduleTask;
            }
            else if ($scheduleMethod === null)
            {
                $defaultScheduleTask = $scheduleTask;
            }
        }

        return $defaultScheduleTask;
    }

    protected function traceAndNotifyScheduleAssignment($scheduleTask)
    {
        $data = [
            Entity::MERCHANT_ID => $scheduleTask->getMerchantId(),
            Entity::SCHEDULE_ID => $scheduleTask->getScheduleId(),
            Entity::TYPE        => $scheduleTask->getType(),
            Entity::METHOD      => $scheduleTask->getMethod()
        ];

        $this->trace->info(TraceCode::SCHEDULE_ASSIGNED, $data);

        $user = $this->getInternalUsernameOrEmail();

        $this->app['slack']->queue(
                "Schedule assigned to Merchant by $user",
                $data,
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:',
                ]);
    }
}
