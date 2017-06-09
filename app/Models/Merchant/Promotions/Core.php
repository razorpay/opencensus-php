<?php

namespace RZP\Models\Merchant\Promotions;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;
use RZP\Models\Schedule\Task;

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
        }

        $this->repo->saveOrFail($scheduleTask);

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

                $this->expireCredits($merchant, $promotion);

                $merchantPromotion = $this->repo->merchant_promotion->findByMerchantAndPromotionId(
                    $merchant->getId(), $promotion->getId());

               if ($merchantPromotion->getRemainingRuns() > 0)
               {
                    $this->repo->transaction(function() use ($merchant, $promotion, $merchantPromotion,
                        $scheduleTask)
                    {
                        $this->applyCredits($merchant, $promotion, $scheduleTask);

                        $merchantPromotion->updateRemainingRuns();

                        $scheduleTask->updateNextRunAndLastRun($considerHolidays = false);
                    });
               }

               $successIds[] = $scheduleTask->getId();

               $successCount++;
            }
            catch (\Exception $e)
            {
                $failedIds[] = $scheduleTask->getId();
            }
        }

        return [
            'success_ids'   => $successIds,
            'failedIds'     => $failedIds,
            'success_count' => $successCount,
        ];
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
        $scheduleTask = $this->repo->schedule_task->fetchByEntityAndMerchant($promotion, $merchant);

        $creditInput = [
            'expiring_at'  => $scheduleTask->getNextRunAt(),
            'campaign'     => $promotion->getName(),
            'promotion_id' => $promotion->getId(),
            'value'        => $promotion->getAmount(),
            'type'         => $promotion->getCreditType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);
    }

    public function expireCredits($merchant, $promotion)
    {
        $creditInput = [
            'campaign'     => $promotion->getName() . 'Expired',
            'promotion_id' => $promotion->getId(),
            'value'        => $this->calculateCreditToExpire($merchant, $promotion) * -1,
            'type'         => $promotion->getCreditType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);
    }

    protected function calculateCreditToExpire($merchant, $promotion)
    {
        $credit = $this->repo->credits->findNonExpiredCredits(
                    $merchant->getId(), $promotion->getId(), time() + 1*24*60*60);

        return ($credit->getValue() - $credit->getUsed());
    }
}
