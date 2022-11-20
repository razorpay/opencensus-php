<?php

namespace RZP\Services\Mock;

use RZP\Models\Merchant;
use RZP\Http\RequestHeader;
use RZP\Jobs\SalesforceRequestJob;


use RZP\Services\SalesForceClient as BaseSalesForceClient;

class SalesForceClient extends BaseSalesForceClient
{
    public function fetchAccountDetails( $input = '', $timeStamp = 0, $timeBased = false)
    {
        return $input;
    }

    protected function dispatchRequestJob($url, $payload, $traceCodeRequest, $traceCodeResponse, $traceCodeError)
    {
        return;
    }

    protected function makeRequestAndGetResponse(array $request)
    {
        return;
    }

    public function fetchAccessToken(bool $skipCache = false)
    {
        return '123';
    }

    public function sendEventToSalesForce(array $payload) {
        return;
    }

    public function sendLeadUpsertEventsToSalesforce(array $payload)
    {
        return;
    }

    public function sendPreSignupDetails(array $input, Merchant\Entity $merchant)
    {
        return;
    }

    public function getSalesforceDetailsForMerchantIDs(array $merchantIds): array
    {
        return [];
    }

    public function sendLeadStatusUpdate(array $payload, string $process)
    {
        return;
    }
}
