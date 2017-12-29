<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
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
        $oldSettledAt = $input['old_settled_at'] ?? null;

        $attributes = [];

        $attributesToUpdate = ['settled_at', 'channel'];

        foreach ($attributesToUpdate as $attr)
        {
            if (empty($input[$attr]) === false)
            {
                $attributes[$attr] = $input[$attr];
            }
        }

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchant)
        {
            try
            {
                $txns[$merchantId] = $this->repo
                                          ->transactions
                                          ->updateAttributes($merchantId, $transactionIds, $oldSettledAt);

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
