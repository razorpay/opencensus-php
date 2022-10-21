<?php

namespace RZP\Models\PaymentLink\CustomDomain\Plans;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Tests\Functional\Payment\ConstantsStub;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;
use Symfony\Component\VarDumper\Caster\ConstStub;

class Core extends Base\Core
{
    public function create(array $planInput): Schedule\Entity
    {
        $this->trace->info(TraceCode::CDS_PRICING_PLAN_CREATE_STARTED, [
            Constants::ALIAS     => $planInput[Constants::ALIAS]
        ]);

        (new Validator)->validateInput('create', $planInput);

        // create schedule
        $plan = (new Schedule\Core)->createSchedule([
            Schedule\Entity::NAME        => $planInput[Constants::ALIAS],
            Schedule\Entity::PERIOD      => $planInput[Constants::PERIOD],
            Schedule\Entity::INTERVAL    => $planInput[Constants::INTERVAL],
            Schedule\Entity::TYPE        => Schedule\Type::CDS_PRICING,
        ]);

        $this->trace->info(TraceCode::CDS_PRICING_PLAN_CREATE_COMPLETED, [
            Constants::ALIAS     => $planInput[Constants::ALIAS]
        ]);

        return $plan;
    }

    public function createMany(array $input):array
    {
        $plans = $input[Constants::PLANS];

        $this->trace->info(TraceCode::CDS_PRICING_PLAN_CREATE_MANY);

        $response = [];

        foreach ($plans as $planInput)
        {
            $plan = $this->create($planInput);

            array_push($response, $plan);
        }

        return [Constants::PLANS => $plans] ;
    }

    public function fetchPlans()
    {
        $schedules = $this->getSchedules();

        $aliasSequence = Plans::PLAN_ALIAS_SEQUENCE;

        asort($aliasSequence);

        $planList = [];

        foreach ($aliasSequence as $alias => $sequenceNumber)
        {
            /**
             * If we have created the plan in the api code base
             * and have not created it in the schedules table by
             * hitting the api, we will not show the plan
             * details in the fetch call.
             */
            if(array_key_exists($alias, $schedules) === false)
            {
                continue;
            }

            $planMetadata = (new Plans())->getByAlias($alias);

            $schedule =  $schedules[$alias];

            $schedule->setAnchor(Schedule\Anchor::getAnchor($schedule->getPeriod(), Carbon::now(\RZP\Constants\Timezone::IST)));

            $plan = $this->generatePlanDetails($planMetadata, $schedules[$alias]);

            $planList[] = $plan;
        }

        return [Constants::PLANS => $planList];
    }

    public function createOrUpdatePlanForMerchant(array $input)
    {
        $this->trace->info(TraceCode::CDS_PLAN_CREATE_INITIATED, [
            Constants::INPUT => $input
        ]);

        $planId = array_get($input, Constants::PLAN_ID);

        (new Validator())->validatePlanCreation($planId, $this->merchant);

        $plan = (new Schedule\Entity())->FindOrFail($planId);

        $currTimestamp = Carbon::now()->getTimestamp();

        $planForMerchant = $this->getActivePlanForMerchant();

        if($planForMerchant === null)
        {
            $planForMerchant = (new Schedule\Task\Core)->create($this->merchant, $this->merchant, [
                Schedule\Task\Entity::TYPE => Schedule\Task\Type::CDS_PRICING,
                Schedule\Task\Entity::SCHEDULE_ID => $plan->getId(),
                Schedule\Task\Entity::NEXT_RUN_AT => $this->getNextBillingDate($plan, $currTimestamp)
            ]);
        }
        else
        {
            $planForMerchant->restore();
        }

        $planForMerchant->saveOrFail();

        $this->trace->info(TraceCode::CDS_PLAN_CREATE_COMPLETE, $planForMerchant->toArrayPublic());

        return $planForMerchant;
    }

    public function deletePlans(array $input): array
    {
        (new Validator())->validateInput('delete', $input);

        $planIds = array_get($input, Constants::PLAN_IDS) ?? [];

        $successfulDeletions = [];

        $failedDeletions = [];

        foreach ($planIds as $planId)
        {
            try
            {
                (new Schedule\Service())->deleteSchedule($planId);

                $successfulDeletions[] = $planId;

                $this->trace->info(TraceCode::CDS_PLAN_DELETION_SUCCESSFUL, [
                    Constants::PLAN_ID      => $planId
                ]);
            }
            catch(\Exception $e)
            {
                $failedDeletions[] = $planId;

                $this->trace->info(TraceCode::CDS_PLAN_DELETION_FAILED, [
                    Constants::PLAN_ID      => $planId,
                    'exception'             => $e
                ]);
            }
        }

        return  [
            Constants::TOTAL_PLANS     => count($planIds),
            Constants::SUCCESSFUL      => $successfulDeletions,
            Constants::FAILED          => $failedDeletions
        ];
    }

    public function fetchPlanForMerchant(): array
    {
        $plan = $this->getActivePlanForMerchant();

        $response = $plan === null ? [] : $this->generatePlanDetails(
            Plans::getByAlias($plan->schedule->getName()),
            $plan->schedule
        );

        return [Constants::PLAN => $response];
    }

    public function deletePlanForMerchant()
    {
        $plan = $this->getActivePlanForMerchant();

        if($plan === null)
        {
            throw new BadRequestValidationFailureException("Plan Does not exist for this merchant");
        }

        $response = $this->repo->schedule_task->deleteOrFail($plan);

        return $response;
    }

    /**
     * @param string $oldPlanId
     * @param string $newPlanId
     * @return void
     *
     * This Function will update all the entries in the schedule_tasks
     * table which belongs to type: cds_pricing and will have old plan
     * id.
     * Use with caution
     */
    public function updatePlansForMerchants(string $oldPlanId, string $newPlanId)
    {
        $this->trace->info(TraceCode::CDS_PLAN_UPDATE_INITIATED,[
            Constants::NEW_PLAN_ID  => $newPlanId,
            Constants::OLD_PLAN_ID  => $oldPlanId
        ]);

        (new Validator())->validatePlansUpdateForMerchant($oldPlanId, $newPlanId);

        // updating the schedule id of only those plans who are active
        $this->repo->schedule_task->updateScheduleId($oldPlanId, $newPlanId, true);

        $this->trace->info(TraceCode::CDS_PLAN_UPDATE_SUCCESSFUL,[
            Constants::NEW_PLAN_ID  => $newPlanId,
            Constants::OLD_PLAN_ID  => $oldPlanId
        ]);
    }

    public function cdsPlansBillingDateUpdate()
    {
        $startOfDay = Carbon::now()->startOfDay()->subDay(1)->getTimestamp();

        $endOfDay = Carbon::now()->endOfDay()->subDay(1)->getTimestamp();

        // Plans who have next_run_at_today
        $plansToday = $this->repo->schedule_task->getScheduleTasksToRun(
            Schedule\Task\Type::CDS_PRICING,
            $startOfDay,
            $endOfDay
        );

        foreach ($plansToday as $plan)
        {
            try
            {
                $oldNextRunAt = $plan->getNextRunAt();

                $newBillingDate = $this->getNextBillingDate($plan->schedule, $oldNextRunAt);

                $plan->setNextRunAt($newBillingDate);

                $plan->setLastRunAt($oldNextRunAt);

                $plan->saveOrFail();

                $this->trace->info(TraceCode::CDS_PLAN_NEXT_BILLING_AT_UPDATED, [
                    Constants::PLAN    => $plan->getId(),
                    Constants::NEW_NEXT_RUN_AT => $plan->getNextRunAt(),
                    Constants::OLD_NEXT_RUN_AT => $plan->getLastRunAt()
                ]);
            }
            catch(\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::CDS_PLAN_NEXT_BILLING_AT_UPDATE_FAILED,
                    [
                        Constants::PLAN    => $plan->getId(),
                    ])
                ;
            }
        }
    }

    protected function getActivePlanForMerchant()
    {
        return $this->repo->schedule_task->fetchActiveByMerchantWithTrashed(
            $this->merchant,
            Schedule\Task\Type::CDS_PRICING,
            true
        );
    }

    protected function generatePlanDetails(array $planMetadata, Schedule\Entity $schedule)
    {
        $currTimestamp = Carbon::now()->getTimestamp();

        $nextBillingDate = $this->getNextBillingDate($schedule, $currTimestamp);

        return [
            Schedule\Entity::ID             => $schedule->getId(),
            Constants::ALIAS                => $schedule->getName(),
            Constants::NAME                 => $planMetadata[Constants::NAME],
            Schedule\Entity::PERIOD         => $schedule->getPeriod(),
            Schedule\Entity::INTERVAL       => $schedule->getInterval(),
            Constants::NEXT_BILLING_AT      => Carbon::createFromTimestamp( $nextBillingDate)->format('j M, Y'),
            Constants::METADATA             => $planMetadata[Constants::METADATA]
        ];
    }

    protected function getSchedules()
    {
        $schedules = $this->repo->schedule->fetchSchedulesByType(Schedule\Type::CDS_PRICING);

        $nameMapOfSchedules = [];

        foreach($schedules as $schedule)
        {
            $nameMapOfSchedules[$schedule->getName()] = $schedule;
        }

        return $nameMapOfSchedules;
    }

    public function getNextBillingDate(Schedule\Entity $schedule, string $timestamp): string
    {
        $scheduleClone = clone $schedule;

        $currTime = Carbon::createFromTimestamp($timestamp, \RZP\Constants\Timezone::IST);

        $scheduleClone->setAnchor(Schedule\Anchor::getAnchor($schedule->getPeriod(), $currTime));

        $timestamp = Schedule\Library::computeFutureRun($scheduleClone, $currTime);

        return $timestamp->getTimestamp();
    }
}
