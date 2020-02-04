<?php

namespace RZP\Services\Mock;

use RZP\Services\FreshdeskTicketClient as BaseFreshdeskTicketClient;

class FreshdeskTicketClient extends BaseFreshdeskTicketClient
{
    protected  function makeRequestAndGetStatus(string $method, string $url, string $auth, array $content)
    {
        return [
            'status' => 2,
            'id'     => 1,
        ];
    }
}
