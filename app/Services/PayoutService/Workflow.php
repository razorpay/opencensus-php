<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;

class Workflow extends Base
{
    const WORKFLOW_PAYOUT_SERVICE_URI = '/payouts/payouts_internal/%s';

    // payout workflow service name for singleton class
    const PAYOUT_SERVICE_WORKFLOW = 'payout_service_workflow';

    const WORKFLOW_STATE_CREATE_CALLBACK_URI = '/workflow/state';

    const WORKFLOW_STATE_UPDATE_CALLBACK_URI = '/workflow/state/%s';

    /**
     * @param string      $payoutId
     * @param string|null $remarks
     *
     * @return array
     */
    public function approvePayoutViaMicroservice(string $payoutId, bool $queueIfLowBalance = true)
    {
        $input = [
            Payout\Entity::QUEUE_IF_LOW_BALANCE => $queueIfLowBalance
        ];

        $this->trace->info(TraceCode::PAYOUT_APPROVE_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        return $this->makeRequestAndGetContent(
            $input,
            sprintf(self::WORKFLOW_PAYOUT_SERVICE_URI, $payoutId) . "/approve/",
            Requests::POST,
            $headers
        );
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function createStateCallbackViaMicroservice(array $input)
    {

        $this->trace->info(TraceCode::CREATE_STATE_VIA_MICROSERVICE_REQUEST,
                           [
                               'input' => $input,
                           ]);

        $headers = [
            Passport::PASSPORT_JWT_V1           => $this->app['basicauth']->getPassportJwt($this->baseUrl),
            RequestHeader::X_Creator_Id         => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
            RequestHeader::X_RAZORPAY_ACCOUNT   => $this->app['request']->header(RequestHeader::X_RAZORPAY_ACCOUNT, null)
        ];

        return $this->makeRequestAndGetContent(
            $input,
            self::WORKFLOW_STATE_CREATE_CALLBACK_URI,
            Requests::POST,
            $headers
        );
    }


    /**
     * @param array $input
     *
     * @return array
     */
    public function updateStateCallbackViaMicroservice(string $id,array $input)
    {

        $this->trace->info(TraceCode::UPDATE_STATE_VIA_MICROSERVICE_REQUEST,
                           [
                               'input'  => $input,
                               'id'     => $id
                           ]);

        $headers = [
            Passport::PASSPORT_JWT_V1           => $this->app['basicauth']->getPassportJwt($this->baseUrl),
            RequestHeader::X_Creator_Id         => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
            RequestHeader::X_RAZORPAY_ACCOUNT   => $this->app['request']->header(RequestHeader::X_RAZORPAY_ACCOUNT, null)
        ];

        return $this->makeRequestAndGetContent(
            $input,
            sprintf(self::WORKFLOW_STATE_UPDATE_CALLBACK_URI, $id),
            Requests::PATCH,
            $headers
        );
    }

    /**
     * @param string      $payoutId
     * @param string|null $remarks
     *
     * @return array
     */
    public function rejectPayoutViaMicroservice(string $payoutId)
    {
        $input = [];

        $this->trace->info(TraceCode::PAYOUT_REJECT_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        return $this->makeRequestAndGetContent(
            $input,
            sprintf(self::WORKFLOW_PAYOUT_SERVICE_URI, $payoutId) . "/reject/",
            Requests::POST,
            $headers
        );
    }
}
