<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Transaction;
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
        (new Validator)->validateInput('update', $input);

        $this->trace->info(
            TraceCode::TRANSACTIONS_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];
        $transactionIds = $input['transaction_ids'] ?? [];
        $settledAt = $input['old_settled_at'] ?? null;

        unset($input['transaction_ids'], $input['merchant_ids'], $input['old_settled_at']);

        $attributes = [];

        foreach ($input as $key => $value)
        {
            $attributes[$key] = $value;
        }

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchant)
        {
            try
            {
                $txns[$merchantId] = $this->repo
                                          ->transactions
                                          ->updateAttributes($merchantId, $transactionIds, $settledAt);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
            'txns'      => $txns,
        ];

        $this->trace->info(
            TraceCode::TRANSACTIONS_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }
}
