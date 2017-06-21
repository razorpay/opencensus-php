<?php

namespace RZP\Models\Merchant\Promotion;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Promotion;
use RZP\Models\Schedule\Task;
use RZP\Models\Merchant\Credits;

class Core extends Base\Core
{
    protected $creditCore;

    public function __construct()
    {
        parent::__construct();

        $this->creditCore = (new Credits\Core);
    }

    public function create(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $input = [
            Entity::REMAINING_RUNS => $promotion->getIterations(),
            Entity::START_TIME     => time(),
        ];

        $merchantPromotion = (new Entity)->build($input);

        $merchantPromotion->merchant()->associate($merchant);

        $merchantPromotion->promotion()->associate($promotion);

        if ($promotion->areCreditsExpirable() === true)
        {
            $scheduleTask = $this->createScheduleTask($merchant, $promotion);

            $scheduleTask->updateNextRunAndLastRun(false);

            $this->repo->saveOrFail($scheduleTask);
        }

        $this->repo->saveOrFail($merchantPromotion);

        return $merchantPromotion;
    }

    protected function addAndExpireCredits(
        $merchant,
        $promotion,
        $merchantPromotion,
        $scheduleTask)
    {
        $this->repo->transaction(
            function() use (
                $merchant,
                $promotion,
                $merchantPromotion,
                $scheduleTask)
            {
                $this->expireCredits($merchant, $promotion);

                if ($merchantPromotion->getRemainingRuns() > 0)
                {
                    $this->applyCredits($merchant, $promotion, $scheduleTask);

                    $merchantPromotion->updateRemainingRuns();

                    $scheduleTask->updateNextRunAndLastRun($considerHolidays = false);

                    $this->repo->saveOrFail($scheduleTask);
                }
                else
                {
                    $merchantPromotion->setExpired();

                    $this->repo->deleteOrFail($scheduleTask);
                }

                $this->repo->saveOrFail($merchantPromotion);
            });
    }

    public function processTasks($scheduleTasks)
    {
        $successIds = [];

        $failedIds = [];

        foreach ($scheduleTasks as $scheduleTask)
        {
            try
            {
                $merchant = $scheduleTask->merchant;

                $promotion = $scheduleTask->entity;

                $merchantPromotion = $this->repo->merchant_promotion
                                          ->findByMerchantAndPromotionId(
                                            $merchant->getId(),
                                            $promotion->getId());

                $this->addAndExpireCredits(
                    $merchant,
                    $promotion,
                    $merchantPromotion,
                    $scheduleTask);

                $successIds[] = $scheduleTask->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $failedIds[] = $scheduleTask->getId();
            }
        }

        $response = [
            'success_ids'   => $successIds,
            'failedIds'     => $failedIds,
        ];

        $this->trace->info(
            TraceCode::SCHEDULE_TASKS_PROCESSED,
            [
                Task\Entity::TYPE => Task\Type::PROMOTION,
                'response'        => $response,
            ]
        );

        return $response;
    }

    public function createScheduleTask(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $input[Task\Entity::TYPE] = Task\Type::PROMOTION;

        $input[Task\Entity::SCHEDULE_ID] = $promotion->schedule->getId();

        $task = (new Task\Core)->create($merchant, $promotion, $input);

        return $task;
    }

    public function applyCredits(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $creditInput = [
            Credits\Entity::CAMPAIGN     => $promotion->getName(),
            Credits\Entity::VALUE        => $promotion->getAmount(),
            Credits\Entity::TYPE         => $promotion->getCreditType(),
        ];

         $scheduleTask = $this->repo->schedule_task->fetchByEntityAndMerchant($promotion, $merchant);

         if ($scheduleTask !== null)
         {
            $creditInput[Credits\Entity::EXPIRING_AT] = $scheduleTask->getNextRunAt();
         }

        $credit = $this->creditCore->create($merchant, $creditInput);

        $credit->promotion()->associate($promotion);

        $this->trace->info(
            TraceCode::CREDITS_ADDED,
            [
                'merchant_id'  => $merchant->getId(),
                'credit_input' => $creditInput,
            ]
        );

        $this->repo->saveOrFail($credit);
    }

    public function expireCredits(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $creditsToExpire = $this->calculateCreditToExpire($merchant, $promotion);

        if ($creditsToExpire !== 0)
        {
            $creditInput = [
                Credits\Entity::CAMPAIGN     => $promotion->getName() . 'Expired',
                Credits\Entity::VALUE        => $creditsToExpire * -1,
                Credits\Entity::TYPE         => $promotion->getCreditType(),
            ];

            $credit = $this->creditCore->create($merchant, $creditInput);

            $credit->promotion()->associate($promotion);

            $this->trace->info(
                TraceCode::CREDITS_EXPIRED,
                [
                    'merchant_id'  => $merchant->getId(),
                    'credit_input' => $creditInput,
                ]
            );

            $this->repo->saveOrFail($credit);
        }
    }

    protected function calculateCreditToExpire(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $credit = $this->repo->credits->findCreditsToExpire(
                    $merchant->getId(), $promotion->getId(), Carbon::now('Asia/Kolkata')->timestamp);

        if ($credit === null)
        {
            return 0;
        }

        return $credit->getUnusedCredits();
    }
}
