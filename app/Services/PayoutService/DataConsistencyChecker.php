<?php

namespace RZP\Services\PayoutService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class DataConsistencyChecker extends Base
{
    const DATA_CONSISTENCY_CHECKER_CRON_PAYOUT_SERVICE_URI = '/payouts/consistency_checker';

    const PAYOUT_SERVICE_DATA_CONSISTENCY_CHECKER = 'payout_service_data_consistency_checker';

    public function initiateDataConsistencyChecker() : array
    {
        $this->trace->info(TraceCode::DATA_CONSISTENCY_CHECKER_VIA_MICROSERVICE_REQUEST);

        $response = $this->makeRequestAndGetContent(
            [],
            self::DATA_CONSISTENCY_CHECKER_CRON_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::DATA_CONSISTENCY_CHECKER_VIA_MICROSERVICE_RESPONSE,
            [
                'response' => $response,
            ]);

        return $response;
    }
}
