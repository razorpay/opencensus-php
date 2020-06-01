<?php

namespace RZP\Tests\Functional\Helpers;

trait WebhookV2Trait
{
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

    protected function getStorkResponse(array $body)
    {
        $res = new \Requests_Response();

        $res->body = json_encode($body);

        return $res;
    }

    protected function getStorkCreatePayloadForBanking(): array
    {
        return [
            'url'           => 'http://webhook.com/v1/dummy/route',
            'secret'        => 'secret',
            'subscriptions' => [
                [
                    'eventmeta'  => ['name' => 'payout.created',],
                ],
            ],
        ];
    }

    protected function getStorkCreatePayloadForPrimary(): array
    {
        return [
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'eventmeta'  => ['name' => 'payment.authorized',],
                ],
            ],
        ];
    }

    protected function getStorkCreateResponseBodyForBanking(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       =>  [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payout.created',],
                ],
            ],
        ];
    }

    protected function getStorkCreateResponseBodyForPrimary(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       =>  [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payment.authorized',],
                ],
            ],
        ];
    }

    protected function getStorkCreateResponseBodyForOauth(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'owner_id'      => '10000000000App',
            'owner_type'    => 'application',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payment.authorized',],
                ],
            ],
        ];
    }

    protected function getStorkGetPayloadForBanking(): array
    {
        return [
            'webhook_id' => 'bankingWebhookId',
            'service'    => 'rx-test',
            'owner_id'   => '10000000000000',
        ];
    }

    protected function getStorkGetPayloadForPrimary(): array
    {
        return [
            'webhook_id' => 'primaryWebhookId',
            'service'    => 'api-test',
            'owner_id'   => '10000000000000',
        ];
    }

    protected function getStorkGetResponseBody(): array
    {
        return  [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route',
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payout.created',],
                ],
            ],
        ];
    }

    protected function getStorkGetResponseBodyWithSecret(): array
    {
        return  [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'secret'        => 'secret',
            'url'           => 'http://webhook.com/v1/dummy/route',
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payout.created',],
                ],
            ],
        ];
    }

    protected function getStorkListPayloadForBanking(): array
    {
        return [
            'service'    => 'rx-test',
            'owner_id'   => '10000000000000',
        ];
    }

    protected function getStorkListPayloadForPrimary(): array
    {
        return [
            'service'    => 'api-test',
            'owner_id'   => '10000000000000',
        ];
    }

    protected function getStorkListResponseBody(): array
    {
        return  [
            [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-test',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => [],
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
            [
                'id'            => 'EZ4ezg241a4qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-test',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => [],
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
    }

    protected function getStorkListResponseBodyWithSecret(): array
    {
        return  [
            [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-test',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => [],
                'secret'        => 'secret',
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
            [
                'id'            => 'EZ4ezg241a4qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-test',
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => [],
                'secret'        => 'secret',
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
    }

    protected function getStorkUpdatePayloadForBanking(): array
    {
        return [
            'id'            => 'bankingWebhookId',
            'url'           => 'http://webhook.com/v1/dummy/route',
            'secret'        => 'secret',
            'subscriptions' => [
                [
                    'eventmeta'  => ['name' => 'payout.processed',],
                ],
            ],
        ];
    }

    protected function getStorkUpdatePayloadForPrimary(): array
    {
        return [
            'id'            => 'primaryWebhookId',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'eventmeta'  => ['name' => 'payment.failed',],
                ],
            ],
        ];
    }

    protected function getStorkUpdateResponseBodyForBanking(): array
    {
        return [
            'id'            => 'bankingWebhookId',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payout.processed',],
                ],
            ],
        ];
    }

    protected function getStorkUpdateResponseBodyForPrimary(): array
    {
        return [
            'id'            => 'primaryWebhookId',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'subscriptions' => [
                [
                    'id'         => 'EZ4ezhzqgKNjxI',
                    'created_at' => '2020-04-01T03:32:10Z',
                    'eventmeta'  => ['name' => 'payment.failed',],
                ],
            ],
        ];
    }

    protected function convertAllToUnixTimestamp(array $webhook)
    {
        $webhook['created_at'] = strtotime($webhook['created_at']);
        $webhook['disabled_at'] = strtotime($webhook['disabled_at']);
        $webhook['subscriptions'] = array_map(function($v)
        {
            if (isset($v['created_at']) === true)
            {
                $v['created_at'] = strtotime($v['created_at']);
            }
            return $v;
        }, $webhook['subscriptions']);

        return $webhook;
    }
}
