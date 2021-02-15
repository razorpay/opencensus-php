<?php

namespace RZP\Models\Schedule\Task;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Constants\Entity as EntityConstant;

class Core extends Base\Core
{
    /**
     * Create a default settlement schedule for merchant
     *
     * @param Merchant\Entity $merchant
     * @throws \Throwable
     */
    public function createDefaultSettlementSchedule(Merchant\Entity $merchant)
    {
        $domesticSchedule = $this->getDefaultMerchantSettlementSchedule($merchant);

        $this->createOrUpdate($merchant, $merchant, [
            Entity::METHOD        => null,
            Entity::TYPE          => Type::SETTLEMENT,
            Entity::SCHEDULE_ID   => $domesticSchedule->getId(),
            Entity::INTERNATIONAL => 0,
        ]);

        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGNED,
            [
                'merchant_id'   => $merchant->getId(),
                'schedule_id'   => $domesticSchedule->getId(),
                'international' => 0,
            ]);

        $internationalSchedule = $this->getDefaultMerchantSettlementSchedule($merchant, true);

        $this->createOrUpdate($merchant, $merchant, [
            Entity::METHOD        => null,
            Entity::TYPE          => Type::SETTLEMENT,
            Entity::SCHEDULE_ID   => $internationalSchedule->getId(),
            Entity::INTERNATIONAL => 1,
        ]);

        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGNED,
            [
                'merchant_id'   => $merchant->getId(),
                'schedule_id'   => $internationalSchedule->getId(),
                'international' => 1,
            ]);
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
     * @throws \Throwable
     */
    public function createOrUpdate(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        $scheduleTask = $this->create($merchant, $entity, $input);

        $this->app['workflow']->setEntityId($merchant->getId());

        $this->repo->transactionOnLiveAndTest(function () use ($scheduleTask)
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
    public function create(Merchant\Entity $merchant, Base\Entity $entity = null, array $input = [])
    {
        $this->trace->info(TraceCode::SCHEDULE_TASK_CREATE_REQUEST, $input);

        $scheduleTask = (new Entity)->build($input);

        $scheduleTask->merchant()->associate($merchant);

        if ($entity !== null)
        {
            $scheduleTask->entity()->associate($entity);
        }

        $scheduleId = $input[Entity::SCHEDULE_ID];

        $schedule = $this->repo->schedule->find($scheduleId);

        $scheduleTask->schedule()->associate($schedule);

        $scheduleTask->updateNextRunAt($scheduleTask->getNextRunAt());

        return $scheduleTask;
    }


    /**
     * Creates merchant schedule entity for Log (Reporting Service)
     * Cannot use the normal create as the `entity_id` doesn't exists in API
     * So the morph relations wont work
     *
     * @param Merchant\Entity $merchant
     * @param                 $input
     *
     * @return Entity
     */
    public function createForExternalService(Merchant\Entity $merchant, array $input = []): Entity
    {
        (new Validator())->validateForExternalServices($input);

        $entityId = $input[Entity::ENTITY_ID];

        $entityType = $input[Entity::ENTITY_TYPE];

        unset($input[Entity::ENTITY_ID]);
        unset($input[Entity::ENTITY_TYPE]);

        $scheduleTask = $this->create($merchant, null, $input);

        $scheduleTask->setEntityId($entityId);

        $scheduleTask->setEntityType($entityType);

        $this->repo->saveOrFail($scheduleTask);

        return $scheduleTask;
    }

    /**
     * Get All Settlement schedules assigned to merchant for payment method
     *
     * @param Merchant\Entity $merchant
     * @param                 $method
     * @param $international
     *
     * @return null|Entity
     */
    public function getMerchantSettlementSchedule(Merchant\Entity $merchant, $method, bool $international = false)
    {
        $scheduleTasks = $this->repo
                              ->schedule_task
                              ->fetchByMerchant(
                                  $merchant,
                                  Type::SETTLEMENT);

        $scheduleTask = $this->filterAndGetScheduleByMethodOrDefault(
                                    $scheduleTasks,
                                    $method,
                                    $international);

        return $scheduleTask;
    }

    /**
     * Get All Settlement schedules assigned to merchant
     *
     * @param Merchant\Entity $merchant
     * @param $international
     *
     * @return null|Entity
     */
    public function getMerchantSettlementScheduleTasks(Merchant\Entity $merchant, bool $international = false)
    {
        $scheduleTasks = $this->repo
                              ->schedule_task
                              ->fetchByMerchantAndInternational(
                                  $merchant,
                                  Type::SETTLEMENT,
                                  $international);

        return $scheduleTasks;
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

        $this->handleChangesForFeeRecoverySchedule($entity, $currentScheduleTask, 'create');

        if ($currentScheduleTask !== null)
        {
            $entity->updateNextRunAt($currentScheduleTask->getNextRunAt());

            $this->handleChangesForFeeRecoverySchedule($entity, $currentScheduleTask, 'update');

            $originalData = [
                Entity::TYPE          => $currentScheduleTask->getType(),
                'schedule'            => $currentScheduleTask->schedule->getName(),
                Entity::NEXT_RUN_AT   => $currentScheduleTask->getNextRunAt(),
                Entity::METHOD        => $currentScheduleTask->getMethod(),
                Entity::INTERNATIONAL => $currentScheduleTask->isInternational(),
            ];

            $this->repo->deleteOrFail($currentScheduleTask);
        }

        $dirtyData = [
            Entity::TYPE                => $entity->getType(),
            'schedule'                  => $entity->schedule->getName(),
            Entity::NEXT_RUN_AT         => $entity->getNextRunAt(),
            Entity::METHOD              => $entity->getMethod(),
            Entity::INTERNATIONAL       => $entity->isInternational(),
        ];

        $this->app['workflow']
             ->setEntity($entity->getEntity())
             ->handle($originalData, $dirtyData);

        $this->repo->saveOrFail($entity);

        // NOTE : This return statement was added for fee recovery schedules. Since we are updating next & last run at
        // for the cloned entity. We want to return the updated values (which are getting saved).
        return $entity;
    }

    public function createOrUpdateForFeeRecovery(Merchant\Entity $merchant, Base\Entity $balance, $input)
    {
        return $this->repo->transaction(function () use ($merchant, $balance, $input)
        {
            $scheduleTask = $this->create($merchant, $balance, $input);

            $this->app['workflow']->setEntityId($merchant->getId());

            return $this->createOrUpdateInMode($scheduleTask, $this->mode);
        });
    }

    /**
     * Fetch schedule to assign for a new merchant
     *
     * @param Merchant\Entity $merchant
     * @param bool            $international
     *
     * @return Schedule\Entity
     */
    protected function getDefaultMerchantSettlementSchedule(Merchant\Entity $merchant, bool $international = false)
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
                                 ->findMerchantSettlementSchedule($parentMerchant, null, $international);

            if ($scheduleTask !== null)
            {
                $schedule = $scheduleTask->schedule;
            }
        }

        if ($schedule === null)
        {
            $defaultDelay = ($international === false)?
                Merchant\Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY :
                Merchant\Entity::INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

            $schedule = (new Schedule\Core)->getOrCreateDefaultSchedule($defaultDelay);
        }

        return $schedule;
    }

    /**
     * Filter a schedule by method or default
     *
     * @param $scheduleTasks
     * @param $method
     * @param $international
     *
     * @return null|Entity
     */
    protected function filterAndGetScheduleByMethodOrDefault(
        $scheduleTasks,
        $method,
        $international = false)
    {
        $defaultScheduleTask = null;

        foreach ($scheduleTasks as $scheduleTask)
        {
            $scheduleMethod = $scheduleTask->getMethod();

            if ($scheduleTask->isInternational() !== $international)
            {
                continue;
            }

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
        $schedule = $scheduleTask->schedule;

        $scheduleType = ucfirst($scheduleTask->getType());

        $scheduleName = $schedule->getName();

        $scheduleId = $schedule->getId();

        $merchantId = $scheduleTask->getMerchantId();

        $method = $scheduleTask->getMethod() ?? "ALL";

        $data = [
            Entity::MERCHANT_ID => $merchantId,
            Entity::SCHEDULE_ID => $scheduleId,
            Entity::TYPE        => $scheduleType,
            Entity::METHOD      => $method,
        ];

        $this->trace->info(TraceCode::SCHEDULE_ASSIGNED, $data);

        $user = $this->getAdminUsername();

        $message = "$scheduleType schedule $scheduleName($scheduleId) assigned to $merchantId for method(s) $method by $user";

        // on sign up we dont need to log to slack, default schedule assignment are logged to slack
        if ($user !== "DASHBOARD_INTERNAL")
        {
            $this->app['slack']->queue(
                $message,
                [],
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:',
                ]);
        }

    }

    /**
     * This Function returns the settlement schedules associated with the settlement Transfer
     * The new function is written because existing method returns default schedule if nothing is assigned
     * But we want T+0, 4pm if nothing is defined
     *
     * @param Merchant\Entity $merchant
     * @return mixed
     */
    public function getSettlementTransferScheduleForMerchant(Merchant\Entity $merchant)
    {
        $scheduleTasks = $this->repo
                              ->schedule_task
                              ->findMerchantSettlementSchedule(
                                            $merchant,
                                            EntityConstant::SETTLEMENT_TRANSFER);
        return $scheduleTasks;
    }

    protected function handleChangesForFeeRecoverySchedule(&$entity, $currentScheduleTask, $action)
    {
        if ($entity->getType() !== Type::FEE_RECOVERY)
        {
            return;
        }

        // Makes sure that last_run_at and next_run_at remain same as earlier
        if ($action === 'update')
        {
            $entity->setNextRunAt($currentScheduleTask->getNextRunAt());
            $entity->setLastRunAt($currentScheduleTask->getLastRunAt());
        }
        // Makes last_run_at as current time and next_run_at as per the schedule
        if ($action === 'create')
        {
            $currentTImeStamp = Carbon::now(Timezone::IST)->getTimestamp();
            $entity->setNextRunAt($currentTImeStamp);

            // This function updates the task based on the schedule. It sets the new last_run_at
            // as the current next_run_at and updates the new next_run_at based on the schedule
            $entity->updateNextRunAndLastRun();
        }
    }
}
