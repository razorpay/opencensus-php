<?php

namespace RZP\Models\Credits\Transaction;

class Core extends Base\Core
{

    public function create($amount, $txn, $creditType)
    {
        $timestamp= time();

        $credits = $this->repo->credit->getSortedCredits(
                        $timestamp, $txn->merchant->getId(), $creditType);

        $creditAmount = $amount;

        foreach ($credits as $credit)
        {
            if ($creditAmount === 0)
            {
                break;
            }

            $creditTxn = new Transaction\Entity;

            $creditTxn->transaction()->associate($txn);

            $availableCredits = $credit->getAmount() - $credit->getUsed();

            if ($availableCredits < $creditAmount)
            {
                $creditsUsed = $creditAmount - $availableCredits;

                $creditAmount = $creditAmount - $creditsUsed;
            }
            else
            {
                $creditsUsed = $creditAmount;

                $creditAmount = 0;
            }


            $credit->updateUsed($creditsUsed);

            $creditTxn->credit()->associate($credit);

            $creditTxn->setCreditsUsed($creditsUsed);

            $this->repo->saveOrFail($credit);

            $this->repo->saveOrFail($creditTxn);
        }
    }
}
