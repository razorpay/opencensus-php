<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response;

class MerchantRiskAlertClient extends \RZP\Services\MerchantRiskAlertClient
{
    /**
     * {@inheritDoc}
     */
    public function request(string $path, array $payload, int $timeoutMs = null)
    {
        $res = new \WpOrg\Requests\Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }
}
