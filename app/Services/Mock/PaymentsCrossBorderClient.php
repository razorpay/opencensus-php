<?php

namespace RZP\Services\Mock;

class PaymentsCrossBorderClient
{
    /**
     * {@inheritDoc}
     */
    public function makeRequest(string $path, array $payload, int $timeoutMs = null)
    {
        $res = new \WpOrg\Requests\Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }

}
