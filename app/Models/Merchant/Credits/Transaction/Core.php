<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Credits;

class Core extends Base\Core
{
    public function create(int $creditAmount, Transaction\Entity $txn, string $creditType)
    {
        $timestamp = time();

        //Credits which will expire first will be used first
        $credits = $this->repo->credits->getCreditsSortedWithExpiry(
                        $timestamp, $txn->merchant->getId(), $creditType);


        //The amount of credits to be decudced will be reflected in the credit log
        //specifying how many credits are used from what log.

        $this->repo->transaction(function() use ($credits, $creditAmount, $txn)
        {
            foreach ($credits as $credit)
            {
                //when all the credit logs are updated with used amount
                if ($creditAmount === 0)
                {
                    break;
                }

                //get number of credits used from particular credit entry
                $creditsUsed = $this->getCreditsUsed($credit, $creditAmount);

                $creditTxn = new Entity;

                $creditTxn->transaction()->associate($txn);

                $credit->updateUsed($creditsUsed);

                $creditTxn->credits()->associate($credit);

                $creditTxn->updateCreditsUsed($creditsUsed);

                $this->repo->saveOrFail($credit);

                $this->repo->saveOrFail($creditTxn);
            }
        });
    }

    protected function getCreditsUsed(Credits\Entity $credit, int & $creditAmount): int
    {
        $availableCredits = $credit->getUnusedCredits();

        $creditsUsed = 0;

        if ($availableCredits < $creditAmount)
        {
            $creditsUsed = $availableCredits;

            $creditAmount = $creditAmount - $creditsUsed;
        }
        else
        {
            $creditsUsed = $creditAmount;

            $creditAmount = 0;
        }

        return $creditsUsed;
    }
}
