<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class Workflow extends Base
{
    const WORKFLOW_PAYOUT_SERVICE_URI = '/payouts/payouts_internal/%s';

    // payout workflow service name for singleton class
    const PAYOUT_SERVICE_WORKFLOW = 'payout_service_workflow';

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
