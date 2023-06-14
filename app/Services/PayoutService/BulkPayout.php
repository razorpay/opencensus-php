<?php

namespace RZP\Services\PayoutService;

use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Payout\Entity;
use RZP\Http\Request\Requests;

class BulkPayout extends Base
{
    const BULK_PAYOUT_VALIDATE_URI = '/payouts/bulk/validate';

    const CREATE_BULK_PAYOUT_PAYOUT_SERVICE_URI = '/payouts/bulk';

    const BATCH_SUBMITTED_CRON_PAYOUT_SERVICE_URI = '/payouts/batch/process';

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

        $this->modifyNotesIfRequired($input);

        $this->trace->info(TraceCode::CREATE_BULK_PAYOUT_VIA_MICROSERVICE_REQUEST,
                           [
                               'input' => $input,
                           ]);

        // X_Creator_Id is user_id and this represents which user has uploaded the bulk payout.
        // X_Creator_Type represents type of X_Creator_Id. In case of bulk payouts it is user everytime
        $headers = [
            Passport::PASSPORT_JWT_V1     => $this->app['basicauth']->getPassportJwt($this->baseUrl),
            RequestHeader::X_Batch_Id     => $this->app['request']->header(RequestHeader::X_Batch_Id, null),
            RequestHeader::X_ENTITY_ID    => $this->app['request']->header(RequestHeader::X_ENTITY_ID, null),
            RequestHeader::X_Creator_Id   => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
            RequestHeader::X_Creator_Type => $this->app['request']->header(RequestHeader::X_Creator_Type, null)
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

    private function modifyNotesIfRequired(array &$input)
    {
        foreach($input as $key => $item)
        {
            if (empty($item[Entity::NOTES]) === true)
            {
                $this->trace->info(TraceCode::BULK_REQUEST_NOTES_INPUT,
                    [
                        Entity::NOTES => $item[Entity::NOTES],
                    ]);

                unset($input[$key][Entity::NOTES]);
            }
        }
    }

    public function initiateBatchSubmittedCronViaMicroservice()
    {
        $this->trace->info(TraceCode::INITIATE_BATCH_SUBMITTED_CRON_VIA_MICROSERVICE);

        $response = $this->makeRequestAndGetContent(
            [],
            self::BATCH_SUBMITTED_CRON_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::BATCH_SUBMITTED_CRON_VIA_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);
    }

}
