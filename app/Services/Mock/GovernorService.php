<?php

namespace RZP\Services\Mock;

use RZP\Services\GovernorService as BaseGovernorService;

class GovernorService extends BaseGovernorService
{
    public function sendRequest(array $requestSchema, $data, $source, $namespace = null, $getEntityIdentifier = null, array $queryParams = [], $client_id = null, $namespace_id =null, $client = null, $rule_chain_id = null, $rule_group_id = null, $rule_id =null)
    {
        return [
            'response_body' => [
                "status"    =>  false,
                "error"     => "SOME_AWESOME_ERROR"
            ],
            'response_code' => 400,
        ];
    }
}
