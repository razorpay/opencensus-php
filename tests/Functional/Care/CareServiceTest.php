<?php


namespace Functional\Care;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CareServiceTest extends TestCase
{
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

    public function testDashboardProxy()
    {
        $this->ba->proxyAuth();

        $testCases = [
            [
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
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'code' => 'internal',
                    'msg'  => 'api service returned 400 Bad Request',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 400,
            ],
            [
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'code' => 'internal',
                    'msg'  => 'api service returned 500 Server Error',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 500,
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

            $this->testData[__FUNCTION__]['response']['content']     = $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_BODY];
            $this->testData[__FUNCTION__]['response']['status_code'] = $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS];

            if ($testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS] >= 400)
            {
                if ($testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS] >= 500)
                {
                    $this->expectException(ServerErrorException::class);
                }
                else if ($testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS] >= 400)
                {
                    $this->expectException(BadRequestException::class);
                }
            }

            $this->startTest();
        }
    }

    public function testDashboardProxyInvalidRoute()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}
