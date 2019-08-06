<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Base\RuntimeManager;
use RZP\Dashboard\Dashboard;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Payout\Core as PayoutCore;

trait SettlementTrait
{
    protected function filterMerchantTransactionsForSettlement($transactions): array
    {
        foreach ($transactions as $transaction)
        {

        }
    }

    protected function isMerchantSettlementAllowed(Merchant\Entity $merchant): bool
    {
        $merchantFeatures = $merchant->getEnabledFeatures();

        // if early settlement is not enabled then continute with normal settlement cycle for the merchant
        if (in_array(Feature\Constants::ES_AUTOMATIC, $merchantFeatures, true) === false)
        {
            return true;
        }

        // if the merchant has early settlement enabled then check the time
        if ($this->isEarlySettlementTime() === true)
        {
            return true;
        }

        // if ES_AUTOMATIC_THREE_PM is enabled on merchant then do settlement only after 3PM
        if (in_array(Feature\Constants::ES_AUTOMATIC_THREE_PM, $merchantFeatures, true) === false)
        {
            $threePm = Carbon::today(Timezone::IST)->hour(15)->getTimestamp();

            if ($now > $threePm)
            {
                return true;
            }
        }

        return false;
    }

    protected function isEarlySettlementTime(): bool
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $fivePm = Carbon::today(Timezone::IST)->hour(17)->getTimestamp();

        $sixPm = Carbon::today(Timezone::IST)->hour(18)->getTimestamp();

        $nineAm = Carbon::today(Timezone::IST)->hour(9)->getTimestamp();

        $tenAm = Carbon::today(Timezone::IST)->hour(10)->getTimestamp();

        //
        // Settle the transaction if time is between 9-10 am or 5-6pm
        // This is the time window promised to the merchants on ES.
        // For example, if a transaction's settled_at is 7 am, this
        // condition ensures that it doesn't get settled in the 7 or 8 am
        // batch but only in the 9 am batch.
        //

        if ((($now >= $nineAm) and ($now < $tenAm)) or
            (($now >= $fivePm) and ($now < $sixPm)))
        {
            return false;
        }

        return true;
    }

    /**
     * @param $txns
     *
     * @return array
     * Returns array keyed by merchant id, and
     * the values are the filtered transactions for that merchant
     */
    protected function filterTransactionsForSettlement($txns): array
    {
        $filterGroupedTxns    = [];

        $transactionSkipCount    = 0;

        $transactionsSettleCount = 0;

        $esMerchants = $this->repo->feature
                            ->findMerchantsHavingFeatures([Feature\Constants::ES_AUTOMATIC])
                            ->pluck(Feature\Entity::ENTITY_ID)
                            ->toArray();

        $esMerchantsThreePm = $this->repo->feature
                                  ->findMerchantsHavingFeatures([Feature\Constants::ES_AUTOMATIC_THREE_PM])
                                  ->pluck(Feature\Entity::ENTITY_ID)
                                  ->toArray();


        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENTS_TXNS_GROUP_BY_MERCHANT_START);

         // Here we are fetch each transaction using a reference.
         // This avoids loading the entire transaction entity from
         // Public Collection preventing extra memory consumption.
        foreach ($txns as $txn)
        {
            // skip if txn not to be settled
            if ($this->shouldSettle($txn) === false)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForRefundAuthTxn = $this->skipForRefundAuthTxn($txn);

            if ($skipForRefundAuthTxn === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForEarlySettlement = $this->skipForEarlySettlement($txn, $esMerchants, $esMerchantsThreePm);

            if ($skipForEarlySettlement === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForDsp = $this->skipForDsp($txn);

            if ($skipForDsp === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForMutualFundsMarketplace = $this->skipForMutualFundsMarketplace($txn);

            if ($skipForMutualFundsMarketplace === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForKarvy = $this->skipForKarvy($txn);

            if ($skipForKarvy === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $transactionsSettleCount++;

            $merchantId = $txn->getMerchantId();

            $filterGroupedTxns[$merchantId] = ($filterGroupedTxns[$merchantId] ?? (new Base\PublicCollection));

            $filterGroupedTxns[$merchantId]->push($txn);

            $txn = null;
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTIONS_SKIPPED,
            [
                'transactions_skip_count'   => $transactionSkipCount,
                'transactions_settle_count' => $transactionsSettleCount
            ]
        );

        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENTS_TXNS_GROUP_BY_MERCHANT_END);

        return $filterGroupedTxns;
    }

    protected function skipForRefundAuthTxn($txn): bool
    {
        // skip if txn is refund of authorized txn and update the txn
        if (($txn->getBalance() === 0) and
            ($txn->isTypeRefund()))
        {
            $payment = $txn->source->payment;

            if ($payment->hasBeenCaptured() === false)
            {
                $txn[Transaction\Entity::SETTLED_AT] = null;

                $this->repo->saveOrFail($txn);

                $this->trace->count(
                    Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                    [
                        Metric::SKIP_REASON => Metric::AUTH_PAYMENT
                    ],
                    1);

                $this->trace->count(
                    Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                    [
                        Metric::SKIP_REASON => Metric::REFUND_AUTH_PAYMENT
                    ],
                    1);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::REFUND_AUTH_PAYMENT
                    ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Early settlement timing check added
     *
     * @param $txn
     * @param $esMerchants
     * @return bool
     */
    protected function skipForEarlySettlement($txn, $esMerchants, $esMerchantsThreePm): bool
    {
        $mid = $txn->getMerchantId();

        if (in_array($mid, $esMerchants, true) === false)
        {
            return false;
        }

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $fivePm = Carbon::today(Timezone::IST)->hour(17)->getTimestamp();

        $sixPm = Carbon::today(Timezone::IST)->hour(18)->getTimestamp();

        $nineAm = Carbon::today(Timezone::IST)->hour(9)->getTimestamp();

        $tenAm = Carbon::today(Timezone::IST)->hour(10)->getTimestamp();

        //
        // Settle the transaction if time is between 9-10 am or 5-6pm
        // This is the time window promised to the merchants on ES.
        // For example, if a transaction's settled_at is 7 am, this
        // condition ensures that it doesn't get settled in the 7 or 8 am
        // batch but only in the 9 am batch.
        //
        if ((($now >= $nineAm) and ($now < $tenAm)) or
            (($now >= $fivePm) and ($now < $sixPm)))
        {
            return false;
        }

        //
        // If settlement was delayed for some reason, beyond our control, settle ASAP
        // For example, if a transaction's settled_at is 7 am, but for some
        // reason the transaction wasn't settled at 9 am, and now it is 2 pm
        // then we want the transactions to be settled even if it's outside
        // the merchant's settlement window, because this transaction's
        // settlement should have been done at 9, and it's not delayed.
        //
        // TODO: handle this my sending merchant ID in the settlement create
        //  so the execution is overriden on these conditions
        if ((($txn->getSettledAt() <= $fivePm) and ($now > $fivePm)) or
            (($txn->getSettledAt() <= $nineAm) and ($now > $nineAm)))
        {
            return false;
        }

        if (in_array($mid, $esMerchantsThreePm, true) === true)
        {
            $threePm = Carbon::today(Timezone::IST)->hour(15)->getTimestamp();

            if (($txn->getSettledAt() <= $threePm) and ($now > $threePm))
            {
                return false;
            }

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $txn->getMerchantId(),
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_OUTSIDE_ES_THREE_PM_WINDOW
                ]);

            return true;
        }

        $this->trace->count(
            Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
            [
                Metric::SKIP_REASON => Metric::BLOCK_OUTSIDE_ES_WINDOW
            ],
            1);

        $this->trace->info(
            TraceCode::SETTLEMENT_SKIPPED,
            [
                'merchant_id'       => $txn->getMerchantId(),
                'transaction_id'    => $txn->getId(),
                'source_id'         => $txn->getEntityId(),
                'reason'            => Metric::BLOCK_OUTSIDE_ES_WINDOW
            ]);

        return true;
    }

    protected function skipForDsp($txn): bool
    {
        // DSP wants settlements only between 10 am and 3 pm ¯\_(ツ)_/¯
        //
        // TODO : Move this to schedules
        // https://github.com/razorpay/api/issues/5347
        //
        if ($txn->getMerchantId() === '7thBRSDflu7NHL')
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $tenAm = Carbon::today(Timezone::IST)->hour(10)->getTimestamp();

            $threePm = Carbon::today(Timezone::IST)->hour(15)->minute(10)->getTimestamp();

            //This condition used when dsp transactions misses the settlement window of 10am - 3pm
            // but needs to be settled immediately on the next cron run, same day
            if (($now > $threePm) and ($txn->getSettledAt() <= $threePm))
            {
                return false;
            }

            if (($now >= $tenAm) and
                ($now <= $threePm))
            {
                return false;
            }

            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                ],
                1);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $txn->getMerchantId(),
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                ]);

            return true;
        }

        return false;
    }

    protected function skipForMutualFundsMarketplace($txn): bool
    {
        // Mutual Fund Marketplace Merchant ids
        $mfMids = [
            Preferences::MID_GOALWISE_TPV,
            Preferences::MID_GOALWISE_NON_TPV,
            Preferences::MID_WEALTHAPP,
            Preferences::MID_WEALTHY,
            Preferences::MID_PAISABAZAAR,
        ];

        $mid = $txn->getMerchantId();

        $merchant = $this->merchants[$mid];

        $parentId = $merchant->getParentId();

        // Check if it is a sub-merchant of a Mutual-fund account
        if (($txn->isTypePayment() === true) and
            ($merchant->isLinkedAccount() === true) and
            (in_array($parentId, $mfMids, true) === true))
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $onePm = Carbon::today(Timezone::IST)->hour(13)->getTimestamp();

            $twoPm = Carbon::today(Timezone::IST)->hour(14)->getTimestamp();

            $twoThirtyPm = Carbon::today(Timezone::IST)->hour(14)->minute(30)->getTimestamp();

            // If settlement was delayed for some reason, beyond our control, settle ASAP
            if ($this->isDelayedSettlement($txn) === true)
            {
                return false;
            }

            //
            // Maps the mids that want to receive only 1 settlement per day.
            // They need all transactions till 1 pm to be settled in the 1 pm cycle.
            //
            $oneSetlAt1PmMids = [
                Preferences::MID_WEALTHY,
                Preferences::MID_PAISABAZAAR,
            ];

            if ((in_array($parentId, $oneSetlAt1PmMids, true) === true) and
                (($now < $onePm) or
                 ($now >= $twoPm)))
            {
                $this->trace->count(
                    Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                    [
                        Metric::SKIP_REASON => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ],
                    1);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ]);

                return true;
            }

            // Normal MF settlement window is 1pm-2pm (2 settlements)
            if (($now < $onePm) or
                ($now > $twoThirtyPm))
            {
                $this->trace->count(
                    Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                    [
                        Metric::SKIP_REASON => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ],
                    1);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Applicable only for Mutual Fund Transactions.
     * That need to be settled only between 1-2 PM
     *
     * @param $txn
     * @return bool
     */
    protected function isDelayedSettlement($txn): bool
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $twoPm = Carbon::today(Timezone::IST)->hour(14)->getTimestamp();

        $today = Carbon::today(Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::SETTLEMENT_DELAYED_MF_CHECK,
            [
                'now'               => $now,
                'two_pm'            => $twoPm,
                'today'             => $today,
                'txn_settled_at'    => $txn->getSettledAt()
            ]);

        //
        // If the transaction was due settlement before today, but wasn't
        // settled for whatever reason, we want to try to settle it immediately.
        //
        if ($txn->getSettledAt() < $today)
        {
            return true;
        }

        //
        // Settle transaction which needed to be settled before 2 pm today
        // but for whatever reason weren't picked up then.
        // In this case, the below condition of settlement window of 1-2 PM
        // is not applicable, because these were due for settlement
        // before 2 pm, and should have been picked up.
        //
        if (($txn->getSettledAt() <= $twoPm) and ($now > $twoPm))
        {
            return true;
        }

        return false;
    }

    protected function createSettlementsFromTxns($txns, string $channel, $merchantSettleToPartner): array
    {
        $merchantId = $txns->first()->getMerchantId();

        $merchant = $this->merchants[$merchantId];

        if ($this->isDebugEnabled() === true)
        {
            $this->trace->info(TraceCode::SETTLEMENTS_CREATE_ENTITIES_FOR_MERCHANT, ['merchant' => $merchantId]);
        }

        list($setlAmount, $setlFee, $setlApiFee, $tax) = $this->getSettlementAmountsForMerchant($txns);

        $balance = $merchant->primaryBalance->getBalance();

        if (($setlAmount < 100) or ($setlAmount > $balance))
        {
            $skipReason = ($setlAmount < 100) ? Metric::MIN_SETTLEMENT_AMOUNT_BLOCK : Metric::SETTLEMENT_AMOUNT_LESS_THAN_BALANCE;

            $this->trace->count(
                Metric::MERCHANTS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => $skipReason
                ],
                1);

            $this->trace->info(TraceCode::SETTLEMENT_SKIPPED,
                [
                    'balance'    => $balance,
                    'merchant'   => $merchant->getId(),
                    'setlAmount' => $setlAmount,
                    'reason'     => 'settlement amount less than 1rs or greater than balance',
                ]);

            return [null, null];
        }

        try
        {
            list($setl, $bankTransferAtpt) = $this->settleForMerchant(
                $merchant, $channel, $txns, $setlAmount, $setlFee, $setlApiFee, $tax, $merchantSettleToPartner);

            return [$setl, $bankTransferAtpt];
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            return [null, null];
        }
    }

    protected function getSettlementAmountsForMerchant($txns): array
    {
        $setlAmount = $setlApiFee = 0;
        $setlFee = $tax = 0;

        foreach ($txns as $txn)
        {
            $setlAmount     += $txn->getCredit() - $txn->getDebit();
            $setlApiFee     += $txn->getApiFee();
            $setlFee        += $txn->getFee();
            $tax            += $txn->getTax();
        }

        return [$setlAmount, $setlFee, $setlApiFee, $tax];
    }

    /**
     * [Marketplace] Updates the recipient's settlement id in the transfer entity.
     *
     *  When the transactions for the internal payments (payments triggered by the transfer
     *  from master merchant to the linked account) are settled, the settlement_id of those
     *  transactions will be updated for the transfer entity that initiated these payments.
     *
     * @param $txns
     */
    protected function updateSettlementIdInTransfer(\Illuminate\Support\Collection $txns)
    {
        $filteredTxnIds = [];

        if ($this->isDebugEnabled() === true)
        {
            $startTime = microtime(true);
        }

        foreach ($txns as $txn)
        {
            $merchantId = $txn->getMerchantId();

            $merchant = $this->merchants[$merchantId];

            if (($txn->isTypePayment() === true) and ($merchant->isLinkedAccount() === true))
            {
                $filteredTxnIds[] = $txn->getId();
            }
        }

        if (empty($filteredTxnIds) === true)
        {
            if ($this->isDebugEnabled() === true)
            {
                $this->trace->info(TraceCode::RECIPIENT_SETTLEMENT_NO_TXNS_TO_UPDATE);
            }

            return;
        }

        try
        {
            $relations = ['source', 'source.transfer'];

            $filteredTxns = $this->repo->transaction->findManyWithRelations($filteredTxnIds, $relations);

            foreach ($filteredTxns as $txn)
            {
                $settlementId = $txn->getSettlementId();

                if ($settlementId === null)
                {
                    continue;
                }

                $transfer = $txn->source->transfer;

                $transfer->setRecipientSettlementId($settlementId);

                $this->repo->saveOrFail($transfer);
            }

            if ($this->isDebugEnabled() === true)
            {
                $timeTaken = microtime(true) - $startTime;

                $this->trace->info(TraceCode::RECIPIENT_SETTLEMENT_UPDATE_TIME_TAKEN, ['time_taken' => $timeTaken]);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::CRITICAL, TraceCode::TRANSFER_UPDATE_SETTLEMENT_ID_FAILED, $filteredTxnIds);
        }
    }

    /**
     * Used payout mutex to block merchant from creating a
     * settlement when payout is in process for the same merchant.
     *
     * @param $merchant
     * @param $channel
     * @param $setlTxns
     * @param $setlAmount
     * @param $setlFee
     * @param $setlApiFee
     * @param $tax
     * @param $merchantSettleToPartner
     * @return array
     */
    protected function settleForMerchant(
        $merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax, $merchantSettleToPartner): array
    {
        $settlement = null;

        $bankTransferAtpt = null;

        $mutexResource = sprintf(PayoutCore::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use($merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax, $settlement, $bankTransferAtpt,
                 $merchantSettleToPartner) {
                try
                {
                    // create settlement and attempt
                    $merchantSettler = new Merchant($merchant, $channel, $this->repo, $this->isDebugEnabled());

                    $setlDetailAmounts = $merchantSettler->calculateSettlementDetailAmounts($setlTxns);

                    $this->traceSettlementDelayOfTransactions($setlTxns);

                    $settlement = $merchantSettler->settle(
                        $setlTxns,
                        $setlAmount,
                        $setlFee,
                        $setlApiFee,
                        $tax,
                        $this->setlTime,
                        $setlDetailAmounts,
                        $merchantSettleToPartner);

                    $merchantSettler->createTransaction($settlement);

                    $bankTransferAtpt = $merchantSettler->createSettlementAttempt($merchantSettleToPartner);
                }
                catch (\Exception $ex)
                {
                    $traceData = [
                        'merchant_id'   => $merchant->getId(),
                        'setlAmount'    => $setlAmount,
                        'reason'        => $ex->getMessage(),
                    ];

                    if ($settlement !== null)
                    {
                        $traceData['settlement_id'] = $settlement->getId();

                        //
                        // Mark it failed anyway, so that it can be retried
                        //
                        $settlement->setStatus(Status::FAILED);

                        $this->repo->saveOrFail($settlement);
                    }

                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::SETTLEMENT_SKIPPED,
                        $traceData);

                    (new SlackNotification)->send('setl_skipped', $traceData, $ex);
                }
                finally
                {
                    return [$settlement, $bankTransferAtpt];
                }
                },PayoutCore::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);
    }

    protected function traceSettlementDelayOfTransactions($setlTxns)
    {
        foreach ($setlTxns as $txn)
        {
            $timeTaken = intval(($this->setlTime - $txn->getSettledAt()) / 60);

            $this->trace->histogram(
                Metric::TRANSACTION_SETTLEMENT_INITIATION_DELAY_MINUTES,
                $timeTaken,
                [Metric::CHANNEL => $txn->getChannel()]
            );
        }
    }

    /**
     * Settlement is done only bank account change is not recent as we need some
     * time till beneficiary is updated in kotak
     *
     * @param Transaction\Entity $txn
     * @return bool
     */
    protected function shouldSettle(Transaction\Entity $txn): bool
    {
        $mid = $txn->getMerchantId();

        $merchant = $this->merchants[$mid];

        $today = Carbon::today(Timezone::IST);

        // Do not settle for Wealthy's sub-merchants on Saturday
        if (($merchant->getParentId() === Preferences::MID_WEALTHY) and
            ($today->dayOfWeek === Carbon::SATURDAY))
        {
            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BLOCK_WEALTHY_ON_SATURDAY
                ],
                1);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $mid,
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_WEALTHY_ON_SATURDAY
                ]);

            return false;
        }

        $shouldSettle = true;

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        $bankAccount = $merchant->bankAccount;

        if ($bankAccount === null)
        {
            $this->trace->error(
                TraceCode::SETTLEMENT_MERCHANT_BANK_ACCOUNT_NOT_MAPPED,
                [
                    'merchant_id'    => $merchant->getId(),
                    'transaction_id' => $txn->getId()
                ]
            );

            return false;
        }

        $channel = $txn->getChannel();

        $allowedChannelFor24x7Settlement = Channel::get24x7Channels();

        if (($this->env !== 'testing') and
            (in_array($channel, $allowedChannelFor24x7Settlement, true) === true))
        {
            return true;
        }

        if (($this->env !== 'testing') and
            ($bankAccount->getCreatedAt() > $lastWorkingDay->getTimestamp()))
        {
            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BANK_ACCOUNT_CREATED_YESTERDAY
                ]);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $mid,
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BANK_ACCOUNT_CREATED_YESTERDAY,
                    'created_at'        => Carbon::createFromTimestamp($txn->getCreatedAt(), Timezone::IST)->format('Y-m-d H:i:s'),
                ]);

            $shouldSettle = false;
        }

        return $shouldSettle;
    }

    protected function traceSetlInitiating($channel)
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y H:i:s');

        $this->trace->info(
            TraceCode::SETTLEMENT_INITIATING,
            [
                'channel'   => $channel,
                'timestamp' => $this->setlTime,
                'time'      => $time,
            ]);
    }

    protected function successNotification($data, $settlements, $traceCode)
    {
        $this->trace->info($traceCode, $data);

        (new SlackNotification)->send('setl_initiate', $data);
    }

    protected function settlementFailure($channel, $e, $traceCode)
    {
        $e = new SettlementFailureException($channel, $e->getMessage(), null, $e);

        $this->failureNotification($e);

        throw $e;
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->send('setl_initiate', [], $exception);
    }

    /**
     * Returns the list of all channels for which settlments needs to be done
     *
     * @param string|null $channel
     *
     * @return array
     */
    protected function getArrayedChannels($channel = null)
    {
        if ($channel === null)
        {
            $channels = Channel::getChannels();
        }
        else
        {
            $channels = [$channel];
        }

        return $channels;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('6144M');

        // Time limit of 30 mins
        RuntimeManager::setTimeLimit(1500);
    }

    /**
     * Early settlement timing check added
     *
     * @param $txn
     * @return bool
     */
    protected function skipForKarvy($txn): bool
    {
        $merchant = $this->merchants[$txn->getMerchantId()];

        // Karvy wants settlements only at 1 pm and 3 pm ¯\_(ツ)_/¯
        if ($merchant->getParentId() === Preferences::MID_KARVY)
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $onePm = Carbon::today(Timezone::IST)->hour(13)->getTimestamp();

            $threePm = Carbon::today(Timezone::IST)->hour(15)->getTimestamp();

            $yesterdayThreePm = Carbon::yesterday(Timezone::IST)->hour(15)->getTimestamp();

            //if settlement for a previous day transaction with
            // settled_at of 3pm was not created then intiate it asap next day
            if ($txn->getSettledAt() <= $yesterdayThreePm)
            {
              return false;
            }

            if (($now > $threePm) and ($txn->getSettledAt() <= $threePm))
            {
                return false;
            }

            if (($now > $onePm) and ($txn->getSettledAt() <= $onePm))
            {
                return false;
            }

            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BLOCK_KARVY_OUTSIDE_TIME_PERIOD
                ],
                1);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $txn->getMerchantId(),
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_KARVY_OUTSIDE_TIME_PERIOD
                ]);

            return true;

        }

        return false;
    }

    protected function traceMemoryUsage(string $traceCode)
    {
        if ($this->isDebugEnabled() === true)
        {
            $memoryAllocated = get_human_readable_size(memory_get_usage(true));
            $memoryUsed = get_human_readable_size(memory_get_usage());
            $memoryPeakUsage = get_human_readable_size(memory_get_peak_usage());
            $memoryPeakUsageAllocated = get_human_readable_size(memory_get_peak_usage(true));

            $this->trace->info(
                $traceCode,
                [
                    'memory_allocated' => $memoryAllocated,
                    'memory_used' => $memoryUsed,
                    'memory_peak_usage' => $memoryPeakUsage,
                    'memory_peak_usage_allocated' => $memoryPeakUsageAllocated,
                ]);
        }
    }

    /**
     * @param $txns
     * @param $merchantId
     * @return array
     */
    protected function processMerchantSettlement($txns, $merchantId): array
    {
        $filterGroupedTxns       = [];

        $transactionSkipCount    = 0;

        $transactionsSettleCount = 0;

        foreach ($txns as $txn)
        {
            $skipForRefundAuthTxn = $this->skipForRefundAuthTxn($txn);

            if ($skipForRefundAuthTxn === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $filterGroupedTxns[$merchantId] = ($filterGroupedTxns[$merchantId] ?? (new Base\PublicCollection));

            $filterGroupedTxns[$merchantId]->push($txn);
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTIONS_SKIPPED,
            [
                'transactions_skip_count'   => $transactionSkipCount,
                'transactions_settle_count' => $transactionsSettleCount
            ]
        );

        return $filterGroupedTxns;
    }
}
