<?php

namespace Functional\Batch;

use Mockery;

use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;

class UpiOnboardedTerminalEditTest extends TestCase
{
    use BatchTestTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiOnboardedTerminalEditTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBulkOnboardedTerminalEditValidateFile()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '1',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '1',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '1',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '1',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '1',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '6217',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '1',
                Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '1',
            ],
        ];
    }

    public function testBulkOnboardedTerminalEditForBatchServiceForUpiInstruments()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@axis',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $expectedInputToTerminalService = $this->getDefaultRequestContentSentToTerminalService();

        $expectedInputToTerminalService['features'] = [
            "cc_on_upi"          => "1",
            "wallet_on_upi"      => "1",
            "credit_line_on_upi" => "1",
        ];
        unset($expectedInputToTerminalService['category']);

        $this->assertArraySelectiveEquals(
            $expectedInputToTerminalService,
            json_decode($inputToTerminalService, true)
        );
    }

    protected function mockTerminalsService(&$arr, $input)
    {
        $terminalsMock = \Mockery::mock(\RZP\Services\TerminalsService::class)->makePartial();

        $terminalsMock->shouldAllowMockingProtectedMethods()
                      ->shouldReceive('sendRequest')
                      ->andReturnUsing(
                          function (
                              string $path, $content, string $method, array $additionalOptions,
                              array  $additionalHeaders
                          ) use (&$arr, $input) {
                              $arr = $content;

                              return [
                                  'terminal'    => [
                                      'id'                      => 'term_10RandomTermId',
                                      'gateway_vpa_whitelisted' => 'rzp@axis',
                                  ],
                                  'status_code' => 200,
                              ];
                          }
                      );

        $terminalsMock->shouldReceive('getTerminalServiceOrgHeaders')->andReturn([]);

        $this->app->instance('terminals_service', $terminalsMock);
    }

    // tests route exposed to batch service

    protected function getDefaultRequestContentSentToTerminalService()
    {
        return [
            "terminal_id"      => "term_10RandomTermId",
            "gateway"          => "upi_axis",
            "currency"         => [
            ],
            "identifiers"      => [
                "vpa"                 => null,
                "gateway_terminal_id" => null,
                "gateway_access_code" => null,
                "vpa_handle"          => null,
            ],
            "features"         => [
                "online"             => "1",
                "recurring"          => "1",
                "merchant_size"      => "1",
                "cc_on_upi"          => "1",
                "wallet_on_upi"      => "1",
                "credit_line_on_upi" => "1",
                "edit_billing_label" => "1",
                "edit_mobile_number" => "1",
            ],
            "sync_instruments" => true,
            "category"         => "6217",
        ];
    }

    public function testBulkOnboardedTerminalEditForBatchServiceForOneNonUpiInstrument()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@axis',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $expectedInputToTerminalService = $this->getDefaultRequestContentSentToTerminalService();

        $expectedInputToTerminalService['features'] = [
            'online'    => '1',
            'recurring' => '0',
        ];
        unset($expectedInputToTerminalService['category']);

        $this->assertArraySelectiveEquals(
            $expectedInputToTerminalService,
            json_decode($inputToTerminalService, true)
        );
    }

    public function testBulkOnboardedTerminalEditForBatchServiceForMultipleNonUpiInstruments()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@axis',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));
    }

    public function testBulkOnboardedTerminalEditForBatchServiceForMixOfUpiAndNonUpiInstruments()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@axis',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));
    }

    // We expect an error here as YB does not support changing MCC
    public function testBulkOnboardedTerminalEditForBatchServiceWithMccForUpiYesbank()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@yesbank',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $this->assertArraySelectiveEquals([], $inputToTerminalService);
    }

    // We expect an error here as ICICI does not support changing billing label
    public function testBulkOnboardedTerminalEditForBatchServiceWithBillingLabelForUpiIcici()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@icici',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $this->assertArraySelectiveEquals([], $inputToTerminalService);
    }

    // Passing a long MCC should cause validation failure
    public function testBulkOnboardedTerminalEditForBatchServiceWithIncorrectMcc()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@yesbank',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $this->assertArraySelectiveEquals([], $inputToTerminalService);
    }

    public function testBulkOnboardedTerminalEditForBatchServiceWithUnsupportedGateway()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@airtel',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $this->assertArraySelectiveEquals([], $inputToTerminalService);
    }

    public function testBulkOnboardedTerminalEditForBatchServiceWithAllFieldsEmpty()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@axis',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));
    }

    public function testBulkOnboardedTerminalEditForBatchServiceWithOnlyOneFieldToEdit()
    {
        $this->ba->appAuth();

        $inputToTerminalService = [];

        $this->mockTerminalsService(
            $inputToTerminalService,
            [
                'terminal'    => [
                    'id'                      => 'term_10RandomTermId',
                    'gateway_vpa_whitelisted' => 'rzp@axis',
                ],
                'status_code' => 200,
            ]
        );

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $expectedInputToTerminalService = $this->getDefaultRequestContentSentToTerminalService();

        $expectedInputToTerminalService['features'] = ['online' => '0', 'recurring' => '0'];
        unset($expectedInputToTerminalService['category']);

        $this->assertArraySelectiveEquals(
            $expectedInputToTerminalService,
            json_decode($inputToTerminalService, true)
        );
    }
}
