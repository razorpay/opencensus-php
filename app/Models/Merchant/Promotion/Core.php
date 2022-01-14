<?php

namespace RZP\Models\Merchant\Promotion;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Promotion;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Task;
use RZP\Models\Merchant\Credits;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $creditCore;

    public function __construct()
    {
        parent::__construct();

        $this->creditCore = (new Credits\Core);
    }

    public function create(Merchant\Entity $merchant, Promotion\Entity $promotion): Entity
    {
        $input = [
            Entity::REMAINING_ITERATIONS => $promotion->getIterations(),
            Entity::START_TIME           => time(),
        ];

        $merchantPromotion = (new Entity)->build($input);

        $merchantPromotion->merchant()->associate($merchant);

        $merchantPromotion->promotion()->associate($promotion);

        $this->repo->saveOrFail($merchantPromotion);

        return $merchantPromotion;
    }

    public function processTasks($scheduleTasks, $timestamp): array
    {
        $successIds = [];

        $failedIds = [];

        foreach ($scheduleTasks as $scheduleTask)
        {
            try
            {
                $merchant = $scheduleTask->merchant;

                $promotion = $scheduleTask->entity;

                $merchantPromotion = $this->repo
                                          ->merchant_promotion
                                          ->findByMerchantAndPromotionId(
                                                $merchant->getId(),
                                                $promotion->getId());

                $this->addAndExpireCredits(
                    $merchant,
                    $promotion,
                    $merchantPromotion,
                    $scheduleTask,
                    $timestamp);

                $successIds[] = $scheduleTask->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SCHEDULE_TASK_PROCESSING_FAILED,
                    [$scheduleTask->getId()]);

                $failedIds[] = $scheduleTask->getId();
            }
        }

        $response = [
            'success_ids'   => $successIds,
            'failed_ids'    => $failedIds,
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

    protected function addAndExpireCredits(
        Merchant\Entity $merchant,
        Promotion\Entity $promotion,
        Entity $merchantPromotion,
        Task\Entity $scheduleTask,
        int $timestamp)
    {
        $this->repo->transaction(
            function() use (
                $merchant,
                $promotion,
                $merchantPromotion,
                $scheduleTask,
                $timestamp)
            {
                $this->expireCredits($merchant, $promotion, $timestamp);

                if ($merchantPromotion->getRemainingIterations() > 0)
                {
                    $scheduleTask->updateNextRunAndLastRun();

                    $this->repo->saveOrFail($scheduleTask);

                    $this->applyCredits($merchant, $promotion);

                    $merchantPromotion->decrementRemainingIterations();
                }
                else
                {
                    $merchantPromotion->setExpired();

                    $this->repo->deleteOrFail($scheduleTask);
                }

                $this->repo->saveOrFail($merchantPromotion);
            });
    }

    protected function createScheduleTask(Merchant\Entity $merchant, Promotion\Entity $promotion): Task\Entity
    {
        $input[Task\Entity::TYPE] = Task\Type::PROMOTION;

        $input[Task\Entity::SCHEDULE_ID] = $promotion->schedule->getId();

        //
        // merchant's signup timestamp
        // Here the merchants creation time is considered as NEXT_RUN_AT and not current time or Activation time
        // Credits are given when merchant signups and not when merchant activates.
        // Need to revisit on this as merchant's signup time is not considered to calculate next_run_at.
        //
        $input[Task\Entity::NEXT_RUN_AT] = Carbon::now(Timezone::IST)->getTimestamp();

        $task = (new Task\Core)->create($merchant, $promotion, $input);

        return $task;
    }

    public function activate(Entity $merchantPromotion)
    {
        $this->repo->transaction(function() use ($merchantPromotion)
        {
            $merchant = $merchantPromotion->merchant;

            $promotion = $merchantPromotion->promotion;

            $credits = (new Credits\Core)->checkIfCreditsAlreadyAppliedForBankingPromotion($promotion, $merchant);

            if ($credits !== null)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_PROMOTION_CREDITS_ALREADY_ASSIGNED,
                    [
                        'merchant_id'  => $merchantPromotion->merchant->getId(),
                        'promotion_id' => $merchantPromotion->promotion->getId(),
                        'credit_id'    => $credits->getId(),
                    ]);

                return;
            }

            if ($promotion->doCreditsExpire() === true)
            {
                $scheduleTask = $this->createScheduleTask($merchant, $promotion);

                $scheduleTask->updateNextRunAndLastRun();

                $this->repo->saveOrFail($scheduleTask);
            }

            $this->applyCredits($merchant, $promotion);

            $merchantPromotion->decrementRemainingIterations();

            $this->repo->saveOrFail($merchantPromotion);

            $this->trace->info(
                TraceCode::MERCHANT_PROMOTION_ACTIVATED,
                [
                    'merchant_id'  => $merchantPromotion->merchant->getId(),
                    'promotion_id' => $merchantPromotion->promotion->getId(),
                ]);
        });
    }

    public function applyCredits(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $creditInput = [
            Credits\Entity::CAMPAIGN     => $promotion->getName(),
            Credits\Entity::VALUE        => $promotion->getCreditAmount(),
            Credits\Entity::TYPE         => $promotion->getCreditType(),
        ];

         $scheduleTask = $this->repo->schedule_task->fetchByEntityAndMerchant($promotion, $merchant);

         if ($scheduleTask !== null)
         {
            $creditInput[Credits\Entity::EXPIRED_AT] = $scheduleTask->getNextRunAt();
         }

        // for banking promotions, merchant will be having a single balance
        // corresponding to all his/her accounts. For PG this balance is tied
        // to primary balance of the merchant.
        if ($promotion->getProduct() === Promotion\Entity::BANKING)
        {
            $creditInput[Credits\Entity::PRODUCT] = Promotion\Entity::BANKING;

            $credit = $this->creditCore->assignCreditsToMerchant($creditInput, $merchant);

            $credit->promotion()->associate($promotion);

            (new Notify)->notifyCreditViaEmail($credit);
        }
        else
        {
            $credit = $this->creditCore->create($merchant, $creditInput);

            $credit->promotion()->associate($promotion);
        }

        $this->trace->info(
            TraceCode::CREDITS_ADDED,
            [
                'merchant_id'  => $merchant->getId(),
                'credit_input' => $creditInput,
                'promotion_id' => $promotion->getId(),
            ]
        );

        $this->repo->saveOrFail($credit);

        return $credit;
    }

    protected function expireCredits(Merchant\Entity $merchant, Promotion\Entity $promotion, int $timestamp)
    {
        $creditsToExpire = $this->calculateCreditToExpire($merchant, $promotion, $timestamp);

        $this->expireCreditsUtility($creditsToExpire, $merchant, $promotion);
    }

    protected function calculateCreditToExpire(
        Merchant\Entity $merchant,
        Promotion\Entity $promotion,
        int $timestamp): int
    {
        $credit = $this->repo->credits->findCreditsToExpire(
                    $merchant->getId(), $promotion->getId(), $timestamp);

        if ($credit === null)
        {
            return 0;
        }

        return $credit->getUnusedCredits();
    }

    protected function expireCreditsUtility($creditsToExpire, Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        if ($creditsToExpire > 0)
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
                    'promotion_id' => $promotion->getId(),
                ]
            );

            $this->repo->saveOrFail($credit);
        }
    }

    public function forceExpireCredits(Merchant\Entity $merchant, $creditInput)
    {
        $credit = $this->creditCore->create($merchant, $creditInput);

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
