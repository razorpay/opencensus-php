<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(int $amount, Entity $txn, string $creditType)
    {
        $timestamp = time();

        //Credits which will expire first will be used first
        $credits = $this->repo->credits->getCreditsSortedWithExpiry(
                        $timestamp, $txn->merchant->getId(), $creditType);


        $creditAmount = $amount;

        foreach ($credits as $credit)
        {
            if ($creditAmount === 0)
            {
                break;
            }

            $availableCredits = $credit->getValue() - $credit->getUsed();

            if ($availableCredits === 0)
            {
                continue;
            }

            $creditTxn = new Entity;

            $creditTxn->transaction()->associate($txn);

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

            $credit->updateUsed($creditsUsed);

            $creditTxn->credits()->associate($credit);

            $creditTxn->updateCreditsUsed($creditsUsed);

            $this->repo->saveOrFail($credit);

            $this->repo->saveOrFail($creditTxn);
        }
    }
}
