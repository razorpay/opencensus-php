<?php

namespace RZP\Services\Mock;

use Requests_Response;

class MerchantRiskAlertClient extends \RZP\Services\MerchantRiskAlertClient
{
    /**
     * {@inheritDoc}
     */
    public function request(string $path, array $payload, int $timeoutMs = null): Requests_Response
    {
        $res = new Requests_Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }
}
