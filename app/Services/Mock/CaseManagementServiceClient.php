<?php

namespace RZP\Services\Mock;

use RZP\Constants\Table;
use \WpOrg\Requests\Response;

class CaseManagementServiceClient
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

    public function forwardToCaseManagerService()
    {
        return [];
    }
}
