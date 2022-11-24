<?php

namespace RZP\Tests\Functional\Helpers;

/**
 * @see Also see TestsWebhookEvents.
 */
trait WebhookTrait
{
    /**
     * @see Use TestsWebhookEvents::expectWebhookEvent if you just need to make assertions about webhook events.
     */
    protected function mockStorkService()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Mock\Stork')->makePartial();

        $this->app->instance('stork_service', $this->storkMock);
    }

    /**
     * @see Use TestsWebhookEvents::expectWebhookEvent if you just need to make assertions about webhook events.
     */
    protected function mockServiceStorkRequest($closure)
    {
        return $this->storkMock->shouldReceive('request')->andReturnUsing($closure);
    }

    protected function getStorkListResponseEmpty()
    {
        $res = new \WpOrg\Requests\Response();
        $res->body = json_encode([]);

        return $res;
    }

    protected function getStorkListResponse()
    {
        $res = new \WpOrg\Requests\Response();

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
        $res = new \WpOrg\Requests\Response();

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
        $res = new \WpOrg\Requests\Response();

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

    protected function getStorkCreateResponse(string $service, string $url, array $events)
    {
        $res = new \WpOrg\Requests\Response();

        $subscriptions = [];
        foreach ($events as $event)
        {
            $subscriptions[] = ['eventmeta'  => ['name' => $event]];
        }

        $body =  [
            'webhook' => [
                'id'            => 'EZ4ezgl4124qKu',
                'created_at'    => '2020-04-01T03:32:10Z',
                'service'       => $service,
                'owner_id'      => '10000000000000',
                'owner_type'    => 'merchant',
                'context'       => '{"mode":"test"}',
                'disabled_at'   => '1970-01-01T00:00:00Z',
                'url'           => $url,
                'subscriptions' => $subscriptions,
            ]
        ];

        $res->body = json_encode($body);

        return $res;
    }

    protected function getStorkUpdateResponse()
    {
        $res = new \WpOrg\Requests\Response();
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
