<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response as Response;

use RZP\Services\Harvester\HarvesterClient as BaseHarvesterClient;

class HarvesterClient extends BaseHarvesterClient
{
    protected function getResponse($request)
    {
        $response = new Response();

        $response->url = $this->queryPath;
        $response->headers = ['Content-Type' => 'application/json'];
        $response->status_code = 200;
        $response->success = true;

        $response->body = '{
                "recent_payments": [
                    {
                        "timestamp": 1503858600000,
                        "value": 0
                    },
                    {
                        "timestamp": 1499711400000,
                        "value": 0
                    },
                    {
                        "timestamp": 1498847400000,
                        "value": 0
                    },
                    {
                        "timestamp": 1501180200000,
                        "value": 0
                    },
                    {
                        "timestamp": 1504809000000,
                        "value": 400
                    },
                    {
                        "timestamp": 1498069800000,
                        "value": 0
                    },
                    {
                        "timestamp": 1503426600000,
                        "value": 0
                    },
                    {
                        "timestamp": 1497810600000,
                        "value": 0
                    },
                    {
                        "timestamp": 1503253800000,
                        "value": 100
                    },
                    {
                        "timestamp": 1505241000000,
                        "value": 200005134
                    },
                    {
                        "timestamp": 1497983400000,
                        "value": 0
                    },
                    {
                        "timestamp": 1500489000000,
                        "value": 0
                    },
                    {
                        "timestamp": 1502908200000,
                        "value": 0
                    },
                    {
                        "timestamp": 1499193000000,
                        "value": 0
                    },
                    {
                        "timestamp": 1497897000000,
                        "value": 0
                    },
                    {
                        "timestamp": 1498501800000,
                        "value": 0
                    }
                ]
            }';

        return $response;
    }

    public function getDataFromPinot($content, $timeout = 2)
    {
        $data = [
            'user_days_till_last_transaction' => 45,
            'merchant_lifetime_gmv'           => 100.0,
            'average_monthly_gmv'             => 10.0,
            'primary_product_used'            => 'payment_links',
            'ppc'                             => 1,
            'mtu'                             => false,
            'average_monthly_transactions'    => 3,
            'pg_only'                         => false,
            'pl_only'                         => true,
            'pp_only'                         => false,
            'merchants_id'                    => '10000000000000',
        ];

        return [$data];
    }
}
