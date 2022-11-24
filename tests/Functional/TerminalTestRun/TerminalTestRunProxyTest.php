<?php

namespace RZP\Tests\Functional;


use RZP\Services\TerminalsService;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Admin\Permission\Repository;
use RZP\Tests\Functional\Helpers\TerminalTrait;

class TerminalTestRunProxyTest extends TestCase
{
    use TerminalTrait;
    use RequestResponseFlowTrait;

    protected $terminalsServiceMock;

    const EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      = 'expected_path';
    const EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    = 'expected_method';
    const EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   = 'expected_request_to_terminals_service';
    const EXPECTED_REQUEST_HEADERS_TERMINALS_SERVICE   = 'expected_request_headers_terminals_service';
    const REQUEST                                      = 'request';

    const TERMINAL_TEST_INSTRUMENT_SAMPLE_REQUEST = [
        'descriptor'            =>  'pg.cards.domestic.visa',
        'handler'               =>  'native_otp',
        'payment_create_input'  =>  [
            'amount'      =>   '100',
            'currency'    =>   'INR',
            'customer_id' =>   'cust_0123456789abcd',
            'token'       =>   'cardToken',
        ],
        'order_create_input'    =>  [
            'amount'    =>  '100',
            'currency'  =>  'INR',
            'secrets'   =>  [
                'secret1'   =>  '1',
                'secret2'   =>  '23',
            ],
            'otp_senders'       =>  ['VX-ICICI'],
            'otp_identifiers'   =>  [
                'amount'    =>  true
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalTestRunProxyTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }

    public function testTerminalTestRunAdminDashboardProxy()
    {
        $this->ba->adminAuth();

        $testCases = [
            [  // create TTR
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/terminal_test_run',
                    'method'    => \Requests::POST,
                    'content'  => [
                        'terminal_id' => '1000000000000t',
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/terminal_test_run',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [
                    'terminal_id' => '1000000000000t',
                ],
            ],
            [   // get ttr
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/terminal_test_run',
                    'method'    => \Requests::GET,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/terminal_test_run',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
            ],
            [   // create instrument
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/terminal_test_instrument',
                    'method'    => \Requests::POST,
                    'content'   => self::TERMINAL_TEST_INSTRUMENT_SAMPLE_REQUEST,
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/terminal_test_instrument',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => self::TERMINAL_TEST_INSTRUMENT_SAMPLE_REQUEST,
            ],
            [   // delete instrument
                self::REQUEST       => [
                    'url'       => '/terminals/proxy/terminal_test_instrument/ttiId123123',
                    'method'    => \Requests::DELETE,
                    'content'   => [],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/terminal_test_instrument/ttiId123123',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::DELETE,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => [],
            ],
            [   // fetch TTR by params
                self::REQUEST       => [
                    'url'       => '/terminal_test_run_fetch',
                    'method'    => \Requests::POST,
                    'content'  => [
                        'query' => 'terminal_id=100000000000t',
                        'count' =>  2,
                    ],
                ],
                self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/terminal_test_run?terminal_id=100000000000t&count=2',
                self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::GET,
                self::EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   => '',
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


    public function testTerminalServiceExecuteTerminalTestRun()
    {
        $this->ba->adminAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getProxyExecuteTerminalTestRunResponse();
        }, 1);

        $this->startTest();
    }
}
