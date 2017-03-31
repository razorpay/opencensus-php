<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Base\RuntimeManager;
use RZP\Constants\Mode;
use RZP\Dashboard\Dashboard;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Attempt\Entity as Attempt;
use RZP\Models\FileStore;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

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
            list($setlTxns, $setlAmount, $setlFee, $setlApiFee, $serviceTax, $setlGatewayFee) =
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
                $merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $serviceTax);

            $txnsSettledCount += $setlTxns->count();

            $settlements->push($setl);

            $setlAttempts->push($bankTransferAtpt);
        }

        return [$settlements, $txnsSettledCount, $setlAttempts];
    }

    protected function getSettlementAmountsForMerchant($txns, & $i, $txnsCount, $merchant): array
    {
        $setlAmount = $setlGatewayFee = $setlApiFee = 0;
        $setlFee = $serviceTax = 0;

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
            $serviceTax     += $txn->getServiceTax();

            $setlTxns->push($txn);
            $i++;
        }

        return [$setlTxns, $setlAmount, $setlFee, $setlApiFee, $serviceTax, $setlGatewayFee];
    }

    protected function settleForMerchant(
        $merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $serviceTax): array
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
                $serviceTax,
                $setlDetailAmounts)
            {
                list($setl, $bankTransferAtpt) = $merchantSettler->settle(
                                                    $setlTxns,
                                                    $setlAmount,
                                                    $setlFee,
                                                    $setlApiFee,
                                                    $serviceTax,
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

        $this->repo->saveOrFail($setl);

        $this->repo->saveOrFail($bankTransferAtpt);

        return [$setl, $bankTransferAtpt];
    }

    protected function updateValuesForAttempts(
        $setlAttempts,
        FileStore\Creator $txtFileEntity,
        FileStore\Creator $excelFileEntity)
    {
        $txtFileId = ($txtFileEntity->get())['id'];
        $excelFileId = ($excelFileEntity->get())['id'];

        return $this->repo->fund_transfer_attempt->updateFileIds(
            $setlAttempts,
            [
                Attempt::TXT_FILE_ID    => FileStore\Entity::verifyIdAndSilentlyStripSign($txtFileId),
                Attempt::EXCEL_FILE_ID  => FileStore\Entity::verifyIdAndSilentlyStripSign($excelFileId),
            ]);
    }

    /**
     * Settlement is done only bank account change is not recent as we need some
     * time till beneficiary is updated in kotak
     */
    protected function shouldSettle($merchant): bool
    {
        $shouldSettle = true;

        $today = Carbon::today('Asia/Kolkata');

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        if ($merchant->bankAccount === null)
        {
            throw new Exception\LogicException(
                'No bank account mapped for merchant settlement',
                null,
                ['merchant_id' => $merchant->getId()]);
        }

        if (($this->mode !== Mode::TEST) and
            ($merchant->bankAccount->getCreatedAt() > $lastWorkingDay->timestamp))
        {
            $shouldSettle = false;
        }

        return $shouldSettle;
    }

    protected function traceSetlInitiating($channel)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y H:i:s');

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
        $e = new SettlementFailureException($channel, null, $e);

        $this->failureNotification($e);

        $this->trace->critical($traceCode);

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