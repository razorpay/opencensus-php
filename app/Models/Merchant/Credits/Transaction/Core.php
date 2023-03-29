<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use Carbon\Carbon;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Credits;

class Core extends Base\Core
{
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

    public function createTransactionForSource(Credits\Entity $credit, Base\PublicEntity $source, string $creditsUsed)
    {
        $this->trace->info(TraceCode::CREDITS_TRANSACTION_CREATE_REQUEST,
            [
                'credit_id'     => $credit->getId(),
                'source_id'     => $source->getId(),
                'credits_used'  => $creditsUsed,
            ]);

        $creditTxn = new Entity;

        $creditTxn->entity()->associate($source);

        $credit->updateUsed($creditsUsed);

        $creditTxn->credits()->associate($credit);

        $creditTxn->updateCreditsUsed($creditsUsed);

        $this->repo->saveOrFail($credit);

        $this->repo->saveOrFail($creditTxn);

        $this->trace->info(TraceCode::CREDITS_TRANSACTION_CREATED,
            [
                'credit_id'         => $credit->getId(),
                'source_id'         => $source->getId(),
                'credits_used'      => $creditsUsed,
                'credits_txn_id'    => $creditTxn->getId(),
            ]);
    }

    /***
     * @param int $creditAmount
     * @param Transaction\Entity $txn
     * @param string $creditType
     */
    public function createCreditTransaction(int $creditAmount, Transaction\Entity $txn, string $creditType)
    {
        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $txn->merchant->getId() . '_' . $creditType;

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($txn, $creditAmount, $creditType)
            {
                $currentTimestamp = time();

                // Credits which will expire first will be used first
                $credits = $this->repo->credits->getCreditsSortedByExpiry(
                    $currentTimestamp, $txn->merchant, $creditType);

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
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
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

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $txn->merchant->getId() . '_' . $creditType;

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($txn, $creditAmount, $forwardTxnId)
            {
                $creditsToReverse = $this->getCreditsToBeReversed($creditAmount, $forwardTxnId);

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
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
        );
    }

    protected function getCreditsToBeReversed(int $creditAmount, string $forwardTxnId)
    {
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
        return $creditsToReverse;
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

    protected function getCreditsUsed(Credits\Entity $credit, int $amountToConsume): int
    {
        $availableCredits = $credit->getUnusedCredits();

        $creditsUsed = ($availableCredits < $amountToConsume) ? $availableCredits : $amountToConsume;

        return $creditsUsed;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string $creditType
     * @param string $product
     * @param $amount
     * @param Base\PublicEntity $source
     * We will first get all the credit balances of a merchant for a type and product
     * which are not expired yet and have some balance amount set.
     * There is only one balance as of now which does not have expiry. The code takes
     * care of handling balances by consuming them in the order of expiry. But if
     * any specific order has to be used for consuming credits, please change this
     * method
     * Now each balance, we get the credits where value > used and which have not
     * expired yet. We go through each credit and see if we can consume it and
     * make credit txn entry for the same.
     * We need to take lock on balance at all the time and a lock on credit
     * record while updating it.
     */
    public function subtractMerchantCreditBalanceAndCreateTransactions(
                                                               Merchant\Entity $merchant,
                                                               string $creditType,
                                                               string $product,
                                                               $amount,
                                                               Base\PublicEntity $source)
    {
        $amount = $amountInPoints = (new Credits\Core)->getCreditInPoints($amount);

        $creditsAvailable = $this->repo->credits->getTypeAggregatedMerchantCreditsForProduct(
                                                            $merchant->getId(),
                                                            Credits\Balance\Product::BANKING);

        $creditsAvailableValue = $creditsAvailable[$creditType] ?? 0;

        if (($amountInPoints <= $creditsAvailableValue) and
             ($amountInPoints !== 0))
        {
            $credits = $this->repo->credits->getCreditsSortedByExpiryForProduct(time(), $merchant->getId(), $creditType, $product);

            $this->repo->transaction(function() use ($credits, $amount, $source)
            {
                foreach ($credits as $credit)
                {
                    if ($amount === 0)
                    {
                        break;
                    }

                    $this->repo->credits->getCreditLockForUpdate($credit);

                    $creditsUsed = $this->getCreditsUsedAndUpdateCreditAmount($credit, $amount);

                    $this->createTransactionForSource($credit, $source, $creditsUsed);

                    $balance = $credit->balance;

                    $balance->decrementBalance($creditsUsed);
                }
            });
        }
    }

    public function checkIfCreditTransactionsExistForSource($sourceId, $sourceType)
    {
        $creditTxns = $this->repo->credit_transaction->getCreditTransactionsForSource($sourceId, $sourceType);

        if ($creditTxns->count() > 0)
        {
            return true;
        }

        return false;
    }

    /*
     * This method returns true if credits are deducted for source.
     * We fetch latest entry from credit transaction table and check if its a positive entry or not.
     * There are possibility of credits deduction & reversal in case of queued payouts so we check on latest entry.
     */
    public function checkIfCreditsDeductedForSource($sourceId, $sourceType)
    {
        $creditTxn = $this->repo->credit_transaction->getLatestCreditTransactionsForSource($sourceId, $sourceType);

        if (isset($creditTxn) === true)
        {
            if ($creditTxn->getCreditsUsed() > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getReverseCreditTransactionsForSource($sourceId, $sourceType)
    {
        $creditTxns = $this->repo->credit_transaction->getReverseCreditTransactionsForSource($sourceId, $sourceType);

        return $creditTxns;
    }

    public function reverseCreditTransactionsForSource(
        string $sourceId,
        string $sourceType,
        Base\PublicEntity $forwardSource)
    {
        $creditTransactions = $this->repo->credit_transaction->getCreditTransactionsForSource($sourceId, $sourceType);

        $creditIds = $creditTransactions->pluck(Entity::CREDITS_ID)
                                        ->toArray();

        $creditsUsed = $creditTransactions->pluck(Entity::CREDITS_USED)
                                          ->toArray();

        $creditsToReverse = [];

        foreach ($creditIds as $key => $creditId)
        {
            $toReverse = $creditsUsed[$key];

            $creditsToReverse[$creditId] = -1 * $toReverse;
        }

        $credits = $this->repo->credits->getCreditEntities(array_keys($creditsToReverse));

        foreach ($credits as $credit)
        {
            $currentTimestamp = Carbon::now()->getTimestamp();

            // this will fail reversal creation. Logic to handle reversals needs
            // to be thought properly. We can add a new type of credit like
            // reward_fee_refund credit but then there things like order in
            // which credits should be consumed will also be required. For
            // now not allowing expired credits to get reversed.
            if (($credit->getExpiredAt() !== null) and
                ($credit->getExpiredAt() <= $currentTimestamp))
            {
                throw new Exception\LogicException(
                    'Expired Credit cannot be reversed',
                    null,
                    [
                        'credit_id' => $credit->getId(),
                    ]);
            }

            $balance = $credit->balance ;

            $this->repo->credits->getCreditLockForUpdate($credit);

            $creditsToBeReversed = $creditsToReverse[$credit->getId()];

            $this->createTransactionForSource($credit, $forwardSource, $creditsToBeReversed);

            $balance->incrementBalance(-1 * $creditsToBeReversed);
        }
    }

    public function reverseCreditsForSource(
        string $sourceId,
        string $sourceType,
        Base\PublicEntity $forwardSource)
    {
        $this->trace->info(TraceCode::CREDITS_REVERSE_REQUEST,
            [
                'source_id'         => $sourceId,
                'source_type'       => $sourceType,
                'forward_source_id' => $forwardSource->getId(),
            ]);

        $creditTransactions = $this->repo->credit_transaction->getCreditTransactionsForSource($sourceId, $sourceType);

        $creditIds = $creditTransactions->pluck(Entity::CREDITS_ID)
            ->toArray();

        $creditsUsed = $creditTransactions->pluck(Entity::CREDITS_USED)
            ->toArray();

        $creditsToReverse = [];

        // The below loop will create credit Id and amount array
        // This will be used to bulk fetch credits and then later
        // get amount to be reversed for each credit
        foreach ($creditIds as $key => $creditId)
        {
            $creditAmountToReverse = $creditsUsed[$key];

            $creditsToReverse[$creditId] = -1 * $creditAmountToReverse;
        }

        // There is an outer db transaction which ensures that credits
        // and credits transactions are created in sync with payouts
        // The order of locking is  - lock on credits and then lock
        // on merchant banking balance
        $credits = $this->repo->credits->getCreditEntitiesLockForUpdate(array_keys($creditsToReverse));

        foreach ($credits as $credit)
        {
            $currentTimestamp = Carbon::now()->getTimestamp();

            // Logic to handle reversals needs to be carefully
            // thought in case of credits having expiry
            if (($credit->getExpiredAt() !== null) and
                ($credit->getExpiredAt() <= $currentTimestamp))
            {
                throw new Exception\LogicException(
                    'Expired Credit cannot be reversed',
                    null,
                    [
                        'credit_id' => $credit->getId(),
                    ]);
            }

            $creditsToBeReversed = $creditsToReverse[$credit->getId()];

            $this->trace->info(TraceCode::CREDITS_TO_BE_REVERSED,
                [
                    'credit_to_be_reversed' => $creditsToBeReversed,
                    'source_id'             => $sourceId,
                    'source_type'           => $sourceType,
                ]);

            $this->createTransactionForSource($credit, $forwardSource, $creditsToBeReversed);
        }
    }

    public function getCreditsForSource(Base\PublicEntity $source)
    {
        $creditsUsed = $this->repo->credit_transaction->getSumOfCreditTransactionsForSource($source->getId());

        return $creditsUsed;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string $creditType
     * @param string $product
     * @param $amount
     * @param Base\PublicEntity $source
     * We will check if the merchant has credits available, if not we will
     * return from there. If yes, we will lock all the credits and reload
     * the credits after locking.
     */
    public function subtractAndGetMerchantCreditsConsumed(
        Merchant\Entity $merchant,
        string $creditType,
        string $product,
        $amount,
        Base\PublicEntity $source)
    {
        $creditsConsumed = 0;

        if ($amount === 0)
        {
            $this->trace->info(TraceCode::CREDITS_AMOUNT_TO_BE_CONSUMED_IS_ZERO,
                [
                    'credit_type' => $creditType,
                    'product'     => $product,
                    'amount'      => $amount,
                    'source_id'   => $source->getId(),
                ]);

            return $creditsConsumed;
        }

        $this->trace->info(TraceCode::CREDITS_CONSUMPTION_REQUEST,
            [
                'credit_type' => $creditType,
                'product'     => $product,
                'amount'      => $amount,
                'source_id'   => $source->getId(),
            ]);

        $credits = $this->repo->credits->getCreditsForMerchant($merchant->getId(), $product, $creditType);

        $validCreditIds = [];

        // sum of available credits before acquiring lock is calculated to avoid
        //    -> waiting for acquiring locks without sufficient credits
        //    -> avoid unnecessary acquisition of locks without sufficient credits
        //the total available credit sum is once again calculated after acquiring locks to ensure
        //that no concurrent request consumed the available credits
        $creditsAvailableBeforeLockAcquisition = 0;

        foreach ($credits as $credit)
        {
            if ($credit->isValid() === true)
            {
                $unusedCredit = $credit->getValue() - $credit->getUsed();

                $creditsAvailableBeforeLockAcquisition += $unusedCredit;

                // here the condition is not equal to zero because there can be negative credits as well.
                if($unusedCredit !== 0)
                {
                    array_push($validCreditIds, $credit->getId());
                }
            }
        }

        if ($amount > $creditsAvailableBeforeLockAcquisition)
        {
            $this->trace->info(TraceCode::CREDITS_TO_BE_CONSUMED_MORE_THAN_MERCHANT_CREDITS,
                [
                    'credit_type' => $creditType,
                    'product'     => $product,
                    'amount'      => $amount,
                    'source_id'   => $source->getId(),
                ]);

            return $creditsConsumed;
        }

        // There is an outer db transaction which ensures that credits
        // and credits transactions are created in sync with payouts
        // The order of locking is  - lock on credits and then lock
        // on merchant banking balance
        $credits = $this->repo->credits->getCreditEntitiesLockForUpdate($validCreditIds);

        $creditsAvailable = 0;

        foreach ($credits as $credit)
        {
            $creditsAvailable += $credit->getValue() - $credit->getUsed();
        }

        $this->trace->info(TraceCode::CREDITS_AVAILABLE,
            [
                'credits_available' => $creditsAvailable,
                'product'           => $product,
                'amount'            => $amount,
                'source_id'         => $source->getId(),
            ]);

        if ($amount > $creditsAvailable)
        {
            $this->trace->info(TraceCode::CREDITS_TO_BE_CONSUMED_MORE_THAN_MERCHANT_CREDITS,
                [
                    'credit_type' => $creditType,
                    'product'     => $product,
                    'amount'      => $amount,
                    'source_id'   => $source->getId(),
                ]);

            return $creditsConsumed;
        }

        foreach ($credits as $credit)
        {
            if ($amount === 0)
            {
                break;
            }

            //check to only consume credits from rows having positive balance not from rows created as a part of data fix.
            if ($credit->getValue() - $credit->getUsed() > 0)
            {
                $creditsUsed = $this->getCreditsUsed($credit, $amount);

                $creditsConsumed += $creditsUsed;

                $amount -= $creditsUsed;

                $this->createTransactionForSource($credit, $source, $creditsUsed);
            }
        }

        $this->trace->info(TraceCode::CREDITS_CONSUMED,
            [
                'product'           => $product,
                'amount'            => $amount,
                'source_id'         => $source->getId(),
                'credits_consumed'  => $creditsConsumed,
            ]);

        return $creditsConsumed;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string $creditType
     * @param string $product
     * We will check if the merchant has credits available. Not credits deduction will be done at here.
     */
    public function fetchMerchantUnusedCredits(
        Merchant\Entity $merchant,
        string $creditType,
        string $product): array
    {
        $this->trace->info(TraceCode::UNUSED_CREDITS_AVAILABILITY_CHECK,
            [
                'credit_type' => $creditType,
                'product'     => $product,
                'merchant_id' => $merchant->getId(),
            ]);

        try {

            $credits = $this->repo->credits->getCreditsForMerchant($merchant->getId(), $product, $creditType);

            $creditsAvailable = 0;

            foreach ($credits as $credit)
            {
                if ($credit->isValid() === true)
                {
                    $unusedCredit = $credit->getValue() - $credit->getUsed();

                    $creditsAvailable += $unusedCredit;
                }
            }

            if ($creditsAvailable <= 0) {
                $this->trace->info(TraceCode::NO_UNUSED_CREDITS_LEFT,
                    [
                        'credit_type' => $creditType,
                        'product'     => $product,
                    ]);

                return [true, 0];
            }

            return [true, $creditsAvailable];
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::UNUSED_CREDITS_AVAILABILITY_CHECK_EXCEPTION,
                [
                    'error'                => $ex->getMessage()
                ]);
        }

        // Return 0 by default in case any exception occurred while processing.
        return [false, 0];
    }
}
