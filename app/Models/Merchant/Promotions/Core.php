<?php

namespace RZP\Models\Merchant\Promotions;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;
use RZP\Models\Schedule\Task;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create($merchant, $promotion)
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

    public function processTasks($scheduleTasks)
    {
        $successIds = [];

        $failedIds = [];

        $successCount = 0;

        foreach ($scheduleTasks as $scheduleTask)
        {
            try
            {
                $merchant = $scheduleTask->merchant;

                $promotion = $scheduleTask->entity;

                $merchantPromotion = $this->repo->merchant_promotion->findByMerchantAndPromotionId(
                    $merchant->getId(), $promotion->getId());

                if ($merchantPromotion->getExpired() === true)
                {
                    continue;
                }

                $this->repo->transaction(function() use ($merchant, $promotion, $merchantPromotion,
                        $scheduleTask)
                {
                    $this->expireCredits($merchant, $promotion);

                    if ($merchantPromotion->getRemainingRuns() > 0)
                    {

                        $this->applyCredits($merchant, $promotion, $scheduleTask);

                        $merchantPromotion->updateRemainingRuns();

                        $scheduleTask->updateNextRunAndLastRun($considerHolidays = false);
                    }
                    else
                    {
                        $merchantPromotion->setExpired();
                    }

                    $this->repo->saveOrFail($merchantPromotion);

                    $this->repo->saveOrFail($scheduleTask);
                });

                $successIds[] = $scheduleTask->getId();

                $successCount++;
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
            'success_count' => $successCount,
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

    public function createScheduleTask($merchant, $promotion)
    {
        $input[Task\Entity::TYPE] = Task\Type::PROMOTION;

        $input[Task\Entity::SCHEDULE_ID] = $promotion->schedule->getId();

        return (new Task\Core)->create($merchant, $promotion, $input);
    }

    public function updateCredits(Entity $merchantPromotion)
    {
        //TODO fill me
    }

    public function applyCredits($merchant, $promotion)
    {
        $creditInput = [
            'campaign'     => $promotion->getName(),
            'promotion_id' => $promotion->getId(),
            'value'        => $promotion->getAmount(),
            'type'         => $promotion->getCreditType(),
        ];

         $scheduleTask = $this->repo->schedule_task->fetchByEntityAndMerchant($promotion, $merchant);

         if ($scheduleTask !== null)
         {
            $creditInput['expiring_at'] = $scheduleTask->getNextRunAt();
         }

        (new Credits\Core)->create($merchant, $creditInput);

        $this->trace->info(
            TraceCode::CREDITS_ADDED,
            [
                'merchant_id'  => $merchant->getId(),
                'credit_input' => $creditInput,
            ]
        );
    }

    public function expireCredits($merchant, $promotion)
    {
        $creditsToExpire = $this->calculateCreditToExpire($merchant, $promotion);

        if ($creditsToExpire === 0)
        {
            return;
        }
        $creditInput = [
            'campaign'     => $promotion->getName() . 'Expired',
            'promotion_id' => $promotion->getId(),
            'value'        => $creditsToExpire * -1,
            'type'         => $promotion->getCreditType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);

        $this->trace->info(
            TraceCode::CREDITS_EXPIRED,
            [
                'merchant_id'  => $merchant->getId(),
                'credit_input' => $creditInput,
            ]
        );
    }

    protected function calculateCreditToExpire($merchant, $promotion)
    {
        $credit = $this->repo->credits->findNonExpiredCredits(
                    $merchant->getId(), $promotion->getId(), time());

        return ($credit->getValue() - $credit->getUsed());
    }
}
