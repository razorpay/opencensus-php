<?php


namespace Functional\Care;

use Mockery;
use RZP\Trace\TraceCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CareServiceTest extends TestCase
{
    const AUTH                                = 'auth';
    const API_ROUTE                           = 'api_route';
    const EXPECTED_CARE_SERVICE_ROUTE         = 'expected_care_service_route';
    const EXPECTED_CARE_SERVICE_REQUEST       = 'expected_care_service_request';
    const ACTUAL_CARE_SERVICE_RESPONSE_BODY   = 'actual_care_service_response_body';
    const ACTUAL_CARE_SERVICE_RESPONSE_STATUS = 'actual_care_service_response_status';

    use RequestResponseFlowTrait;

    protected $careServiceMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CareServiceTestData.php';

        parent::setUp();

        $this->setUpCareServiceMock();
    }

    public function testInternalMerchantFetch()
    {
        $this->ba->careAppAuth();

        $this->startTest();
    }

    protected function setUpCareServiceMock()
    {
        $this->careServiceMock = Mockery::mock('RZP\Services\CareServiceClient', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app['care_service'] = $this->careServiceMock;
    }

    protected function expectCareServiceRequestAndRespondWith($expectedPath, $expectedContent, $respondWithBody, $respondWithStatus)
    {
        $this->careServiceMock
            ->shouldReceive('sendRequest')
            ->times(1)
            ->with(Mockery::on(function ($actualPath) use ($expectedPath)
            {
                return $expectedPath === $actualPath;
            }), Mockery::on(function ($actualMethod)
            {
                return strtolower($actualMethod) === 'post';
            }),
                Mockery::on(function ($actualContent) use ($expectedContent)
                {
                    return $expectedContent === $actualContent;
                }))
            ->andReturnUsing(function () use ($respondWithBody, $respondWithStatus)
            {
                $response = new \Requests_Response;

                $response->body = json_encode($respondWithBody);

                $response->status_code = $respondWithStatus;

                return $response;
            });
    }


    public function testProxy()
    {
        $testCases = [
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'cron',
                self::API_ROUTE                           => '/care_service/cron/twirp/rzp.care.callback.v1.CallbackService/InitSlots',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/InitSlots',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'cron',
                self::API_ROUTE                           => '/care_service/cron/twirp/rzp.care.callback.v1.CallbackService/PushCallbackToQueue',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/PushCallbackToQueue',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/GetSlots',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/GetSlots',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CreateCallback',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/CreateCallback',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/GetCallback',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/GetCallback',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->expectCareServiceRequestAndRespondWith(
                $testCase[self::EXPECTED_CARE_SERVICE_ROUTE],
                $testCase[self::EXPECTED_CARE_SERVICE_REQUEST],
                $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_BODY],
                $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS]
            );

            $this->testData[__FUNCTION__]['request']['url'] = $testCase[self::API_ROUTE];

            $this->testData[__FUNCTION__]['response']['content']     = $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_BODY];
            $this->testData[__FUNCTION__]['response']['status_code'] = $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS];

            switch ($testCase[self::AUTH])
            {
                case 'proxy':
                    $this->ba->proxyAuth();
                    break;
                case 'cron':
                    $this->ba->cronAuth();
                    break;
            }

            $this->app['trace']->info(TraceCode::MISC_TRACE_CODE, $this->testData[__FUNCTION__]);

            $this->startTest();
        }
    }

    public function testDashboardProxyInvalidRoute()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxy400Exception()
    {
        $this->ba->proxyAuth();

        $this->expectCareServiceRequestAndRespondWith(
            'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
            [
                'merchant' => [
                    'id' => '10000000000000',
                    ],
            ],
            [
                'code' => 'internal',
                'msg'  => 'error message',
            ],
            400
        );

        $this->startTest();
    }

    public function testProxy500Exception()
    {
        $this->ba->proxyAuth();

        $this->expectCareServiceRequestAndRespondWith(
            'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
            [
                'merchant' => [
                    'id' => '10000000000000',
                ],
            ],
            [
                'code' => 'internal',
                'msg'  => 'error message',
            ],
            500
        );

        $this->startTest();
    }
}
