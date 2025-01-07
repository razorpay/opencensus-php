<?php

namespace RZP\Services\Mock;

class CrossBorderImportServiceClient
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

    public function validateImportPayment($input)
    {
        return null;
    }


}
