<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;

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
                //Trace Log
            }
        }
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
        $creditLog = $this->repo->credit->findNonExpiredCreditLog(
                    $merchant->getId(), $promotion->getId());

        $newCredits = $this->repo->credit->getNotExpiredNewCredits($creditLog->getCreatedAt(), $creditLog->getType());

        $totalApplicableBalance = $creditLog->getValue();

        foreach ($newCredits as $newCredit)
        {
            $totalApplicableBalance += $newCredit->getValue();
        }

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        if ($creditLog->getType() === Credit\Type::FEE)
        {
            $balanceCredits = $balance->getFeeCredits();
        }
        else
        {
            $balanceCredits = $balance->getAmountCredits();
        }

        $usedCredits = $totalApplicableBalance - $balanceCredits;

        if ($usedCredits > $creditLog->getValue())
        {
            return 0;
        }

        return ($creditLog->getValue() - $usedCredits);
    }
}
