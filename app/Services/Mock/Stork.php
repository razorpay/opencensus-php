<?php

namespace RZP\Services\Mock;

use Requests_Response;

class Stork extends \RZP\Services\Stork
{
    /**
     * {@inheritDoc}
     */
    public function request(string $path, array $payload): Requests_Response
    {
        $res = new Requests_Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }
}
