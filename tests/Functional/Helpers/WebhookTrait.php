<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Jobs;
use RZP\Models\Merchant\Webhook\Inferno;
use Http\Discovery\MessageFactoryDiscovery;

trait WebhookTrait
{
    protected function createInfernoMock(array $withMethods = ['fire']): Inferno
    {
        $infernoMock = $this->getMockBuilder(Inferno::class)
                            ->setMethods($withMethods)
                            ->getMock();

        $this->app->instance('webhook.inferno', $infernoMock);

        return $infernoMock;
    }

    /**
     * Sets mocked inferno instance expectations for fire() method.
     *
     * @param Inferno $infernoMock
     * @param array   $testDataKeys
     */
    protected function setInfernoExpectations(array $testDataKeys, Inferno $infernoMock = null)
    {
        $infernoMock = $infernoMock ?? $this->createInfernoMock();

        $times = 0;

        $with = [];
        // 1st argument of fire method is a job class of type Jobs\Webhook
        $arg1Callback = function ($job)
        {
            return $job instanceof Jobs\WebHook;
        };

        //
        // Iterates over the test data keys and sets callback to assert the 2nd argument
        // of every consecutive call of inferno's fire() method.
        //
        foreach ($testDataKeys as $testDataKey)
        {
            $times = $times + 1;

            $arg2Callback = function ($actualWebhook) use ($testDataKey)
            {
                $expectedEvent = $this->testData[$testDataKey];
                $actualEvent   = json_decode($actualWebhook['event'], true);

                $this->assertArraySelectiveEquals($expectedEvent, $actualEvent);

                return true;
            };

            $with[] = [$this->callback($arg1Callback), $this->callback($arg2Callback)];
        }

        $infernoMock->expects($this->exactly($times))
                    ->method('fire')
                    ->withConsecutive(...$with);
    }

    protected function setInfernoMockClient()
    {
        $this->app['webhook.inferno']->setClient($this->app['httplug']->driver('mock'));

        $messageFactory = MessageFactoryDiscovery::find();

        $client = $this->app['webhook.inferno']->getClient();

        $response = $messageFactory->createResponse(200);
        $client->addResponse($response);

        return $client;
    }

    protected function verifyRequestsData($client, array $requestsDataKeys)
    {
        $requests = $client->getRequests();

        foreach ($requests as $index => $request)
        {
            $expectedData = $this->testData[$requestsDataKeys[$index]];

            $this->assertEquals(['Razorpay-Webhook/v1'], $request->getHeader('User-Agent'));
            $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
            $this->assertEquals($expectedData['url'], (string) $request->getUri());

            $body = (string) $request->getBody();
            $decodedBody = json_decode($body, true);

            $this->assertArraySelectiveEquals($expectedData['content'], $decodedBody);
        }
    }

    protected function mockStorkService()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Stork')->makePartial();

        $this->app->instance('stork_service', $this->storkMock);
    }

    protected function mockServiceStorkRequest($closure)
    {
        return $this->storkMock->shouldReceive('request')->andReturnUsing($closure);
    }

    protected function getStorkListResponseEmpty()
    {
        $res = new \Requests_Response();
        $res->body = json_encode([]);

        return $res;
    }

    protected function getStorkListResponse()
    {
        $res = new \Requests_Response();

        $body =  [
            'webhooks' => [
                [
                    'id'            => 'EZ4ezgl4124qKu',
                    'created_at'    => '2020-04-01T03:32:10Z',
                    'service'       => 'rx-live',
                    'owner_id'      => '10000000000000',
                    'owner_type'    => 'merchant',
                    'context'       => '{"mode":"test"}',
                    'disabled_at'   => '1970-01-01T00:00:00Z',
                    'url'           => 'http://webhook.com/v1/dummy/route',
                    'subscriptions' => [
                        [
                            'id'         => 'EZ4ezhzqgKNjxI',
                            'created_at' => '2020-04-01T03:32:10Z',
                            'eventmeta'  => ['name' => 'payout.created',],
                        ],
                    ],
                ],
            ]
        ];

        $res->body = json_encode($body);

        return $res;
    }

    protected function getStorkGetResponse()
    {
        $res = new \Requests_Response();

        $body =  [
            'webhook' => [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-live',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => '{"mode":"test"}',
                'disabled_at'   => '1970-01-01T00:00:00Z',
                'url'           => 'http://webhook.com/v1/dummy/route',
                'subscriptions' => [
                    [
                        'id'         => 'EZ4ezhzqgKNjxI',
                        'created_at' => '2020-04-01T03:32:10Z',
                        'eventmeta'  => ['name' => 'payout.created',],
                    ],
                ],
            ],
        ];

        $res->body = json_encode($body);

        return $res;
    }

    protected function getStorkGetResponseProductPrimary()
    {
        $res = new \Requests_Response();

        $body =  [
            'webhook' => [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'api-live',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => '{"mode":"test"}',
                'disabled_at'   => '1970-01-01T00:00:00Z',
                'url'           => 'http://webhook.com',
                'subscriptions' => [
                    [
                        'id'         => 'EZ4ezhzqgKNjxI',
                        'created_at' => '2020-04-01T03:32:10Z',
                        'eventmeta'  => ['name' => 'payment.authorized',],
                    ],
                ],
            ],
        ];

        $res->body = json_encode($body);

        return $res;
    }

    protected function getStorkCreateResponse()
    {
        $res = new \Requests_Response();
        $body =  [
            'webhook' => [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-live',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => '{"mode":"test"}',
                'disabled_at'   => '1970-01-01T00:00:00Z',
                'url'           => 'http://webhook.com/v1/dummy/route'  ,
                'subscriptions' => [
                    [
                        'id'         => 'EZ4ezhzqgKNjxI',
                        'created_at' => '2020-04-01T03:32:10Z',
                        'eventmeta'  => ['name' => 'payout.created',],
                    ],
                ],
            ]
        ];

        $res->body = json_encode($body);

        return $res;
    }

    protected function getStorkUpdateResponse()
    {
        $res = new \Requests_Response();
        $body =  [
            'webhook' => [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-live',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => '{"mode":"test"}',
                'disabled_at'   => '1970-01-01T00:00:00Z',
                'url'           => 'http://webhook.com/v1/dummy/route'  ,
                'subscriptions' => [
                    [
                        'id'         => 'EZ4ezhzqgKNjxI',
                        'created_at' => '2020-04-01T03:32:10Z',
                        'eventmeta'  => ['name' => 'payout.initiated',],
                    ],
                    [
                        'id'         => 'EZ4ezhzqgKNjxI',
                        'created_at' => '2020-04-01T03:32:10Z',
                        'eventmeta'  => ['name' => 'payout.reversed',],
                    ],
                ],
            ]
        ];

        $res->body = json_encode($body);

        return $res;
    }
}
