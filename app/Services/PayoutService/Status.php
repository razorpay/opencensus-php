<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class Status extends Base
{

    const UPDATE_PAYOUT_STATUS_WITH_FTS = '/payouts/update_payouts_with_fts';

    // payout status service name for singleton class
    const PAYOUT_SERVICE_STATUS = 'payout_service_status';

    public function updatePayoutStatusViaFTS($payoutId,
                                             string $status,
                                             string $failureReason = null,
                                             string $bankStatusCode = null)
    {
        $request = [
            'id'                => $payoutId,
            'status'            => $status,
            'failure_reason'    => $failureReason,
            'bank_status_code'  => $bankStatusCode
        ];

        $this->trace->info(TraceCode::PAYOUT_STATUS_UPDATE_FROM_FTS_REQUEST,
            $request);

        $response = $this->makeRequestAndGetContent(
            $request,
            self::UPDATE_PAYOUT_STATUS_WITH_FTS,
            Requests::PATCH);

        return $response;
    }
}
