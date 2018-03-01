<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Base\RuntimeManager;
use RZP\Constants\Mode;
use RZP\Dashboard\Dashboard;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\Payment;
use RZP\Models\Feature\Constants as FConstants;
use RZP\Models\Merchant\Preferences;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
            $merchant = $txn->merchant;

            // skip if txn not to be settled
            if ($this->shouldSettle($merchant) === false)
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

            $merchantId = $merchant->getId();

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
        // Settle only between 12pm and 1 pm

        // Is a submerchant of a mutual fund market place
        $isSubMerchantOfMf = false;

        // Mutual Fund Marketplace Merchant ids
        $mfMids = [
            Preferences::MID_GOALWISE_TPV,
            Preferences::MID_GOALWISE_NON_TPV,
            Preferences::MID_WEALTHAPP,
            Preferences::MID_WEALTHY,
        ];

        if (($txn->isTypePayment() === true) and
            ($txn->merchant->isLinkedAccount() === true) and
            (in_array($txn->merchant->getParentId(), $mfMids, true) === true))
        {
            $isSubMerchantOfMf = true;
        }

        if ($isSubMerchantOfMf === true)
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $onePm = Carbon::today(Timezone::IST)->hour(13)->getTimestamp();

            $oneThirtyPm = Carbon::today(Timezone::IST)->hour(13)->minute(30)->getTimestamp();

            $twoPm = Carbon::today(Timezone::IST)->hour(14)->getTimestamp();

            $twoTenPm = Carbon::today(Timezone::IST)->hour(14)->minute(10)->getTimestamp();

            //
            // Wealthy does not want any settlements to happen outside their given window,
            // i.e. after 1pm. TODO: Better way to implement this.
            //
            if (($txn->merchant->getParentId() === Preferences::MID_WEALTHY) and
                ($now > $oneThirtyPm))
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
                return false;
            }

            if (($now < $onePm) or
                ($now > $twoTenPm))
            {
                return true;
            }
        }

        return false;
    }

    protected function createSettlementsFromTxns($txns, string $channel): array
    {
        $merchant = $txns->first()->merchant;

        list($setlAmount, $setlFee, $setlApiFee, $tax) = $this->getSettlementAmountsForMerchant($txns);

        $balance = $merchant->balance->getBalance();

        if (($setlAmount <= 100) or ($setlAmount > $balance))
        {
            $this->trace->info(TraceCode::SETTLEMENT_SKIPPED,
                [
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
        foreach ($txns as $txn)
        {
            if (($txn->isTypePayment() === true) and ($txn->merchant->isLinkedAccount() === true))
            {
                $filteredTxnIds[] = $txn->getId();
            }
        }

        if (empty($filteredTxnIds) === true)
        {
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
     * @param MerchantEntity $merchant
     *
     * @return bool
     * @throws Exception\LogicException
     */
    protected function shouldSettle(MerchantEntity $merchant): bool
    {
        //
        // Skip settlements for few merchants
        // Details in: https://github.com/razorpay/api/issues/5830
        // Temporary, until https://github.com/razorpay/api/pull/6161
        // is merged
        //
        $skipMerchantIds = [
            Preferences::MID_GOALWISE_NON_TPV,
            Preferences::MID_GOALWISE_TPV,
            Preferences::MID_MONEYVIEW,
            Preferences::MID_WEALTHY,
            Preferences::MID_PIGGY,
        ];

        if (in_array($merchant->getId(), $skipMerchantIds, true) === true)
        {
            return false;
        }

        $today = Carbon::today(Timezone::IST);

        // Do not settle for Wealthy's sub-merchants on Saturday
        if (($merchant->getParentId() === Preferences::MID_WEALTHY) and
            ($today->dayOfWeek === Carbon::SATURDAY))
        {
            return false;
        }

        $shouldSettle = true;

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        if ($merchant->bankAccount === null)
        {
            throw new Exception\LogicException(
                'No bank account mapped for merchant settlement',
                null,
                ['merchant_id' => $merchant->getId()]);
        }

        if (($this->env !== 'testing') and
            ($merchant->bankAccount->getCreatedAt() > $lastWorkingDay->getTimestamp()))
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

//        $this->failureNotification($e);

        throw $e;
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->failure('setl_initiate', $exception);
    }

    /**
     * Returns the list of all channels for which settlments needs to be done
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
        RuntimeManager::setTimeLimit(300);
    }
}
