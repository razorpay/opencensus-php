<?php

namespace RZP\Models\Transaction;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class BulkUpdate extends Base\Core
{
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

        s($attributes);

        $successCount = $failedCount = 0;

        $failedIds = [];

        $txns = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $txns[$merchantId] = $this->repo
                                          ->transactions
                                          ->updateAttributes($merchantId, $transactionIds, $oldSettledAt, $attributes);

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
