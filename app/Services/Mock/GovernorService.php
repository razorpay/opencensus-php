<?php

namespace RZP\Services\Mock;

use RZP\Services\GovernorService as BaseGovernorService;

class GovernorService extends BaseGovernorService
{
    public function sendRequest(array $requestSchema, $data, $source, $namespace = null, $getEntityIdentifier = null, array $queryParams = [])
    {
        return [
            'response_body' => [
                "status"    =>  false,
                "error"     => "SOME_AWESOME_ERROR"
            ],
            'response_code' => 400,
        ];
    }
    public function sendRequestV1(string $method, string $path, array $content)
    {
        return [
            [
                "id" => "cps",
                "name" => "cps",
            ],
            [
                "id" => "routingengine",
                "name" => "routingengine"
            ],
        ];
    }
}
