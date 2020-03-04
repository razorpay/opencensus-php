<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Credits;

class Core extends Base\Core
{
    const MERCHANT_CREDIT_TYPE_MUTEX_PREFIX = 'merchant_credit_type_';
    const MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT = 30; // seconds
    const MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT = 5;

    public function create(Credits\Entity $credit, Transaction\Entity $txn, string $creditsUsed)
    {
        $creditTxn = new Entity;

        $creditTxn->transaction()->associate($txn);

        $credit->updateUsed($creditsUsed);

        $creditTxn->credits()->associate($credit);

        $creditTxn->updateCreditsUsed($creditsUsed);

        $this->repo->saveOrFail($credit);

        $this->repo->saveOrFail($creditTxn);
    }

    /***
     * @param int $creditAmount
     * @param Transaction\Entity $txn
     * @param string $creditType
     */
    public function createCreditTransaction(int $creditAmount, Transaction\Entity $txn, string $creditType)
    {
        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = self::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $txn->merchant->getId() . '_' . $creditType;

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($txn, $creditAmount, $creditType)
            {
                $currentTimestamp = time();

                // Credits which will expire first will be used first
                $credits = $this->repo->credits->getCreditsSortedByExpiry(
                    $currentTimestamp, $txn->merchant->getId(), $creditType);

                //
                // The amount of credits to be deducted will be reflected in the credit log
                // specifying how many credits are used from what log.
                //
                $this->repo->transaction(function() use ($credits, $creditAmount, $txn)
                {
                    foreach ($credits as $credit)
                    {
                        // When all the credit logs are updated with used amount
                        if ($creditAmount === 0)
                        {
                            break;
                        }

                        // Get number of credits used from particular credit entry
                        $creditsUsed = $this->getCreditsUsedAndUpdateCreditAmount($credit, $creditAmount);

                        $this->create($credit, $txn, $creditsUsed);
                    }
                });
            },
            self::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            self::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
        );
    }

    /**
     * Should be used only for Credit types with `expired_at` = NULL
     * else might result in crediting back to an expired credit entity
     * Using reversals currently only for refunds and we never expire refund credits
     *
     * @param int $creditAmount
     * @param Transaction\Entity $txn
     * @param string $forwardTxnId
     * @param string $creditType
     * @throws Exception\LogicException
     */
    public function createCreditReversalTransaction(
        int $creditAmount, Transaction\Entity $txn, string $forwardTxnId, string $creditType)
    {
        if ($creditAmount >= 0)
        {
            throw new Exception\LogicException('Credit Amount should be negative in reversal cases');
        }

        $creditAmount = -1 * $creditAmount;

        // credit_transactions used in the forward transaction in reverse order
        $creditTransactions = $this->repo->credit_transaction->getAllCreditLogsOfTransaction($forwardTxnId);

        $creditIds = $creditTransactions->pluck(Entity::CREDITS_ID)
                                        ->toArray();

        $creditsUsed = $creditTransactions->pluck(Entity::CREDITS_USED)
                                          ->toArray();

        $creditsToReverse = [];

        foreach ($creditIds as $key => $creditId)
        {
            // When all the credit logs are reversed with used amount/fee
            if ($creditAmount === 0)
            {
                break;
            }

            $toReverse = min($creditAmount, $creditsUsed[$key]);

            $creditAmount -= $toReverse;

            $creditsToReverse[$creditId] = -1 * $toReverse;
        }

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = self::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $txn->merchant->getId() . '_' . $creditType;

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($txn, $creditsToReverse)
            {
                $credits = $this->repo->credits->getCreditEntities(array_keys($creditsToReverse));

                //
                // The amount of credits to be deducted will be reflected in the credit log
                // specifying how many credits are used from what log.
                //
                $this->repo->transaction(function() use ($credits, $creditsToReverse, $txn)
                {
                    foreach ($credits as $credit)
                    {
                        $this->create($credit, $txn, $creditsToReverse[$credit->getId()]);
                    }
                });
            },
            self::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            self::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
        );
    }

    protected function getCreditsUsedAndUpdateCreditAmount(Credits\Entity $credit, int & $creditAmount): int
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
