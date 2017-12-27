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
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Entity as MerchantEntity;

trait SettlementTrait
{
    protected function filterTransactionsForSettlement($txns)
    {
        $filteredTxns = new Base\PublicCollection;

        foreach ($txns as $txn)
        {
            // skip if txn not to be settled
            if ($this->shouldSettle($txn->merchant) === false)
            {
                continue;
            }

            // skip if txn is refund of authorized txn and update the txn
            if (($txn->getBalance() === 0) and
                ($txn->isTypeRefund()))
            {
                $payment = $txn->source->payment;

                if ($payment->hasBeenCaptured() === false)
                {
                    $txn[Transaction\Entity::SETTLED_AT] = null;

                    $this->repo->saveOrFail($txn);

                    continue;
                }
            }

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
                    continue;
                }
            }

            $filteredTxns->push($txn);
        }

        return $filteredTxns;
    }

    protected function createSettlementsFromTxns($txns, $channel): array
    {
        $settlements = new Base\PublicCollection;
        $setlAttempts = new Base\PublicCollection;
        $txnsSettledCount = 0;

        $i = 0;
        $txnsCount = $txns->count();

        while ($i < $txnsCount)
        {
            $merchant = $txns[$i]->merchant;

            // Settlement amount
            list($setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax, $setlGatewayFee) =
                $this->getSettlementAmountsForMerchant($txns, $i, $txnsCount, $merchant);

            //
            // settle only if settlement amount is more than INR 1 and greater than
            // merchants account balance
            //
            $balance = $merchant->balance->getBalance();

            if (($setlAmount <= 100) or ($setlAmount > $balance))
            {
                $this->trace->info(TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant'   => $merchant->getId(),
                        'setlAmount' => $setlAmount,
                        'balance'    => $balance
                    ]);

                continue;
            }

            list($setl, $bankTransferAtpt) = $this->settleForMerchant(
                $merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax);

            $txnsSettledCount += $setlTxns->count();

            $settlements->push($setl);

            $setlAttempts->push($bankTransferAtpt);
        }

        $this->updateSettlementIdInTransfer($txns);

        return [$settlements, $txnsSettledCount, $setlAttempts];
    }

    protected function getSettlementAmountsForMerchant($txns, & $i, $txnsCount, $merchant): array
    {
        $setlAmount = $setlGatewayFee = $setlApiFee = 0;
        $setlFee = $tax = 0;

        $setlTxns = new Base\PublicCollection;

        assert($txns[$i]->merchant !== null);

        $merchantId = $merchant->getId();

        // Since transactions are ordered by the merchant id,
        // we can do the following operation in O(n) instead of O(n^2)
        while (($i < $txnsCount) and
               ($txns[$i]->getMerchantId() === $merchantId))
        {
            $txn = $txns[$i];

            $setlAmount     += $txn->getCredit() - $txn->getDebit();
            $setlGatewayFee += $txn->getGatewayFee();
            $setlApiFee     += $txn->getApiFee();
            $setlFee        += $txn->getFee();
            $tax            += $txn->getTax();

            $setlTxns->push($txn);
            $i++;
        }

        return [$setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax, $setlGatewayFee];
    }

    /**
     * [Marketplace] Updates the recipient's settlement id in the transfer entity.
     *
     *  When the transactions for the internal payments (payments triggered by the transfer
     *  from master merchant to the linked account) are settled, the settlement_id of those
     *  transactions will be updated for the transfer entity that initiated these payments.
     *
     * @param Base\PublicCollection $txns
     */
    protected function updateSettlementIdInTransfer(Base\PublicCollection $txns)
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
        // create settlement and update batch settlement entity in transaction
        $merchantSettler = new Merchant($merchant, $channel, $this->repo);

        $setlDetailAmounts = $merchantSettler->calculateSettlementDetailAmounts($setlTxns);

        list($setl, $bankTransferAtpt) = $this->repo->transaction(
            function() use (
                $merchantSettler,
                $setlTxns,
                $setlAmount,
                $setlFee,
                $setlApiFee,
                $tax,
                $setlDetailAmounts)
            {
                list($setl, $bankTransferAtpt) = $merchantSettler->settle(
                                                    $setlTxns,
                                                    $setlAmount,
                                                    $setlFee,
                                                    $setlApiFee,
                                                    $tax,
                                                    $this->setlTime,
                                                    $setlDetailAmounts);

                list($setl, $bankTransferAtpt) = $this->createAndupdateBatchEntities(
                                                    $setl,
                                                    $setlTxns->count(),
                                                    $bankTransferAtpt);

                return [$setl, $bankTransferAtpt];
            });

        return [$setl, $bankTransferAtpt];
    }

    protected function createAndupdateBatchEntities($setl, int $setlTxnsCount, $bankTransferAtpt): array
    {
        $this->createOrUpdateBatchFundTransferForEntity($setl, $setlTxnsCount);

        $bankTransferAtpt->batchFundTransfer()->associate($this->batchFundTransfer);

        $setl->batchFundTransfer()->associate($this->batchFundTransfer);

        $this->repo->saveOrFail($setl);

        $this->repo->saveOrFail($bankTransferAtpt);

        return [$setl, $bankTransferAtpt];
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
            '8ytYezIThlseJd', // Goalwise Non-TPV
            '7BfRNg10LH7N6T', // Goalwise TPV
            '8hXTLsmoM3F6PH', // Moneyview
        ];

        if (in_array($merchant->getId(), $skipMerchantIds, true) === true)
        {
            return false;
        }

        $shouldSettle = true;

        $today = Carbon::today(Timezone::IST);

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
