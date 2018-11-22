<?php

namespace RZP\tests\Functional\StatusCake;

use RZP\Jobs\DynamicNetBankingUrlUpdater;

class StatusCakeUpdaterTest extends DynamicNetBankingUrlUpdater
{
    public function __construct(string $mode = null)
    {
        parent::__construct('test');

        $this->redis->set('gateway:SBIN_netbanking_url', 'https://retail.onlinesbi.com/retail/login.htm');
    }

    public function makeRequestAndGetData($url, $requestHeaders, $content = [], $method = 'POST')
    {
        if ($method === 'POST')
        {
            $responseArray = [
                [
                    'TestID' => 3555258,
                    'Paused' => false,
                    'TestType' => "HTTP",
                    'WebsiteName' => "SBI_NB",
                    'WebsiteURL' => "qwtwrywy",
                    'CheckRate' => 900,
                    'ContactGroup' => ["128908"],
                    'Public' => 0,
                    'Status' => "Down",
                    'TestTags' => [
                        "issuer:SBIN",
                        "method:netbanking",
                    ],
                    'WebsiteHost' => "",
                    'NormalisedResponse' => 0,
                    'Uptime' => 0
                ]
            ];

            return $responseArray;
        }
        else if($method === 'PUT')
        {
            $responseArray = [
                'Success' => true,
                'Issues' => [],
                'Message' => "Test has been updated!"
            ];

            return $responseArray;
        }
        else
        {
            throw new \Exception('Invalid method passed');
        }
    }

    public function errorHandle()
    {
        parent::handle();

        throw new \Exception('Unknown Error Occured');
    }
}
