<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount\Provider;

class Service extends Base\Service
{
    public function bulkUpdate(array $input)
    {
        (new Validator)->validateInput('bulk_update', $input);

        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_BULK_UPDATE_REQUEST,
            $input);

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
