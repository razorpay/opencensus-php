<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response;

class Stork extends \RZP\Services\Stork
{
    /**
     * {@inheritDoc}
     */
    public function request(string $path, array $payload, int $timeoutMs = null): \WpOrg\Requests\Response
    {
        $res = new \WpOrg\Requests\Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }
}
