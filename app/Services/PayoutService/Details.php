<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class Details extends Base
{
    const UPDATE_PAYOUT_DETAILS_WITH_FTS = '/payouts/update_payouts_details_with_fts';

    // payout detail service name for singleton class
    const PAYOUT_SERVICE_DETAIL = 'payout_service_detail';

    public function updatePayoutDetailsViaFTS(Payout\Entity $payout, array $input = [])
    {
        $this->trace->info(TraceCode::PAYOUT_DETAILS_UPDATE_FROM_FTS_REQUEST,
            [
                'payout_id' => $payout->getId(),
                'input'     => $input,
            ]);

        $request = $input + [
                'source_id' => $payout->getId(),
            ];

        $response = $this->makeRequestAndGetContent(
            $request,
            self::UPDATE_PAYOUT_DETAILS_WITH_FTS,
            Requests::POST);

        return $response;
    }
}
