<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use RZP\Models\Base;

class Core extends Base\Core
{

    public function create($amount, $txn, $creditType)
    {
        $timestamp = time();

        $credits = $this->repo->credits->getSortedCredits(
                        $timestamp, $txn->merchant->getId(), $creditType);

        $creditAmount = $amount;

        foreach ($credits as $credit)
        {
            if ($creditAmount === 0)
            {
                break;
            }

            $creditTxn = new Entity;

            $creditTxn->transaction()->associate($txn);

            $availableCredits = $credit->getValue() - $credit->getUsed();

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
