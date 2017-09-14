<?php

namespace RZP\Services\Mock;

use RZP\Services\HarvesterClient as BaseHarvesterClient;
use Requests_Response as Response;

class HarvesterClient extends BaseHarvesterClient
{
    protected function getResponse($request)
    {
        $response = new Response();

        $response->url = $this->queryPath;
        $response->headers = ['Content-Type' => 'application/json'];
        $response->status_code = 200;
        $response->success = true;

        $response->content = [
            'current_balance'    =>  100000,
            'last_transaction'    =>  '5',
            'recent_payments'    =>  [
                '7rMI17mH90KM3s',
                '7rMGib0wzmzkqh',
                '7rM0CFmUuC4lWt',
                '7rL6nJNxqirI2R',
                '7rL5o3TvqszTfT',
                '7rKpRzW5mOq9nQ',
                '7rKazXz1GDa5Vh',
                '7rKQ6gdIdxusPd',
                '7rK2dxmi1iXedH',
                '7rJwq8TPBr1FJ6'
            ],
            'recent_refunds'    =>  [
                '3'
            ],
            'recent_settlements'    =>  [
                '4ODUKoocS5vQOS',
                '4MdLq90J0jYi1X',
                '4LRkTrbuGPJvph',
                '4Ig0d9Amj76DfI',
                '4H5sAosU7n9HeE',
                '4FuGovm2rTbUBJ',
                '4D8WwfX3HKaD2D',
                '4CjzpFmVMePdH0',
                '4AMn6Hsd0SkL5l',
                '48meXIfOBZvIMy'
            ],
            'successful_transactions'    =>  212,
            'tota_payments'    =>  212,
            'total_refunds'    =>  1,
            'total_settlements'    =>  35,
            'total_volume'    =>  2354775,
            'transaction_types'    =>  [
                [
                    'doc_count'    =>  75,
                    'key'    =>  'netbanking'
                ],
                [
                    'doc_count'    =>  64,
                    'key'    =>  'card'
                ],
                [
                    'doc_count'    =>  47,
                    'key'    =>  'wallet'
                ],
                [
                    'doc_count'    =>  26,
                    'key'    =>  'upi'
                ]
            ]
        ];

        return $response;
    }
}
