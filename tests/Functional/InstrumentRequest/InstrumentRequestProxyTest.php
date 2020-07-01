<?php

namespace RZP\Tests\Functional;


use RZP\Services\TerminalsService;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Admin\Permission\Repository;
use RZP\Tests\Functional\Helpers\TerminalTrait;

class InstrumentRequestProxyTest extends TestCase
{
    use TerminalTrait;
    use RequestResponseFlowTrait;

    protected $terminalsServiceMock;

    const EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      = 'expected_path';
    const EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    = 'expected_method';
    const EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   = 'expected_request_to_terminals_service';
    const EXPECTED_REQUEST_HEADERS_TERMINALS_SERVICE   = 'expected_request_headers_terminals_service';
    const REQUEST                                      = 'request';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InstrumentRequestProxyTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }
    public function testInternalInstrumentAdminDashboardProxyMissingPermission()
    {
        // deleting all permissions that were seeded
        $permissionNames = [Name::VIEW_INTERNAL_INSTRUMENT_REQUESTS, Name::UPDATE_INTERNAL_INSTRUMENT_REQUESTS, Name::DELETE_INTERNAL_INSTRUMENT_REQUESTS];

        foreach($permissionNames as $name)
        {
            $permission = (new Repository)->findByOrgIdAndPermission(\RZP\Models\Admin\Org\Entity::RAZORPAY_ORG_ID, $name);

            $permission->deleteOrFail();

        }

        $requests = [
            [
                'url'      => '/internal_instrument_request/0123456789abcd',
                'method'   => 'get',
            ],
            [
                'url'      => '/internal_instrument_request/0123456789abcd',
                'method'   => 'patch',
                'content'  => [
                    'status' => 'pending'
                ]
            ],
            [
                'url'      => '/internal_instrument_request/0123456789abcd',
                'method'   => \Requests::DELETE,
                'content'  => [
                    'status' => 'pending',
                ],
            ],
            [
                'url'      => '/internal_instrument_request_fetch',
                'method'   => \Requests::POST,
            ],
            [
                'url'      => '/internal_instrument_request/?gateway=hitachi&merchant_id=a,b,c&method=cards&start_time=123&endTime=456&has_special_pricing_request=true',
                'method'   => 'patch',
                'content'  => [
                    'status' => 'pending',
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->mockTerminalsServiceSendRequest(null, 0);

        foreach($requests as $request)
        {
            $this->testData[__FUNCTION__]['request'] = $request;

            $this->startTest();
        }
    }

    public function testInternalInstrumentAdminDashboardProxy()
    {
        $this->ba->adminAuth();

        $testCases = [
            [
                self::REQUEST       => [
                    'url'       => '/instrument_request/razorx/admin',
                    'method'    => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/instrument_request/razorx/admin',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [
                self::REQUEST       => [
                    'url'      => '/internal_instrument_request/0123456789abcd',
                    'method'   => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request/0123456789abcd',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request/0123456789abcd',
                    'method'   => \Requests::PATCH,
                    'content'  => [
                        'status' => 'pending',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request/0123456789abcd',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  [
                    'status' => 'pending',
                ],
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request/0123456789abcd',
                    'method'   => \Requests::DELETE,
                    'content'  => [
                        'status' => 'pending',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request/0123456789abcd',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::DELETE,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request_fetch',
                    'method'   => \Requests::POST,
                    'content'  => [
                        'query' => 'hitachi&merchant_ids=a&method=cards&start_time=123&end_time=456&has_special_pricing_request=true',
                        'count' => '40',
                        'skip'  => '4',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request?hitachi&merchant_ids=a&method=cards&start_time=123&end_time=456&has_special_pricing_request=true&count=40&skip=4',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request/',
                    'method'   => \Requests::PATCH,
                    'content'  => [
                        'query'  => 'gateway=hitachi&merchant_ids=a,b,c&method=cards&start_time=123&endTime=456&has_special_pricing_request=true',
                        'body'   => [
                            'status' => 'pending',
                        ],
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request?gateway=hitachi&merchant_ids=a,b,c&method=cards&start_time=123&endTime=456&has_special_pricing_request=true',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'status' => 'pending',
                ],
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

            $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

            $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

                $this->assertArrayHasKey('X-Dashboard-Admin-Email', $additionalHeaders);

                $response = new \Requests_Response;

                $response->body = '
                       {
                        "data": {
                           "testKey": "testValue"
                        }
                    }';

                return $response;
            }, 1);

            $this->startTest();

        }
    }
}
