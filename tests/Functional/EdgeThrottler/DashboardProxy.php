<?php

namespace RZP\Tests\Functional\EdgeThrottler;

use GuzzleHttp\Psr7\Response;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use function GuzzleHttp\json_encode;


class DashboardProxy extends TestCase
{
    use RequestResponseFlowTrait;

    const RATE_LIMITS_REQUEST_CONTENT = [
        'key' => 'POST::123',
        'config' => [
            'bucket' => [
                'default' => [
                    'max_tokens'      => 10,
                    'refill_count'    => 10,
                    'refill_interval' => 10,
                ]
            ],
            'strict_consistency' => 1,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testGetServices()
    {
        $mockResponse = new Response(200, [], '
            {
                "next": null,
                "data": [{
                    "host": "host.docker.internal",
                    "id": "074f6a5d-468b-4dba-a534-9d80cdfe93eb",
                    "protocol": "http",
                    "name": "0xfffffff"
                }]
            }
        ');

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url' => '/edge/services',
            'method' => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/services', $req->getUri()->getPath());

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id'   => '074f6a5d-468b-4dba-a534-9d80cdfe93eb',
                    'name' => '0xfffffff',
                    'host' => 'host.docker.internal',
                ]
            ],
            'offset' => null,
        ]);
    }

    public function testGetRoutes()
    {
        $mockResponse = new Response(200, [], '
            {
                "next": "/routes?offset=123",
                "data": [{
                    "id": "9405289d-70a0-4b53-adfb-1429ad432fd5",
                    "path_handling": "v1",
                    "service": {
                        "id": "050a23b1-628e-4af8-96ef-80201ddd2a19"
                    },
                    "hosts": ["localhost"],
                    "name": "get-1",
                    "methods": null,
                    "paths": [
                        "\/get-1"
                    ]
                }]
            }
        ');

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url'    => '/edge/service/050a23b1-628e-4af8-96ef-80201ddd2a19/routes',
            'method' => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/services/050a23b1-628e-4af8-96ef-80201ddd2a19/routes', $req->getUri()->getPath());

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id'      => '9405289d-70a0-4b53-adfb-1429ad432fd5',
                    'name'    => 'get-1',
                    'methods' => null,
                    'paths'   => ['/get-1'],
                    'hosts'   => ['localhost']
                ]
            ],
            'offset' => '123',
        ]);
    }

    public function testCreateRuleOnRoute()
    {
        $responseArray = [
            'id'         => '9405289d-70a0-4b53-adfb-1429ad432fd5',
            'route'      => [
                'id' => '050a23b1-628e-4af8-96ef-80201ddd2a19',
            ],
            'rule'       => 'consumer_username::route_name',
            'rule_component_preference' => '{\"consumer_username\":false,\"route_name\":true}',
            'created_at' => 1621410238,
            'updated_at' => 1621410238,
            'enabled'    => true,
        ];

        $mockResponse = new Response(201, [], json_encode($responseArray));

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $forwardedContent = [
            'rule'    => 'consumer_username::route_name',
            'enabled' => true,
        ];

        $response = $this->sendRequest([
            'url'     => '/edge/rate_limiter/rule',
            'method'  => 'POST',
            'content' => [
                'route_id'     => 'test_route',
                'service_id'   => 'test_service_id',
                'service_name' => 'test_service_name',
                'context'      => 'test_support_ticket_link'
            ] + $forwardedContent
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/routes/test_route/rate-limit-rules', $req->getUri()->getPath());
        $this->assertSame(json_encode($forwardedContent), $req->getBody()->getContents());

        $response->assertCreated();
        $response->assertExactJson($responseArray);
    }

    public function testUpdateRuleOnService()
    {
        $responseArray = [
            'id'         => '9405289d-70a0-4b53-adfb-1429ad432fd5',
            'service'    => [
                'id' => '050a23b1-628e-4af8-96ef-80201ddd2a19',
            ],
            'rule'       => 'consumer_username::route_name',
            'rule_component_preference' => '{\"consumer_username\":false,\"route_name\":true}',
            'created_at' => 1621410238,
            'updated_at' => 1621410238,
            'enabled'    => true
        ];

        $mockResponse = new Response(200, [], json_encode($responseArray));

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $forwardedContent = [
            'enabled'  => true,
            'priority' => 10,
        ];

        $response = $this->sendRequest([
            'url' => '/edge/rate_limiter/rule/123',
            'method' => 'PATCH',
            'content' => [
                'rule'         => 'consumer_username::route_name',
                'service_id'   => 'test_service_id',
                'service_name' => 'test_service_name',
                'context'      => 'test_support_ticket_link'
            ] + $forwardedContent
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('PATCH', $req->getMethod());
        $this->assertSame('/services/test_service/rate-limit-rules/123', $req->getUri()->getPath());
        $this->assertSame(json_encode($forwardedContent), $req->getBody()->getContents());

        $response->assertOk();
        $response->assertExactJson($responseArray);
    }

    public function testListRuleOnRoute()
    {
        $mockResponse = new Response(200, [], '{
            "data": [{
                "id": "9405289d-70a0-4b53-adfb-1429ad432fd5",
                "rule": "consumer_username::route_name",
                "rule_component_preference": "{\"consumer_username\":false,\"route_name\":true}",
                "enabled": true,
                "service": {
                    "id": "050a23b1-628e-4af8-96ef-80201ddd2a19"
                },
                "created_at": 1621410238,
                "updated_at": 1621410238
            }],
            "offset": null
          }
        ');

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url' => '/edge/rate_limiter/rules?route_id=test_route',
            'method' => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/routes/test_route/rate-limit-rules', $req->getUri()->getPath());

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id'         => '9405289d-70a0-4b53-adfb-1429ad432fd5',
                    'service'    => [
                        'id' => '050a23b1-628e-4af8-96ef-80201ddd2a19',
                    ],
                    'rule' => 'consumer_username::route_name',
                    'rule_component_preference' => '{\"consumer_username\":false,\"route_name\":true}',
                    'created_at' => 1621410238,
                    'updated_at' => 1621410238,
                    'enabled'    => true
                ],
            ],
            'offset' => null
        ]);
    }

    public function testListRateLimitRules()
    {
        $mockResponse = new Response(200, [], '{
            "data": [{
                "id": "80a423aa-1178-4898-9213-11854e151d65",
                "rule": "request_header.318",
                "rule_component_preference": "{\"request_header\":true}",
                "enabled": true,
                "priority": 100,
                "route": null,
                "service": {
                    "id": "094d8793-a2d5-4468-a302-3042567d4d65"
                },
                "created_at": 1649080101,
                "updated_at": 1649080101
            }],
            "offset": null
          }
        ');

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url' => '/edge/rate_limiter/rules',
            'method' => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/routes/test_route/rate-limit-rules', $req->getUri()->getPath());

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id'         => '80a423aa-1178-4898-9213-11854e151d65',
                    'service'    => [
                        'id' => '094d8793-a2d5-4468-a302-3042567d4d65',
                    ],
                    'rule' => 'request_header.318',
                    'rule_component_preference' => '{\"request_header\":true}',
                    'created_at' => 1649080101,
                    'updated_at' => 1649080101,
                    'enabled'    => true,
                    'priority'   => 100,
                    'route'      => null,
                ],
            ],
            'offset' => null
        ]);

    }

    public function testDeleteRuleOnService()
    {
        $this->markTestSkipped();
        $mockResponse = new Response(204);

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url'     => '/edge/rate_limiter/rule/123',
            'method'  => 'DELETE',
            'content' => [
                'service_id' => 'test_service',
            ],
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('DELETE', $req->getMethod());
        $this->assertSame('/services/test_service/rate-limit-rules/123', $req->getUri()->getPath());

        $response->assertNoContent();
    }

    public function testCreateLimits()
    {
        $additionalData = [
            'created_at' => 1621410238,
            'updated_at' => 1621410238,
        ];

        $content = self::RATE_LIMITS_REQUEST_CONTENT;
        $content['rule'] = ['id' => "l1234"];

        // Extra properties for having better reference while approving request
        $extraContent = [
            'rule_id'      => 'test_rule_id',
            'rule_name'    => 'test_rule_name',
            'service_id'   => 'test_service_id',
            'service_name' => 'service_name',
            'context'      => 'test_support_ticket_link'
        ];

        $mockResponse = new Response(201, [], json_encode($content + $additionalData));

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url' => '/edge/rate_limiter/rule/l1234/limit',
            'method' => 'POST',
            'content' => self::RATE_LIMITS_REQUEST_CONTENT + $extraContent
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/rate-limits', $req->getUri()->getPath());
        $this->assertSame(json_encode($content), $req->getBody()->getContents());

        $response->assertCreated();
        $response->assertExactJson($content + $additionalData);
    }

    public function testUpdateLimits()
    {
        $additionalData = [
            'created_at' => 1621410238,
            'updated_at' => 1621410238,
        ];

        $mockResponse = new Response(200, [], json_encode(self::RATE_LIMITS_REQUEST_CONTENT + $additionalData));

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        // Extra properties for having better reference while approving request
        $extraContent = [
            'rule_id'      => 'test_rule_id',
            'rule_name'    => 'test_rule_name',
            'service_id'   => 'test_service_id',
            'service_name' => 'service_name',
            'context'      => 'test_support_ticket_link'
        ];

        $response = $this->sendRequest([
            'url' => '/edge/rate_limiter/limit/123',
            'method' => 'PATCH',
            'content' => self::RATE_LIMITS_REQUEST_CONTENT + $extraContent
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('PATCH', $req->getMethod());
        $this->assertSame('/rate-limits/123', $req->getUri()->getPath());

        $content = self::RATE_LIMITS_REQUEST_CONTENT;
        unset($content['key']);
        $this->assertSame(json_encode($content), $req->getBody()->getContents());

        $response->assertOk();
        $response->assertExactJson(self::RATE_LIMITS_REQUEST_CONTENT + $additionalData);
    }

    public function testListLimits()
    {
        $additionalData = [
            'created_at' => 1621410238,
            'updated_at' => 1621410238,
        ];

        $mockResponse = new Response(200, [], json_encode([
            'data' => [self::RATE_LIMITS_REQUEST_CONTENT + $additionalData],
            'next' => '/rate-limits?offset=aqwe+',
        ]));

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url'     => '/edge/rate_limiter/limits?offset=123+',
            'method'  => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/rate-limits', $req->getUri()->getPath());
        $this->assertSame('offset=123+', $req->getUri()->getQuery());

        $response->assertOk();
        $response->assertExactJson([
            'data'   => [self::RATE_LIMITS_REQUEST_CONTENT + $additionalData],
            'offset' => 'aqwe+',
        ]);

    }

    public function testDeleteLimit()
    {
        $mockResponse = new Response(204);

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url'     => '/edge/rate_limiter/limit/123',
            'method'  => 'DELETE',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('DELETE', $req->getMethod());
        $this->assertSame('/rate-limits/123', $req->getUri()->getPath());

        $response->assertNoContent();
    }

    public function testGetConsumerSuccess()
    {
        $mockResponse = new Response(200, [], '{
            "status_code": 200,
            "success": true,
            "data": {
                "id": "987d9062-1faf-4b49-b750-11b087bca7f1",
                "username": "JyNwL6yP1A6naO"
            },
          }'
        );

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url' => 'edge/consumers/JyNwL6yP1A6naO',
            'method' => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/consumers/JyNwL6yP1A6naO', $req->getUri()->getPath());

        $response->assertOk();
        $response->assertExactJson([
            'status_code' => 200,
            'success'     => true,
            'data'        => [
                'id'         => '987d9062-1faf-4b49-b750-11b087bca7f1',
                'username'   => 'JyNwL6yP1A6naO'
            ]
        ]);
    }

    public function testGetConsumerFailure()
    {
        $mockResponse = new Response(400, [], '{
            "status_code": 400,
            "success": false,
            "errors": [
                "Bad request",
                "Status Code: 400"
            ],
          }'
        );

        $httpClient = app('throttler_http_client');
        $httpClient->addResponse($mockResponse);

        $response = $this->sendRequest([
            'url' => 'edge/consumers/JyNwL6yP1A6na',
            'method' => 'GET',
        ]);

        $this->assertCount(1, $httpClient->getRequests());

        $req = $httpClient->getRequests()[0];

        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/consumers/JyNwL6yP1A6na', $req->getUri()->getPath());

        $response->assertOk();
        $response->assertExactJson([
            'status_code' => 400,
            'success'     => false,
            'errors'        => [
                'Bad request',
                'Status Code: 400'
            ]
        ]);
    }

    public function testRequestFailure()
    {
        try
        {
            $this->sendRequest([
                'url'     => '/edge/rate_limiter/limit/123',
                'method'  => 'DELETE',
            ]);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
        }
    }

}
