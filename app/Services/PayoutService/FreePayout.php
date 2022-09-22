<?php

namespace RZP\Services\PayoutService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

class FreePayout extends Base
{
    const UPDATE_FREE_PAYOUT_PAYOUTS_SERVICE_URI = '/admin/free_payout/';
    const MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE_URI = '/payouts/free_payout_migration';

    // update free payout attributes for singleton class
    const PAYOUT_SERVICE_FREE_PAYOUT = 'payout_service_free_payout';

    /**
     * @param string $id
     * @param array  $input
     *
     */
    public function updateFreePayoutAttributesViaMicroservice(string $id, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_FREE_PAYOUT_VIA_MICROSERVICE_REQUEST,
                           [
                               'input' => $input,
                           ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $input,
            self::UPDATE_FREE_PAYOUT_PAYOUTS_SERVICE_URI . $id,
            Requests::POST,
            $headers
        );

        $this->trace->info(
            TraceCode::UPDATE_FREE_PAYOUT_VIA_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);

        return $response;
    }

    // freePayoutMigrationForMicroservice is used for both migrating free payout
    // to payouts service and rolling back free payout from payouts service
    public function freePayoutMigrationForMicroservice(array $input)
    {
        $this->trace->info(TraceCode::MIGRATE_FREE_PAYOUT_COUNTER_AND_SETTINGS_REQUEST,
            [
                'input' => $input,
            ]);

        $response = $this->makeRequestAndGetContent(
            $input,
            self::MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::MIGRATE_FREE_PAYOUT_COUNTER_AND_SETTINGS_RESPONSE,
            [
                'payouts service response' => $response,
            ]);

        return $response;
    }
}
