<?php

namespace RZP\Services\Mock;

use RZP\Services\GovernorService as BaseGovernorService;

class GovernorService extends BaseGovernorService
{
    protected function sendRequest(string $method, string $url, array $auth, array $data, array $queryParams = [])
    {
        $url = $url . '?';

        foreach ($queryParams as $key => $value)
        {
            $url .= $key . '=' . $value . '&';
        }

        return [
            'response_body' => [
                "status"    =>  false,
                "error"     => "SOME_AWESOME_ERROR"
            ],
            'response_code' => 400,
        ];
    }
}
