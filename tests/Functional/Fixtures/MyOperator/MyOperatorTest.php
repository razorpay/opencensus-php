<?php


namespace Functional\Care;

use Mockery;
use Illuminate\Support\Str;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MyOperatorTest extends TestCase
{
    const API_ROUTE                             = 'api_route';
    const EXPECTED_MYOPERATOR_ROUTE             = 'expected_myoperator_route';
    const EXPECTED_MYOPERATOR_REQUEST_BODY      = 'expected_myoperator_request_body';
    const ACTUAL_MYOPERATOR_RESPONSE_BODY       = 'actual_myoperator_response_body';
    const ACTUAL_MYOPERATOR_RESPONSE_STATUS     = 'actual_myoperator_response_status';
    const API_REQUEST_BODY                      = 'api_request_body';
    const MYOPERATOR_FUNC_TO_MOCK               = 'myoperator_func_to_mock';
    const REQUEST_METHOD                        = 'method';
    const IS_MYOPERATOR_OLD_VERSION             = "myoperator_old_version";

    use RequestResponseFlowTrait;

    protected $myOperatorMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MyOperatorsTestData.php';

        parent::setUp();

        $this->setMyOperatorServiceMock();
    }

    public function testMyOperatorProxyRequest()
    {
        $this->ba->careAppAuth();

        $testCases = [
            [
                self::API_ROUTE                             => '/myoperator/user',
                self::EXPECTED_MYOPERATOR_ROUTE             => 'user',
                self::EXPECTED_MYOPERATOR_REQUEST_BODY      => [],
                self::REQUEST_METHOD                                => 'GET',
                self::IS_MYOPERATOR_OLD_VERSION             => true,
                self::ACTUAL_MYOPERATOR_RESPONSE_BODY     => [
                    "status" => "success",
                    "page_size" => 20,
                    "total_user" => 3,
                    "data" => [
                        [
                            "user_id" => "58d8c0df74fe7796",
                            "role_id" => "1",
                            "is_available" => 0
                        ],
                        [
                            "user_id" => "5fd9f8566de84971",
                            "role_id" => "2",
                            "is_available" => "1"
                        ],
                        [
                            "user_id" => "5798425f94a2d589",
                            "role_id" => "8",
                            "is_available" => "1"
                        ]
                    ],
                    "next_page" => "",
                    "prev_page" => "",
                    "code" => 200
                ],
                self::ACTUAL_MYOPERATOR_RESPONSE_STATUS             => 200,
                self::MYOPERATOR_FUNC_TO_MOCK                   => 'makeCalLOutboundApiRequest'
            ],
            [
                self::API_ROUTE                                 => '/myoperator/campaign/obd-api-v1',
                self::EXPECTED_MYOPERATOR_ROUTE                 => 'obd-api-v1',
                self::EXPECTED_MYOPERATOR_REQUEST_BODY   => [
                    "company_id" => "6007b8a872f2b754",
                    "type" => "2",
                    "public_ivr_id" => "603c957f7ba01907",
                    "reference_id" => "abcd"
                ],
                self::REQUEST_METHOD                                => 'POST',
                self::API_REQUEST_BODY                   => [
                    "company_id" => "6007b8a872f2b754",
                    "type" => "2",
                    "public_ivr_id" => "603c957f7ba01907",
                    "reference_id" => "abcd"
                ],
                self::ACTUAL_MYOPERATOR_RESPONSE_BODY   => [
                    'details'       =>  'Request accepted successfully',
                    'status'        =>  'success',
                    'code'          =>  '200',
                    'unique_id'     =>  '2b7e441c-7a70-11eb-b830-9edbe0989de7',
                    'reference_id'  =>  'abcd'
                ],
                self::ACTUAL_MYOPERATOR_RESPONSE_STATUS             => 200,
                self::MYOPERATOR_FUNC_TO_MOCK                       => 'makeApiRequestAndGetResponse',
                self::IS_MYOPERATOR_OLD_VERSION                     => false,
            ],
            [
                self::API_ROUTE                             => '/myoperator/campaign/obd-api-v1',
                self::EXPECTED_MYOPERATOR_ROUTE             => 'obd-api-v1',
                self::EXPECTED_MYOPERATOR_REQUEST_BODY      => [],
                self::REQUEST_METHOD                                => 'POST',
                self::ACTUAL_MYOPERATOR_RESPONSE_BODY   => [
                    "description"=> "The requested URL was not found on the server.",
                    "class"=> "BAD_REQUEST",
                    "code"=> "BAD_REQUEST_ERROR",
                ],
                self::ACTUAL_MYOPERATOR_RESPONSE_STATUS         => 200,
                self::MYOPERATOR_FUNC_TO_MOCK                   => 'makeApiRequestAndGetResponse',
                self::IS_MYOPERATOR_OLD_VERSION                 => false
            ]
        ];

        foreach ($testCases as $testCase)
        {
            $this->expectMyOperatorRequestAndRespondWith(
                $testCase[self::EXPECTED_MYOPERATOR_ROUTE],
                $testCase[self::EXPECTED_MYOPERATOR_REQUEST_BODY],
                $testCase[self::ACTUAL_MYOPERATOR_RESPONSE_BODY],
                $testCase[self::ACTUAL_MYOPERATOR_RESPONSE_STATUS],
                $testCase[self::MYOPERATOR_FUNC_TO_MOCK],
                $testCase[self::IS_MYOPERATOR_OLD_VERSION],
                $testCase[self::REQUEST_METHOD]
            );

            if (empty($testCase[self::API_REQUEST_BODY]) === false)
            {
                $this->testData[__FUNCTION__]['request']['content']     = $testCase[self::API_REQUEST_BODY];
            }
            $this->testData[__FUNCTION__]['request']['method']      = $testCase[self::REQUEST_METHOD];
            $this->testData[__FUNCTION__]['request']['url']         = $testCase[self::API_ROUTE];
            $this->testData[__FUNCTION__]['response']['content']     = $testCase[self::ACTUAL_MYOPERATOR_RESPONSE_BODY];
            $this->testData[__FUNCTION__]['response']['status_code'] = $testCase[self::ACTUAL_MYOPERATOR_RESPONSE_STATUS];

            $this->startTest();
        }
    }

    protected function setMyOperatorServiceMock()
    {
        $this->myOperatorMock = Mockery::mock('RZP\Services\MyOperator', [$this->app->trace,
            $this->app->config->get('applications.myoperator'),
            $this->app['basicauth']])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app['myoperator'] = $this->myOperatorMock;
    }

    protected function expectMyOperatorRequestAndRespondWith($expectedPath, $expectedContent,
                                                             $respondWithBody, $respondWithStatus, $funcName,
                                                             $isOldVersion = true , $expectedMethod)
    {
        $this->myOperatorMock
            ->shouldReceive($funcName)
            ->times(1)
            ->with(Mockery::on(function ($actualPayload) use ($expectedContent)
                {
                    foreach ($expectedContent as $key => $value)
                    {
                        if (isset($actualPayload[$key]) === false)
                        {
                            return false;
                        }

                        if ($expectedContent[$key] !== $actualPayload[$key])
                        {
                            return false;
                        }
                    }
                    return true;
                }),
                Mockery::on(function ( $actualPath) use ( $expectedPath)
                {
                    if (empty($expectedPath) === false && empty($actualPath) === false) {
                        return Str::startsWith( $actualPath, $expectedPath );
                    }
                }),
                Mockery::on(function ( $actualMethod) use ( $expectedMethod)
                {
                    return $actualMethod === $expectedMethod;
                }))
                ->andReturnUsing(function () use ($respondWithBody, $respondWithStatus, $isOldVersion)
                {
                    $response = new \WpOrg\Requests\Response;

                    $response->body = json_encode($respondWithBody);

                    $response->status_code = $respondWithStatus;

                    if ($isOldVersion === true)
                    {
                        return $response;
                    }
                    else
                    {
                        return $respondWithBody;
                    }
                });
    }
}
