<?php

namespace RZP\Services\PayoutService;

use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;

class BulkPayout extends Base
{
    const BULK_PAYOUT_VALIDATE_URI = '/payouts/bulk/validate';

    const CREATE_BULK_PAYOUT_PAYOUT_SERVICE_URI = '/payouts/bulk';

    const PAYOUT_SERVICE_BULK_PAYOUTS = 'payout_service_bulk_payouts';

    /**
     * This function acts as the handler to call payout service to validate a payouts bulk file
     * @param array $input
     * @return array
     */
    public function validateBulkPayoutViaMicroservice(array $input): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_BULK_VALIDATE_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]
        );

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        return $this->makeRequestAndGetContent(
            $input,
            self::BULK_PAYOUT_VALIDATE_URI,
            Requests::POST,
            $headers
        );
    }

    /**
     * @param array  $input
     *
     */
    public function createBulkPayoutViaMicroservice(array $input)
    {
        $this->trace->info(TraceCode::CREATE_BULK_PAYOUT_VIA_MICROSERVICE_REQUEST,
                           [
                               'input' => $input,
                           ]);

        $headers = [
            RequestHeader::X_Batch_Id  => $this->app['request']->header(RequestHeader::X_Batch_Id, null),
            RequestHeader::X_ENTITY_ID => $this->app['request']->header(RequestHeader::X_ENTITY_ID, null)
        ];

        $response = $this->makeRequestAndGetContent(
            $input,
            self::CREATE_BULK_PAYOUT_PAYOUT_SERVICE_URI,
            Requests::POST,
            $headers
        );

        $this->trace->info(
            TraceCode::CREATE_BULK_PAYOUT_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);

        return $response;
    }
}
