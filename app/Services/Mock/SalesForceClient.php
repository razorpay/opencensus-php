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

    protected function createAndSendRequest(array $request)
    {
        return;
    }

    public function fetchAccessToken()
    {
        return '123';
    }
}
