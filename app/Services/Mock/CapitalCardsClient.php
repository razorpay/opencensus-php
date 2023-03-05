<?php

namespace RZP\Services\Mock;

use RZP\Exception;
use RZP\Services\CapitalCardsClient as BaseCapitalCardsClient;

class CapitalCardsClient extends BaseCapitalCardsClient
{
    public function getCorpCardAccountDetails(array $request)
    {
        if (empty($request['balance_id']) === true)
        {
            throw new Exception\InvalidArgumentException(
                'balance_id is mandatory');
        }

        $response = [
            [
                'entity_id'      => 'qaghsgatyuqsyl',
                'account_number' => '19823456789102'
            ]
        ];

        return $response;
    }
}
