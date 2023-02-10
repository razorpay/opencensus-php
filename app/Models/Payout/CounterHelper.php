<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Counter;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\Feature\Constants;

class CounterHelper extends Base\Core
{
    // Constants for criteria of counter decrement

    const SET_STATUS = 'set_status';

    const INSUFFICIENT_BALANCE = 'insufficient_balance';

    const REVERSAL_OR_FAILURE = 'reversal_or_failure';

    const CRITERIA = 'criteria';

    const TRANSACTION_FAILURE = 'transaction_failure';

    const CURRENT_TIME_FIRST_OF_MONTH = 'current_time_first_of_month';

    const PAYOUT_INITIATED_FIRST_OF_MONTH = 'payout_initiated_first_of_month';

    const COUNTER = 'counter';

    /*
    This method is used to increment the counter if the balance is of type banking and the counter corresponding to that
    has not exceeded the free_payouts_count. It also decides what is going to be the
     */
    public function updateFreePayoutConsumedIfApplicable(Balance\Entity $balance)
    {
        if (($balance->getType() !== Balance\Type::BANKING) or
            (($balance->merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === true) and
             ($balance->getAccountType() === Balance\AccountType::SHARED)))
        {
            return null;
        }

        // This is to ensure that this method is called from within a transaction only as we are updating entities here
        // and don't want them to be done outside of a transaction.
        assertTrue($this->repo->counter->isTransactionActive());

        /** @var Counter\Entity $counter */
        $counter = (new Counter\Core)->fetchOrCreate($balance);

        $freePayoutsCount = (new Balance\FreePayout)->getFreePayoutsCount($balance);

        /** @var Counter\Entity $counter */
        $counter = $this->repo->counter->lockForUpdate($counter->getId());

        $counter = $this->resetFreePayoutsConsumedIfApplicable($counter);

        $freePayoutsConsumed = $counter->getFreePayoutsConsumed();

        // This is to prevent redundant db calls for merchants that do/might do a large number of payouts and therefore
        // we should not be checking the whole flow again and again for these merchants in such cases. We just have a
        // primary check for this so as to reduce the db calls for payouts after the allowed free payouts have been
        // consumed.
        if ($freePayoutsConsumed >= $freePayoutsCount)
        {
            return null;
        }

        $counter->setFreePayoutsConsumed($freePayoutsConsumed + 1);

        $this->repo->counter->saveOrFail($counter);

        $this->trace->info(
            TraceCode::FREE_PAYOUTS_CONSUMED_INCREMENT,
            [
                Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $counter->getFreePayoutsConsumedLastResetAt(),
                Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                Balance\FreePayout::FREE_PAYOUTS_COUNT              => $freePayoutsCount,
                self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
            ]
        );

        return Entity::FREE_PAYOUT;
    }

    /*
    This method is used to decrement the counter for multiple scenarios including set status calls, insufficient balance
    conditions, and reversals/failures of payouts.
     */
    public function decreaseFreePayoutsConsumedIfApplicable(Entity $payout, string $criteria)
    {
        if ($payout->getIsPayoutService() == true)
        {
            return false;
        }

        $shouldDecreaseFreePayoutsConsumed = false;

        $balance = $payout->balance;

        switch ($criteria)
        {
            case self::SET_STATUS:
                $shouldDecreaseFreePayoutsConsumed =
                    $this->shouldDecreaseFreePayoutsConsumedInCaseOfStatusUpdate($payout);
                break;

            case self::INSUFFICIENT_BALANCE:
                $shouldDecreaseFreePayoutsConsumed =
                    $this->shouldDecreaseFreePayoutsConsumedInCaseOfInsufficientBalance($payout);
                break;

            case self::REVERSAL_OR_FAILURE:
                $shouldDecreaseFreePayoutsConsumed =
                    $this->shouldDecreaseFreePayoutsConsumedInCaseOfReversalOrFailure($balance, $payout);
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_CRITERIA_SENT_FOR_COUNTER_DECREMENT,
                    $payout->toArrayPublic());
        }

        if ($shouldDecreaseFreePayoutsConsumed === true)
        {
            $this->fetchCounterAndDecreaseFreePayoutsConsumed($balance, $payout, $criteria);
        }

        return $shouldDecreaseFreePayoutsConsumed;
    }

    public function fetchCounterAndDecreaseFreePayoutsConsumed(
        Balance\Entity $balance, Entity $payout = null, string $criteria = "")
    {
        $counter = $this->getCounterForBalance($balance, $payout);

        $counter = $this->decreaseFreePayoutsConsumed($counter);

        $this->trace->info(
            TraceCode::FREE_PAYOUTS_CONSUMED_DECREMENT,
            [
                Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT =>
                    $counter->getFreePayoutsConsumedLastResetAt(),
                Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
                self::CRITERIA                                      => $criteria,
            ]
        );

        $resp = [
            Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
            Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $counter->getFreePayoutsConsumedLastResetAt(),
            Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
        ];

        return $resp;
    }

    /*
    This method is used to decrement counter in case of payout create failure or failure in cases like process queued
    payout, process pending payout, etc.
     */
    public function decreaseFreePayoutsConsumedInCaseOfTransactionFailure(string $balanceId)
    {
        /** @var Balance\Entity $balance */
        $balance = $this->repo->balance->findOrFailById($balanceId);

        if (($balance->getType() === Balance\Type::BANKING))
        {
            $accountType = $balance->getAccountType();

            /** @var Counter\Entity $counter */
            $counter = $this->repo->counter->getCounterByAccountTypeAndBalanceId($accountType, $balanceId);

            if ($counter !== null)
            {
                $counter = $this->decreaseFreePayoutsConsumed($counter);

                $this->trace->info(
                    TraceCode::FREE_PAYOUTS_CONSUMED_DECREMENT,
                    [
                        Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                        Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT =>
                            $counter->getFreePayoutsConsumedLastResetAt(),
                        Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                        self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                        self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
                        self::CRITERIA                                      => self::TRANSACTION_FAILURE,
                    ]
                );
            }
        }
    }

    /*
    This method is used to reset the counter if applicable. It resets counter for each balance id at the start of each
    month whenever a new payout is made for that balance id in the new month.
     */
    protected function resetFreePayoutsConsumedIfApplicable(Counter\Entity $counter)
    {
        if ($this->shouldResetFreePayoutsConsumed($counter) === true)
        {
            $counter->resetFreePayoutsConsumed();

            $this->trace->info(
                TraceCode::FREE_PAYOUTS_CONSUMED_RESET,
                [
                    Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                    Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT =>
                        $counter->getFreePayoutsConsumedLastResetAt(),
                    Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                    self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                    self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
                ]
            );
        }

        return $counter;
    }

    /*
    This method is used to check whether counter needs to be reset or not. It checks if the
    free_payouts_consumed_last_reset_at of the counter and the first date of the current month are equal or not. If not,
    reset. Else, don't reset.
    */
    protected function shouldResetFreePayoutsConsumed(Counter\Entity $counter)
    {
        $freePayoutsConsumedLastResetAt = $counter->getFreePayoutsConsumedLastResetAt();

        $currentTime = Carbon::now(Timezone::IST);

        $currentMonthTimestamp = $currentTime->firstOfMonth()->getTimestamp();

        $this->trace->info(
            TraceCode::FREE_PAYOUTS_CONSUMED_RESET_CHECK,
            [
                Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $freePayoutsConsumedLastResetAt,
                Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
                self::CURRENT_TIME_FIRST_OF_MONTH                   => $currentMonthTimestamp,
            ]
        );

        if ($currentMonthTimestamp !== $freePayoutsConsumedLastResetAt)
        {
            return true;
        }

        return false;
    }

    /*
    This method is used to decide whether to decrement counter in set status calls. It checks whether the payout was
    expected to be a free_payout (expectedFeeType is free_payout) and was not made a free payout, in that case, we
    should decrement the counter.
     */
    protected function shouldDecreaseFreePayoutsConsumedInCaseOfStatusUpdate(Entity $payout) : bool
    {
        $expectedFeeType = $payout->getExpectedFeeType();

        $feeType = $payout->getFeeType();

        if (($expectedFeeType === Entity::FREE_PAYOUT) and
            ($feeType !== Entity::FREE_PAYOUT))
        {
            return true;
        }

        return false;
    }

    /*
    This method is used to decide whether to decrement counter if payout was assigned fee type as free_payout. If such
    payout fails due to low balance and is made a queued payout instead, we should decrement it. It is also used in
    processing of batch_submitted payout processing where we fail payout based on similar criteria.
     */
    protected function shouldDecreaseFreePayoutsConsumedInCaseOfInsufficientBalance(Entity $payout) : bool
    {
        if ($payout->getFeeType() === Entity::FREE_PAYOUT)
        {
            return true;
        }

        return false;
    }

    /*
    This method decides whether the counter needs to be decreased in case of reversal/failure. Counter should be
    decreased in case the payout marked as free_payout was reversed/failed in the same month it was created.
     */
    protected function shouldDecreaseFreePayoutsConsumedInCaseOfReversalOrFailure(Balance\Entity $balance,
                                                                                  Entity $payout) : bool
    {
        /** @var Counter\Entity $counter */
        $counter = null;

        $shouldDecreaseFreePayoutConsumedForPayout = false;

        $freePayoutsSupportedModes = (new Balance\FreePayout)->getFreePayoutsSupportedModes($balance);

        if ((in_array($payout->getMode(), $freePayoutsSupportedModes, true) === true) and
            ($payout->getFeeType() === Entity::FREE_PAYOUT))
        {
            $counter = $this->getCounterForBalance($balance, $payout);

            $shouldDecreaseFreePayoutConsumedForPayout =
                $this->shouldDecreaseFreePayoutConsumedForPayout($counter, $payout);
        }

        if (($shouldDecreaseFreePayoutConsumedForPayout === true) and
            (empty($counter) === false))
        {
            return true;
        }

        return false;
    }

    public function getCounterForBalance(Balance\Entity $balance, Entity $payout = null)
    {
        $accountType = $balance->getAccountType();

        $balanceId = $balance->getId();

        $counter = $this->repo->counter->getCounterByAccountTypeAndBalanceId($accountType, $balanceId);

        if ($counter === null)
        {
            throw new Exception\LogicException(
                'No counter found.',
                ErrorCode::SERVER_ERROR_COUNTER_ABSENT,
                [
                    'payout_id'    => $payout->getId() ?? '',
                    'merchant_id'  => $balance->getMerchantId(),
                    'balance_id'   => $balance->getId(),
                    'account_type' => $balance->getAccountType(),
                ]);
        }

        return $counter;
    }

    /*
    This method is used to check whether counter needs to be decreased in case of payout failure/reversal. It checks if
    the free_payouts_consumed_last_reset_at of the counter and the first date of the payout initiation month are equal
    or not. If yes, decrease. Else, don't decrease.
    */
    protected function shouldDecreaseFreePayoutConsumedForPayout(Counter\Entity $counter, Entity $payout) : bool
    {
        $freePayoutsConsumedLastResetAt = $counter->getFreePayoutsConsumedLastResetAt();

        $payoutInitiatedDate = Carbon::createFromTimestamp($payout->getInitiatedAt(), Timezone::IST);

        $payoutInitiatedMonth = $payoutInitiatedDate->firstOfMonth()->getTimestamp();

        $payoutStateChangeDate = Carbon::now(Timezone::IST);

        $payoutStateChangeMonth = $payoutStateChangeDate->firstOfMonth()->getTimestamp();

        $this->trace->info(
            TraceCode::FREE_PAYOUTS_CONSUMED_DECREMENT_CHECK,
            [
                Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $freePayoutsConsumedLastResetAt,
                Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
                self::CURRENT_TIME_FIRST_OF_MONTH                   => $payoutStateChangeMonth,
                self::PAYOUT_INITIATED_FIRST_OF_MONTH               => $payoutInitiatedMonth,
            ]
        );

        if (($freePayoutsConsumedLastResetAt === $payoutInitiatedMonth) and
           ($freePayoutsConsumedLastResetAt === $payoutStateChangeMonth))
        {
            return true;
        }

        return false;
    }

    protected function decreaseFreePayoutsConsumed(Counter\Entity $counter)
    {
        $updatedCounter = $this->repo->counter->transaction(
            function() use ($counter)
            {
                $counter = $this->repo->counter->lockForUpdate($counter->getId());

                $freePayoutsConsumed = $counter->getFreePayoutsConsumed();

                $counter->setFreePayoutsConsumed($freePayoutsConsumed - 1);

                $this->repo->counter->saveOrFail($counter);

                return $counter;
            });

        return $updatedCounter;
    }

    public function rollbackCounter(Balance\Entity $balance, $freePayoutsConsumed, $freePayoutsConsumedLastResetAt)
    {
        /** @var Counter\Entity $counter */
        $counter = (new Counter\Core)->fetchOrCreate($balance);

        /** @var Counter\Entity $counter */
        $counter = $this->repo->counter->lockForUpdate($counter->getId());

        $counter->setFreePayoutsConsumed($freePayoutsConsumed);
        $counter->setFreePayoutsConsumedLastResetAt($freePayoutsConsumedLastResetAt);

        $this->repo->counter->saveOrFail($counter);

        $this->trace->info(
            TraceCode::COUNTER_ENTITY_ROLLBACK,
            [
                Counter\Entity::FREE_PAYOUTS_CONSUMED               => $counter->getFreePayoutsConsumed(),
                Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => $counter->getFreePayoutsConsumedLastResetAt(),
                Counter\Entity::BALANCE_ID                          => $counter->getBalanceId(),
                self::COUNTER . '_' . Counter\Entity::ID            => $counter->getId(),
                self::COUNTER . '_' . Counter\Entity::UPDATED_AT    => $counter->getUpdatedAt(),
            ]
        );
    }
}
