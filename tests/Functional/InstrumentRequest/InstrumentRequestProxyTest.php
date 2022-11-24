<?php

namespace RZP\Tests\Functional;


use GuzzleHttp\Psr7\Response;
use RZP\Models\Admin\Admin\Admin\Entity;
use RZP\Services\TerminalsService;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Admin\Permission\Repository;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;

class InstrumentRequestProxyTest extends TestCase
{
    use TerminalTrait;
    use RequestResponseFlowTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;

    protected $terminalsServiceMock;

    const EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      = 'expected_path';
    const EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    = 'expected_method';
    const EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   = 'expected_request_to_terminals_service';
    const EXPECTED_REQUEST_HEADERS_TERMINALS_SERVICE   = 'expected_request_headers_terminals_service';
    const REQUEST                                      = 'request';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InstrumentRequestProxyTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }
    public function testInternalInstrumentAdminDashboardProxyMissingPermission()
    {
        // deleting all permissions that were seeded
        $permissionNames = ['view_internal_instrument_request', 'update_internal_instrument_request', 'delete_internal_instrument_request'];

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
            [
                'url'      => '/internal_instrument_request/?gateway=hitachi&merchant_id=a,b,c&method=cards&start_time=123&endTime=456&has_special_pricing_request=true',
                'method'   => 'post',
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

    public function testMerchantInstrumentKAMDashboardProxyMissingPermission()
    {
        // deleting all permissions that were seeded
        $permissionNames = ['update_merchant_instrument_request', 'view_merchant_instrument_request'];

        foreach($permissionNames as $name)
        {
            $permission = (new Repository)->findByOrgIdAndPermission(\RZP\Models\Admin\Org\Entity::RAZORPAY_ORG_ID, $name);

            $permission->deleteOrFail();

        }

        $requests = [
            [
                'type' => 'admin_proxy',
                'url'      => '/merchant_instrument_request',
                'method'   => \Requests::POST,
                'content'   =>  [
                    'instrument'    =>  'pg.cards.visa',
                    'special_pricing' => 'special_pricing_test'
                ]
            ],
            [
                'type' => 'admin_proxy',
                'url'      => '/merchant_instrument_request/mir_12341234',
                'method'   => \Requests::PATCH,
                'content'   =>  [
                    'status'    =>  'cancelled',
                ]
            ],
            [
                'type' => 'admin',
                'url'      => '/merchant_instrument_request_fetch',
                'method'   => \Requests::POST,
                'content'  => [
                    'merchant_ids' => ['a'],
                    'status'       => 'activated',
                ]
            ],
        ];

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->mockTerminalsServiceSendRequest(null, 0);

        foreach($requests as $request)
        {
            $this->testData[__FUNCTION__]['request'] = $request;

            if ($request['type'] === 'admin')
            {
                $this->ba->adminAuth();
            }
            else
            {
                $this->ba->adminProxyAuth();
            }

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
                    'url'      => '/internal_instrument_request/0123456789abcd/toggle_pause',
                    'method'   => \Requests::PATCH,
                    'content'  => [
                        'status' => 'paused',
                        'comment' => 'Merchant needs time to procure information',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request/0123456789abcd/toggle_pause',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  [
                    'status' => 'paused',
                    'comment' => 'Merchant needs time to procure information',
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
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  [],
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request_fetch',
                    'method'   => \Requests::POST,
                    'content'  => [
                        'merchant_ids' => ['testMID'],
                        'gateway'      => 'hitachi',
                        'method'       => 'cards',
                        'start_time'   => '123',
                        'end_time'     => '456',
                        'count'        => '40',
                        'skip'         => '4',
                        'has_special_pricing_request' => true
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'merchant_ids' => ['testMID'],
                    'gateway'      => 'hitachi',
                    'method'       => 'cards',
                    'start_time'   => '123',
                    'end_time'     => '456',
                    'count'        => 40,
                    'skip'         => 4,
                    'has_special_pricing_request' => true
                ],
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request/',
                    'method'   => \Requests::PATCH,
                    'content'  => [
                        'status' => 'pending',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request_v2',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'status' => 'pending',
                ],
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/internal_instrument_request',
                    'method'   => \Requests::POST,
                    'content'  => [
                        'query'  => 'gateway=hitachi&merchant_ids=a,b,c&method=cards&start_time=123&endTime=456&has_special_pricing_request=true',
                        'body'   => [
                            'status' => 'requested',
                            'internal_comments' => 'some comment1',
                        ],
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  [
                    'query'  => 'gateway=hitachi&merchant_ids=a,b,c&method=cards&start_time=123&endTime=456&has_special_pricing_request=true',
                    'body'   => [
                        'status' => 'requested',
                        'internal_comments' => 'some comment1',
                    ],
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

                if ($method !== \Requests::DELETE)
                {
                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));
                }
                else
                {
                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);
                }

                $this->assertArrayHasKey('X-Dashboard-Admin-Email', $additionalHeaders);
                $this->assertArrayHasKey('X-Dashboard-Admin-OrgId', $additionalHeaders);
                $this->assertEquals('org_100000razorpay', $additionalHeaders['X-Dashboard-Admin-OrgId']);

                $response = new \WpOrg\Requests\Response;

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

    public function testFetchPreviousStatus()
    {
        $this->ba->adminAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/terminals/proxy/instrument_request_previous_status/14charIirId123',
                'method'   => \Requests::GET,
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/instrument_request_previous_status/14charIirId123',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

            $response = new \WpOrg\Requests\Response;

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

    public function testUpdateTemplateMappings()
    {
        $this->ba->adminAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/terminals/proxy/template_mappings/amex',
                'method'   => \Requests::PATCH,
                'content'  => [
                ],
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/template_mappings/amex',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

            $response = new \WpOrg\Requests\Response;

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

    public function testAdminFetchMultipleTerminalsProxy()
    {
        $this->ba->adminAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/terminals/proxy/admin_fetch_multiple/instrument_enablement_state',
                'method'   => \Requests::POST,
                'content'  => [
                    'common_params' => [
                        'count' => 2,
                        'skip' => 0,
                        'from' => 0,
                        'to' => 99999999999999,
                    ],
                    'query_params' => [
                        'merchant_id' => [
                            'testMID1234567',
                            'testMID1234568',
                        ],
                    ],
                ],
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/admin_fetch_multiple/instrument_enablement_state',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                'common_params' => [
                    'count' => 2,
                    'skip' => 0,
                    'from' => 0,
                    'to' => 99999999999999,
                ],
                'query_params' => [
                    'merchant_id' => [
                        'testMID1234567',
                        'testMID1234568',
                    ],
                ],
            ],
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

            $response = new \WpOrg\Requests\Response;

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

    public function testFetchTemplateMappings()
    {
        $this->ba->adminAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/terminals/proxy/template_mappings/amex',
                'method'   => \Requests::GET,
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/template_mappings/amex',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

            $response = new \WpOrg\Requests\Response;

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

    public function testMerchantInstrumentMerchantDashboardProxy()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->ba->proxyAuth();

        $testCases = [
            [
                self::REQUEST       => [
                    'url'      => '/merchant_instrument_request',
                    'method'   => \Requests::POST,
                    'content'   =>  [
                        'instrument'    =>  'pg.cards.visa',
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => ['instrument' => 'pg.cards.visa', 'merchant_id' => '10000000000000'],
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/merchant_instrument_request',
                    'method'   => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request?merchant_id=10000000000000',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/merchant_instrument_status',
                    'method'   => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_status?merchant_id=10000000000000',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/merchant_instrument_request/mir_1234567',
                    'method'   => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request/mir_1234567',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   =>  '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/merchant_instrument_request/mir_12341234',
                    'method'   => \Requests::PATCH,
                    'content'  => [
                        'status'  => 'cancelled',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request/mir_12341234',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'status'    =>  'cancelled'
                ],
            ]
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

            $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

            $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

                $this->assertArrayHasKey('X-Dashboard-Merchant-Id', $additionalHeaders);

                $this->assertArrayHasKey('X-Dashboard-Merchant-OrgId', $additionalHeaders);

                $this->assertEquals('10000000000000', $additionalHeaders['X-Dashboard-Merchant-Id']);

                $this->assertEquals('100000razorpay', $additionalHeaders['X-Dashboard-Merchant-OrgId']);

                $response = new \WpOrg\Requests\Response;

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

    public function testKAMAdminDashboardProxy()
    {
        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth();

        $testCases = [
            [
                self::REQUEST       => [
                    'url'      => '/merchant_instrument_request',
                    'method'   => \Requests::POST,
                    'content'   =>  [
                        'instrument'    =>  'pg.cards.visa',
                        'special_pricing' => 'special_pricing_test'
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'instrument' => 'pg.cards.visa',
                    'special_pricing' => 'special_pricing_test',
                    'merchant_id' => '10000000000000'
                ],
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchant_instrument_request/mir_12341234',
                    'method'   => \Requests::PATCH,
                    'content'   =>  [
                        'status'    =>  'cancelled',
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request/mir_12341234',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => ['status' => 'cancelled'],
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

            $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

            $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

                $this->assertArrayHasKey('X-Dashboard-Merchant-Id', $additionalHeaders);

                $this->assertArrayHasKey('X-Dashboard-Admin-Email', $additionalHeaders);

                $response = new \WpOrg\Requests\Response;

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

    public function testKamAdminDashboardFetchMerchantInstrument(){

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $testCases = [
            [

                self::REQUEST       => [
                    'url'       => '/merchant_instrument_request_fetch?count=50',
                    'method'    => \Requests::POST,
                    'content' => [
                        'status'       => 'activated',
                        'merchant_ids' => [
                            "10000000000000"
                        ],
                        'start_time'   => '123',
                        'end_time'     => '456',
                        'has_special_pricing_request' => true,
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/composite_instrument_request',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'merchant_ids' => ["10000000000000"],
                    'status'       => 'activated',
                    'start_time'   => '123',
                    'end_time'     => '456',
                    'count'        => 50,
                    'skip'         => 0,
                    'has_special_pricing_request' => '1',
                ],

            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchant_instrument_requests',
                    'method'   => \Requests::POST,
                    'content'   =>  [
                        [
                            'instrument'    =>  'pg.cards.visa',
                            'special_pricing' => 'special_pricing_test',
                            'merchant_id' => '10000000000000',
                        ],
                        [
                            'instrument'    =>  'pg.cards.mastercard',
                            'special_pricing' => 'special_pricing_test2',
                            'merchant_id' => '10000000000001',
                        ],
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_requests',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    [
                        'instrument'    =>  'pg.cards.visa',
                        'special_pricing' => 'special_pricing_test',
                        'merchant_id' => '10000000000000',
                    ],
                    [
                        'instrument'    =>  'pg.cards.mastercard',
                        'special_pricing' => 'special_pricing_test2',
                        'merchant_id' => '10000000000001',
                    ],
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

                $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

                $this->assertArrayHasKey('X-Dashboard-Admin-Email', $additionalHeaders);

                $response = new \WpOrg\Requests\Response;

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

    public function testKamAxisAdminDashboardFetchMerchantInstrument()
    {
        $this->fixtures->create('merchant', [
            'id'    => '10000000000001',
        ]);

        $testCase = [
            self::REQUEST       =>  [
                'method'  => 'POST',
                'url'     => '/merchant_instrument_request_fetch?count=50',
                'content' => [
                    'merchant_ids' => [
                        "10000000000000",
                        "10000000000001"
                    ],
                    'start_time'   => '123',
                    'end_time'     => '456',
                    'has_special_pricing_request' => true,
                ],
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/composite_instrument_request',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'merchant_ids' => ["10000000000001"],
                    'start_time'   => '123',
                    'end_time'     => '456',
                    'count'        => 50,
                    'skip'         => 0,
                    'has_special_pricing_request' => '1',
            ],
        ];

        $this->org = $this->fixtures->create('org', [
            'id'                      => Org::AXIS_ORG_ID,
            'email'                   => 'admin@axis.com',
            'from_email'              => 'noreplay@axis.com',
            'cross_org_access'        => true,
        ]);

        $this->fixtures->merchant->edit('10000000000001', ['org_id' => Org::AXIS_ORG_ID]);

        $this->org = $this->getDbEntityById('org', Org::AXIS_ORG_ID);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, 'org_'.Org::AXIS_ORG_ID);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

            $this->assertArrayHasKey('X-Dashboard-Admin-Email', $additionalHeaders);

            $response = new \WpOrg\Requests\Response;

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


    public function testKamAdminDashboardBulkCreateMerchantInstrumentsV2()
    {
        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/merchant_instrument_requests_v2',
                'method'   => \Requests::POST,
                'content'   =>  [
                    'data' => json_encode([
                        [
                            'instrument'    =>  'pg.cards.visa',
                            'special_pricing' => 'special_pricing_test',
                            'merchant_id' => '10000000000000',
                        ],
                        [
                            'instrument'    =>  'pg.cards.mastercard',
                            'special_pricing' => 'special_pricing_test2',
                            'merchant_id' => '10000000000001',
                        ],
                    ])
                ]
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_requests_v2',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                'data' => json_encode([
                    [
                        'instrument'    =>  'pg.cards.visa',
                        'special_pricing' => 'special_pricing_test',
                        'merchant_id' => '10000000000000',
                    ],
                    [
                        'instrument'    =>  'pg.cards.mastercard',
                        'special_pricing' => 'special_pricing_test2',
                        'merchant_id' => '10000000000001',
                    ],
                ])
            ],
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['data' => ['testKey' => 'testValue']]];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendFormRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

            $this->assertArrayHasKey('X-Dashboard-Admin-Email', $additionalHeaders);
            $this->assertArrayHasKey('permission', $additionalHeaders);

            $body = '
                   {
                    "data": {
                       "testKey": "testValue"
                    }
                }';

            $response = new Response(200, [], $body);

            return $response;
        }, 1);

        $this->startTest();

    }

    public function testDiscrepancyAdminDashboardProxy()
    {
        $this->ba->adminAuth();

        $testCases = [
            [
                // Create iir_discrepancy_answers on admin KAM dashboard
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/iir_discrepancy_answers/admin',
                    'method'    => \Requests::POST,
                    'content'  => [
                        'data' => json_encode([
                            [
                                'answer_field_value' => 'swiggy.cmo/policy',
                                'answered_comment'   => 'provided private policy link',
                                'file'   => 'file' // TODO: Test content-type: multipart form data
                            ],
                        ])
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/iir_discrepancy_answers',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'data' => json_encode([
                        [
                            'answer_field_value' => 'swiggy.cmo/policy',
                            'answered_comment'   => 'provided private policy link',
                            'file'   => 'file' // TODO: Test content-type: multipart form data
                        ],
                    ])
                ],
            ],
            [
                // get discrepancy static list
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/discrepancy_list',
                    'method'    => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/discrepancy_list',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => ''
            ],
            [   // raise iir_discrepancies
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/iir_discrepancies',
                    'method'    => \Requests::POST,
                    'content'   => [
                        'internal_instrument_request_id' => '14charIirId123',
                        'discrepancy_ids' => ['discrepancyid1', 'discrepancyid2'],
                        'iir_status' => 'action_required',
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/iir_discrepancies',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'internal_instrument_request_id' => '14charIirId123',
                    'discrepancy_ids' => ['discrepancyid1', 'discrepancyid2'],
                    'iir_status' => 'action_required',
                ],
            ],
            [   // get iir_discrepancies of an IIR
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/internal_instrument_request/iirid123123123/iir_discrepancies',
                    'method'    => \Requests::GET
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/internal_instrument_request/iirid123123123/iir_discrepancies',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [   // create static discrepancy
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/discrepancy',
                    'method'    => \Requests::POST
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/discrepancy',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [   // Edit static discrepancy
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/discrepancy/123321',
                    'method'    => \Requests::PATCH
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/discrepancy/123321',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [   // get all iir_discrepancies of a merchant
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/merchants/100000000000000/iir_discrepancies',
                    'method'    => \Requests::GET
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchants/100000000000000/iir_discrepancies',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [   // update iir_discrepancy status
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/iir_discrepancies/iirDiscrepancyId1231',
                    'method'    => \Requests::PATCH,
                    'content'   => [
                        'status' => 'resolved'
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/iir_discrepancies/iirDiscrepancyId1231',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::PATCH,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'status' => 'resolved'
                ],
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

            $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

            if($testCase['request']['url'] === '/terminals/proxy/iir_discrepancy_answers/admin')
            {
                $this->testData[__FUNCTION__]['response'] = ['content' => ['data' => ['testKey' => 'testValue']]];

                $this->mockTerminalsServiceSendFormRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

                    $body = '
                    {
                     "data": {
                        "testKey": "testValue"
                     }
                 }';

                    $response = new Response(200, [], $body);

                    return $response;

                }, 1);
            }
            else
            {
                $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);
                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                    if ($method !== \Requests::DELETE)
                    {
                        $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));
                    }
                    else
                    {
                        $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);
                    }

                    $response = new \WpOrg\Requests\Response;

                    $response->body = '
                           {
                            "data": {
                               "testKey": "testValue"
                            }
                        }';

                    return $response;
                }, 1);
            }

            $this->startTest();

        }
    }

    public function testDiscrepancyMerchantDashboardProxy()
    {
        $this->ba->privateAuth();

        $testCases = [
            [
                self::REQUEST       => [
                    'url'      => '/terminals/proxy/merchant_instrument_request/mirid123123/iir_discrepancies',
                    'method'   => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/merchant_instrument_request/mirid123123/iir_discrepancies',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [
                self::REQUEST       => [
                    'url'      => '/terminals/proxy/discrepancy_list_merchant',
                    'method'   => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/discrepancy_list',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [
                self::REQUEST                              => [
                    'url'      => '/terminals/proxy/iir_discrepancy_answers',
                    'method'   => \Requests::POST,
                    'content'  => [
                        'data' => json_encode([
                            [
                                'answer_field_value' => 'swiggy.cmo/policy',
                                'answered_comment'   => 'provided private policy link',
                                'file'   => 'file' // TODO: Test content-type: multipart form data
                            ],
                        ])
                    ]
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/iir_discrepancy_answers',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'data' => json_encode([
                        [
                            'answer_field_value' => 'swiggy.cmo/policy',
                            'answered_comment'   => 'provided private policy link',
                            'file'   => 'file' // TODO: Test content-type: multipart form data
                        ],
                    ])
                ],
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

            if($testCase['request']['url'] === '/terminals/proxy/iir_discrepancy_answers')
            {
                $this->testData[__FUNCTION__]['response'] = ['content' => ['data' => ['testKey' => 'testValue']]];

                $this->mockTerminalsServiceSendFormRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

                    $body = '
                    {
                     "data": {
                        "testKey": "testValue"
                     }
                 }';

                    $response = new Response(200, [], $body);

                    return $response;

                }, 1);
            }
            else
            {
                $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

                $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

                    $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], json_decode($content, true));

                    $response = new \WpOrg\Requests\Response;

                    $response->body = '
                           {
                            "data": {
                               "testKey": "testValue"
                            }
                        }';

                    return $response;
                }, 1);

            }

            $this->startTest();
        }
    }

    public function testCollectInfoAdminProxy()
    {
        $this->ba->adminAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/terminals/proxy/collect_info/merchant/10000000000000/details/admin',
                'method'   => \Requests::POST,
                'content'  => [
                    'testKey' => 'testValue',
                ],
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/collect_info/merchant/10000000000000/details',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '{"testKey":"testValue"}'
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

            $response = new \WpOrg\Requests\Response;

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

    public function testCollectInfoProxy()
    {
        $this->ba->privateAuth();

        $testCase = [
            self::REQUEST       => [
                'url'      => '/terminals/proxy/collect_info/merchant/details',
                'method'   => \Requests::POST,
                'content'  => [
                    'testKey' => 'testValue',
                ],
            ],
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/collect_info/merchant/10000000000000/details',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
            self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '{"testKey":"testValue"}'
        ];

        $this->testData[__FUNCTION__]['response'] = ['content' => ['testKey' => 'testValue']];

        $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method, $additionalOptions = [], $additionalHeaders) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE], $content);

            $response = new \WpOrg\Requests\Response;

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
