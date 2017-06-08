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
            $this->createScheduleTask($merchant, $promotion);
        }

        $this->repo->saveOrFail($merchantPromotion);

        return $merchantPromotion;
    }

    public function processTasks($scheduleTaks)
    {
        foreach ($scheduleTaks as $scheduleTask)
        {
            try
            {
                $merchant = $scheduleTask->merchant;

                $promotion = $scheduleTask->entity;

                $merchantPromotion = $this->repo->findByMerchantAndPromotionId(
                    $merchant->getId(), $promotion->getId());

                $this->expireCredits($merchant, $promotion);

               if ($merchantPromotion->getRemainingRuns() > 0)
               {
                    $this->repo->transaction(function() use ($merchant, $promotion, $merchantPromotion)
                    {
                        $this->applyCredits($merchant, $promotion);

                        $merchantPromotion->updateRemainingRuns();

                        $scheduleTask->updateNextRunAndLastRun($considerHolidays = false);
                    });
               }
            }
            catch (\Exception $e)
            {
                //Trace Log fill me
            }
        }
    }

    public function createScheduleTask($merchant, $promotion)
    {
        $input[Task\Entity::TYPE] = Task\Type::PROMOTION;

        $input[Task\Entity::SCHEDULE_ID] = $promotion->schedule->getId();

        (new Task\Core)->create($merchant, $promotion, $input);
    }

    public function updateCredits(Entity $merchantPromotion)
    {
        //TODO fill me
    }

    public function applyCredits($merchant, $promotion)
    {
        $creditInput = [
            'campaign' => $promotion->getName(),
            'value'    => $promotion->getAmount(),
            'type'     => $promotion->getCreditType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);
    }

    public function expireCredits($merchant, $promotion)
    {
        $creditInput = [
            'campaign' => $promotion->getName(),
            'value'    => $this->calculateCreditToExpire() * -1,
            'type'     => $promotion->getCreditType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);
    }

    protected function calculateCreditToExpire($merchant, $promotion)
    {
        $credit = $this->repo->credit->findNonExpiredCredits(
                    $merchant->getId(), $promotion->getId(), time());

        return ($credit->getAmount() - $credit->getUsed());
    }
}
