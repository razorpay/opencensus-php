<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function bulkReconcile(array $input, string $channel): array
    {
        $this->trace->info(TraceCode::FTA_BULK_RECONCILE_REQUEST, $input);

        (new Validator)->validateInput('bulk_reconcile', $input);

        $summary = (new BulkRecon($input, $channel))->process();

        return $summary;
    }

    public function bulkUpdate(array $input)
    {
        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_BULK_UPDATE_REQUEST,
            $input);

        (new Validator)->validateInput('bulk_update', $input);

        $fundTransferAttempts = $this->repo
                                     ->fund_transfer_attempt
                                     ->findMany($input['ids']);

        unset($input['ids']);

        $sourceIds = [];

        foreach ($fundTransferAttempts as $fundTransferAttempt)
        {
            $fundTransferAttempt->fill($input);

            $this->repo->saveOrFail($fundTransferAttempt);

            if (($fundTransferAttempt->isLatest() === true) and
                (isset($input['status']) === true))
            {
                $source = $fundTransferAttempt->source;

                $source->setStatus($input['status']);

                $this->repo->saveOrFail($source);

                $sourceIds[] = $source->getId();
            }
        }

        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_UPDATED,
            [
                'ids'        => $fundTransferAttempts->getIds(),
                'source_ids' => $sourceIds,
                'input'      => $input,
            ]);

        return $fundTransferAttempts->getPublicIds();
    }
}
