<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Base\RuntimeManager;
use RZP\Dashboard\Dashboard;
use RZP\Models\Merchant\Preferences;

trait SettlementTrait
{
    /**
     * @param $txns
     *
     * @return array
     * Returns array keyed by merchant id, and
     * the values are the filtered transactions for that merchant
     */
    protected function filterTransactionsForSettlement($txns): array
    {
        $filterGroupedTxns = [];

        foreach ($txns as $txn)
        {
            // skip if txn not to be settled
            if ($this->shouldSettle($txn) === false)
            {
                continue;
            }

            $skipForRefundAuthTxn = $this->skipForRefundAuthTxn($txn);

            if ($skipForRefundAuthTxn === true)
            {
                continue;
            }

            $skipForDsp = $this->skipForDsp($txn);

            if ($skipForDsp === true)
            {
                continue;
            }

            $skipForMutualFundsMarketplace = $this->skipForMutualFundsMarketplace($txn);

            if ($skipForMutualFundsMarketplace === true)
            {
                continue;
            }

            $merchantId = $txn->getMerchantId();

            $filterGroupedTxns[$merchantId] = ($filterGroupedTxns[$merchantId] ?? (new Base\PublicCollection));

            $filterGroupedTxns[$merchantId]->push($txn);
        }

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

                return true;
            }
        }

        return false;
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

            if (($now < $tenAm) or
                ($now > $threePm))
            {
                return true;
            }
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
                return true;
            }

            // Normal MF settlement window is 1pm-2pm (2 settlements)
            if (($now < $onePm) or
                ($now > $twoThirtyPm))
            {
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

    protected function createSettlementsFromTxns($txns, string $channel): array
    {
        $merchantId = $txns->first()->getMerchantId();

        $merchant = $this->merchants[$merchantId];

        $this->trace->info(TraceCode::SETTLEMENTS_CREATE_ENTITIES_FOR_MERCHANT, ['merchant' => $merchantId]);

        list($setlAmount, $setlFee, $setlApiFee, $tax) = $this->getSettlementAmountsForMerchant($txns);

        $balance = $merchant->balance->getBalance();

        if (($setlAmount < 100) or ($setlAmount > $balance))
        {
            $this->trace->info(TraceCode::SETTLEMENT_SKIPPED,
                [
                    'balance'    => $balance,
                    'merchant'   => $merchant->getId(),
                    'setlAmount' => $setlAmount,
                ]);

            return [null, null];
        }

        list($setl, $bankTransferAtpt) = $this->settleForMerchant(
            $merchant, $channel, $txns, $setlAmount, $setlFee, $setlApiFee, $tax);

        return [$setl, $bankTransferAtpt];
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

        $startTime = microtime(true);

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
            $this->trace->info(TraceCode::RECIPIENT_SETTLEMENT_NO_TXNS_TO_UPDATE);

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

            $timeTaken = microtime(true) - $startTime;

            $this->trace->info(TraceCode::RECIPIENT_SETTLEMENT_UPDATE_TIME_TAKEN, ['time_taken' => $timeTaken]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::CRITICAL, TraceCode::TRANSFER_UPDATE_SETTLEMENT_ID_FAILED, $filteredTxnIds);
        }
    }

    protected function settleForMerchant(
        $merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax): array
    {
        $settlement = null;

        $bankTransferAtpt = null;

        try
        {
            // create settlement and attempt
            $merchantSettler = new Merchant($merchant, $channel, $this->repo);

            $setlDetailAmounts = $merchantSettler->calculateSettlementDetailAmounts($setlTxns);

            $settlement = $merchantSettler->settle(
                                $setlTxns,
                                $setlAmount,
                                $setlFee,
                                $setlApiFee,
                                $tax,
                                $this->setlTime,
                                $setlDetailAmounts);

            $merchantSettler->createTransaction($settlement);

            $bankTransferAtpt = $merchantSettler->createSettlementAttempt();
        }
        catch (\Throwable $ex)
        {
            $traceData = [
                'merchant'      => $merchant->getId(),
                'setlAmount'    => $setlAmount,
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
        }

        return [$settlement, $bankTransferAtpt];
    }

    /**
     * Settlement is done only bank account change is not recent as we need some
     * time till beneficiary is updated in kotak
     *
     * @param Transaction\Entity $txn
     *
     * @return bool
     * @throws Exception\LogicException
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
            return false;
        }

        $shouldSettle = true;

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        $bankAccount = $merchant->bankAccount;

        if ($bankAccount === null)
        {
            throw new Exception\LogicException(
                'No bank account mapped for merchant settlement',
                null,
                ['merchant_id' => $merchant->getId()]);
        }

        if (($this->env !== 'testing') and
            ($bankAccount->getCreatedAt() > $lastWorkingDay->getTimestamp()))
        {
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

        (new SlackNotification)->success('setl_initiate', $data);

        Dashboard::send('settlement', $settlements);
    }

    protected function settlementFailure($channel, $e, $traceCode)
    {
        $e = new SettlementFailureException($channel, $e->getMessage(), null, $e);

        $this->failureNotification($e);

        throw $e;
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->failure('setl_initiate', $exception);
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
        RuntimeManager::setMemoryLimit('1024M');

        // Time limit of 9 mins 55 seconds
        RuntimeManager::setTimeLimit(599);
    }
}
