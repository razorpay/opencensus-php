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

    public function fetchTcsData($input)
    {
        // Mock TCS data response
        return [
            'tcs_percent' => 5.0,
            'tcs_amount' => 500,
            'tcs_applicable' => true,
            'total_amount' => 10500
        ];
    }

}
