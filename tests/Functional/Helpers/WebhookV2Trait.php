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
            'created_by'    => 'MerchantUser01',
            'subscriptions' => [
                [
                    'eventmeta'  => ['name' => 'payout.created',],
                ],
            ],
        ];
    }

    protected function getApiCreatePayloadForBanking(): array
    {
        return [
            'url'     => 'http://webhook.com/v1/dummy/route',
            'secret'  => 'secret',
            'events'  => [ 'payout.created' => '1' ],
        ];
    }

    protected function getStorkCreatePayloadForPrimary(): array
    {
        return [
            'url'           => 'http://webhook.com/v1/dummy/route',
            'created_by'    => 'MerchantUser01',
            'subscriptions' => [
                [
                    'eventmeta'  => ['name' => 'payment.authorized',],
                ],
            ],
        ];
    }

    protected function getApiCreatePayloadForPrimary(): array
    {
        return [
            'url'     => 'http://webhook.com/v1/dummy/route',
            'events'  => [ 'payment.authorized'  => '1' ],
        ];
    }

    protected function getStorkCreateResponseBodyForBanking(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'created_by'    => 'MerchantUser01',
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

    protected function getApiCreateResponseBodyForBanking(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'created_by'    => 'MerchantUser01',
            'owner_type'    => 'merchant',
            'context'       =>  [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route',
            'events'        => [ 'payout.created'  => true ],
        ];
    }

    protected function getStorkCreateResponseBodyForPrimary(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'owner_id'      => '10000000000000',
            'created_by'    => 'MerchantUser01',
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

    protected function getApiCreateResponseBodyForPrimary(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'created_by'    => 'MerchantUser01',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       =>  [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'events'        => [ 'payment.authorized'  => true ],
        ];
    }

    protected function getStorkCreateResponseBodyForOauth(): array
    {
        return [
            'id'            => 'EZ4ezgl4124qKu',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'owner_id'      => '10000000000App',
            'created_by'    => 'MerchantUser01',
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

    protected function getApiCreateResponseBodyForOauth(): array
    {
        return [
            'id'             => 'EZ4ezgl4124qKu',
            'created_at'     => '2020-04-01T03:32:10Z',
            'service'        => 'api-test',
            'owner_id'       => '10000000000App',
            'owner_type'     => 'application',
            'context'        => [],
            'disabled_at'    => '1970-01-01T00:00:00Z',
            'url'            => 'http://webhook.com/v1/dummy/route',
            'events'         => [ 'payment.authorized'  => true ],
            'application_id' => '10000000000App'
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

    protected function getApiGetResponseBody(): array
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
            'events'        => [ 'payout.created' => true ],
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

    protected function getApiGetResponseBodyWithSecret(): array
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
            'events'        => [ 'payout.created' => true ],
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

    protected function getStorkListPayloadForApplication(): array
    {
        return [
            'service'    => 'api-test',
            'owner_id'   => '10000000000App',
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

    protected function getStorkListResponseBodyForApplication(): array
    {
        return  [
            [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => 'rx-test',
                'owner_id'      => '10000000000App',
                'owner_type'    => 'application',
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
                'owner_id'      => '10000000000App',
                'owner_type'    => 'application',
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

    protected function getApiListResponseBody(): array
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
                'events'        => [ 'payout.created' => true ],
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
                'events'        => [ 'payout.created' => true ],
            ],
        ];
    }

    protected function getApiListResponseBodyForApplication()
    {
        return  [
            [
                'id'             => 'EZ4ezgl4124qKu',
                'created_at'     => '2020-04-01T03:32:10Z',
                'service'        => 'rx-test',
                'owner_id'       => '10000000000App',
                'owner_type'     => 'application',
                'context'        => [],
                'disabled_at'    => '1970-01-01T00:00:00Z',
                'url'            => 'http://webhook.com/v1/dummy/route',
                'events'         => [ 'payout.created' => true ],
                'application_id' => '10000000000App',
            ],
            [
                'id'             => 'EZ4ezg241a4qKu',
                'created_at'     => '2020-04-01T03:32:10Z',
                'service'        => 'rx-test',
                'owner_id'       => '10000000000App',
                'owner_type'     => 'application',
                'context'        => [],
                'disabled_at'    => '1970-01-01T00:00:00Z',
                'url'            => 'http://webhook.com/v1/dummy/route',
                'events'         => [ 'payout.created' => true ],
                'application_id' => '10000000000App',
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

    protected function getApiListResponseBodyWithSecret(): array
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
                'events'        => [ 'payout.created' => true ],
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
                'events'        => [ 'payout.created' => true ],
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

    protected function getApiUpdatePayloadForBanking(): array
    {
        return [
            'id'            => 'bankingWebhookId',
            'url'           => 'http://webhook.com/v1/dummy/route',
            'secret'        => 'secret',
            'events'        => [ 'payout.processed' => '1' ],
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

    protected function getApiUpdatePayloadForPrimary(): array
    {
        return [
            'id'            => 'primaryWebhookId',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'events'        => [ 'payment.failed' => '1' ],
        ];
    }

    protected function getApiUpdatePayloadForOauth(): array
    {
        return [
            'id'             => 'primaryWebhookId',
            'url'            => 'http://webhook.com/v1/dummy/route'  ,
            'events'         => [ 'payment.failed' => '1' ],
            'application_id' => '10000000000App'
        ];
    }

    protected function getStorkUpdateResponseBodyForBanking(): array
    {
        return [
            'id'            => 'bankingWebhookId',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'created_by'    => 'MerchantUser01',
            'updated_by'    => 'MerchantUser01',
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

    protected function getApiUpdateResponseBodyForBanking(): array
    {
        return [
            'id'            => 'bankingWebhookId',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'rx-test',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'created_by'    => 'MerchantUser01',
            'updated_by'    => 'MerchantUser01',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'events'        => [ 'payout.processed' => true ],
        ];
    }

    protected function getStorkUpdateResponseBodyForPrimary(): array
    {
        return [
            'id'            => 'primaryWebhookId',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'created_by'    => 'MerchantUser01',
            'updated_by'    => 'MerchantUser01',
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

    protected function getApiUpdateResponseBodyForPrimary(): array
    {
        return [
            'id'            => 'primaryWebhookId',
            'created_at'    => '2020-04-01T03:32:10Z',
            'service'       => 'api-test',
            'created_by'    => 'MerchantUser01',
            'updated_by'    => 'MerchantUser01',
            'owner_id'      => '10000000000000',
            'owner_type'    => 'merchant',
            'context'       => [],
            'disabled_at'   => '1970-01-01T00:00:00Z',
            'url'           => 'http://webhook.com/v1/dummy/route'  ,
            'events'        => [ 'payment.failed' => true ],
        ];
    }

    protected function convertAllToUnixTimestamp(array $webhook)
    {
        $webhook['created_at'] = strtotime($webhook['created_at']);
        $webhook['disabled_at'] = strtotime($webhook['disabled_at']);
        return $webhook;
    }
}
