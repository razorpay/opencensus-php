<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Refund;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Report\Types\BasicEntityReport;

class Service extends Base\Service
{
    public function getTransactionRecords($input)
    {
        $txns = $this->repo->transaction->fetch($input, $this->merchant->getKey());

        return $txns->toArrayPublic();
    }

    public function getTransactionRecordById($id)
    {
        return $this->repo->transaction->fetchAndReturnPublicArray($id, $this->merchant);
    }

    public function settlementFixer()
    {
        return $this->repo->transaction(function()
        {
            return (new BugFixer)->settlementFixerInTxn();
        });
    }

    public function getReport($input)
    {
        $report = new BasicEntityReport(Constants\Entity::TRANSACTION);

        return $report->getReport($input);
    }

    public function createFeeBreakupForTransaction($input)
    {
        return (new Transaction\DataMigration())->createFeeBreakupForTransaction($input);
    }

    public function getEntityTransaction($entity, $id)
    {
        if ($entity === Constants\Entity::PAYMENT)
        {
            Payment\Entity::verifyIdAndStripSign($id);
        }
        else if ($entity === Constants\Entity::REFUND)
        {
            Refund\Entity::verifyIdAndStripSign($id);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                "invalid entity, entity should be either payment or refund");
        }

        $txn = $this->repo->transaction->findByEntityId($id, $this->merchant, true);

        return $txn->toArrayPublic();
    }

    public function updateMultipleTransactions(array $input)
    {
        (new Validator())->validateInput('unsettled_txns_channel_update', $input);

        $channel    = $input['channel'];

        $merchantId = $input['merchant_id'];

        return (new Transaction\BulkUpdate)->updateMultipleTransactions($merchantId, $channel);
    }

    public function markTransactionPostpaid($input)
    {
        $this->trace->info(
            TraceCode::TRANSACTIONS_TO_POSTPAID_INPUT,
            $input);

        $transactionIds = $input['transaction_ids'];

        $successIds = [];

        $failedIds = [];

        $transactions = $this->repo->transaction->fetchMultipleTransactionsFromIds($transactionIds);
        $transactionCore = (new Transaction\Core);

        foreach ($transactions as $transaction)
        {
            try
            {
                $transactionCore->markTransactionPostpaid($transaction);
                $successIds[] = $transaction->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::TRANSACTIONS_TO_POSTPAID_FAILED,
                    ['transaction_id' => $transaction->getId()]
                );

                $failedIds[] = $transaction->getId();
            }
        }

        $response = [
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
        ];

        $this->trace->info(
            TraceCode::TRANSACTIONS_TO_POSTPAID_RESPONSE,
            $response);

        return $response;
    }

    public function transactionsBulkUpdateBalanceId(array $input)
    {
        $merchantIds = $input['merchant_ids'] ?? [];

        $merchantIdLimit = (int) ($input['merchant_limit'] ?? 5);

        $limit = (int) ($input['limit'] ?? 10000);

        if (empty($merchantIds))
        {
            $transactionMerchants = $this->repo->transaction->getMerchantIdsWhereBalanceIdIsNull($merchantIdLimit);

            $merchantIds = $transactionMerchants->pluck(Entity::MERCHANT_ID)
                                                ->toArray();
        }

        if (empty($merchantIds) === true)
        {
            return [];
        }

        $merchants = $this->repo->merchant->findMany($merchantIds);

        if (count($merchants) === 0 )
        {
            return [];
        }

        $this->trace->info(
            TraceCode::TRANSACTIONS_BULK_UPDATE_BALANCE_ID_REQUEST,
            [
                'merchant_ids' => $merchantIds,
            ]);

        $count = 0;
        $failedIds = 0;
        $merchantUpdates = [];

        foreach ($merchants as $merchant)
        {
            try
            {
                $balanceId = $merchant->balance->getId();
                $merchantId = $merchant->getId();

                $tempCount = $this->repo->transaction->bulkUpdateBalanceId($merchantId, $balanceId, $limit);

                $count += $tempCount;

                $merchantUpdates[] = [$merchantId => $tempCount];
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TRANSACTIONS_BULK_UPDATE_BALANCE_ID_ERROR,
                    [
                        'id' => $merchant->getId(),
                    ]);

                $failedIds[] = $merchant->getId();
            }
        }

        return [
            'merchant_ids'     => $merchantIds,
            'count'            => $count,
            'failed_ids'       => $failedIds,
            'merchant_updates' => $merchantUpdates,
        ];
    }
}
