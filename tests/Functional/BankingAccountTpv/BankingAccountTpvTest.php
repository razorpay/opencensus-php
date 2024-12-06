<?php

namespace RZP\Tests\Functional\BankingAccountTpv;

use Queue;
use Mockery;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\BankingAccountTpv\Type;
use RZP\Models\BankingAccountTpv\Core;
use RZP\Models\BankingAccountTpv\Entity;
use RZP\Models\BankingAccountTpv\Status;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Jobs\BankingAccountTpvMigration;
use RZP\Models\BankingAccountTpv\Constants;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;
use RZP\Tests\Traits\MocksSplitz;

class BankingAccountTpvTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use MocksSplitz;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountTpvTestData.php';

        parent::setUp();

        $this->fixtures->edit('balance', '10000000000000', [
            'balance'       => 10000,
            'type'          => 'banking',
            'merchant_id'   => '10000000000000',
            'account_type'  => 'shared',
        ]);
    }

    protected function mockMozartResponseForSourceAccountAddition(
        $expectedInput, $expectedResponse = [], $throwError = false, $times = 1): void
    {
        $mozartServiceMock = Mockery::mock('RZP\Services\Mock\Mozart', [$this->app])
                                    ->shouldAllowMockingProtectedMethods()->makePartial();

        $expectedResponse = (empty($expectedResponse) === true) ? [
            "success" => true,
            "error"   => [],
            "data"    => [
                "request_id"        => "24020231264",
                "status"            => "SUCCESS",
                "response_code"     => "00",
                "response_message"  => "Source account has been added successfully.",
                "client_identifier" => "Z82"
            ],
            "next"    => [],
            "mozart_id" => "cl7381c2b5po4din5q8g",
            "external_trace_id" => "5a31015763304f49dd130b9e6d4f0363"
        ] : $expectedResponse;

        if ($throwError === false)
        {
            $mozartServiceMock->shouldReceive('sendMozartRequest')
                              ->andReturnUsing(function(string $namespace,
                                                        string $gateway,
                                                        string $action,
                                                        array  $input,
                                                        string $version = 'v1',
                                                        bool   $useMozartMappedInternalErrorCode = false,
                                                        int    $timeout = 60,
                                                        int    $connectTimeout = 10,
                                                        bool   $logResponse = true, bool $addEntities = true) use ($expectedInput, $expectedResponse) {

                                  self::assertEquals(COnstants::SOURCE_ACCOUNT_ADDITION_MOZART_NAMESPACE, $namespace);
                                  self::assertEquals(Channel::YESBANK, $gateway);
                                  self::assertEquals(Constants::SOURCE_ACCOUNT_ADDITION_MOZART_ACTION, $action);
                                  self::assertArraySelectiveEquals($expectedInput, $input);

                                  return $expectedResponse;
                              })->times($times);
        }
        else
        {
            $mozartServiceMock->shouldReceive('sendMozartRequest')
                              ->andReturnUsing(function(string $namespace,
                                                        string $gateway,
                                                        string $action,
                                                        array  $input,
                                                        string $version = 'v1',
                                                        bool   $useMozartMappedInternalErrorCode = false,
                                                        int    $timeout = 60,
                                                        int    $connectTimeout = 10,
                                                        bool   $logResponse = true, bool $addEntities = true) use ($expectedInput, $expectedResponse) {

                                  self::assertEquals(COnstants::SOURCE_ACCOUNT_ADDITION_MOZART_NAMESPACE, $namespace);
                                  self::assertEquals(Channel::YESBANK, $gateway);
                                  self::assertEquals(Constants::SOURCE_ACCOUNT_ADDITION_MOZART_ACTION, $action);
                                  self::assertArraySelectiveEquals($expectedInput, $input);

                                  throw new Exception\GatewayErrorException(
                                      ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                                      $expectedResponse['error']['gateway_error_code'] ?? 'gateway_error_code',
                                      $expectedResponse['error']['gateway_error_description'] ?? 'gateway_error_desc',
                                      [
                                          'error' => $expectedResponse['error'],
                                          'data'  => $expectedResponse['data']
                                      ]);
                              })->times($times);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function mockBasFetchCredentialsToThrowError($times = 1)
    {
        $this->app['config']->set('applications.banking_account_service.mock', false);

        $this->app['rzp.mode'] = Mode::TEST;

        $basMock = Mockery::mock('RZP\Services\BankingAccountService', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $basMock->shouldReceive('fetchBankingCredentials')
                ->andThrowExceptions([new \Exception("Server Error")])->times($times);

        $this->app->instance('banking_account_service', $basMock);
    }

    public function testAdminTpvCreate()
    {
        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $response = &$this->testData[__FUNCTION__]['response'];

        $response['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] =
            FundAccountValidation::verifyIdAndSilentlyStripSign($fav['id']);

        $this->startTest();

        $fav = $this->getDbEntity('fund_account_validation',
                                  [
                                      'id' => $response['content'][Entity::FUND_ACCOUNT_VALIDATION_ID],
                                  ]);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'payer_account_number' => '98711120003344',
                                      'status'               => Status::APPROVED,
                                  ]);

        $this->assertNotNull($fav);

        $this->assertNotNull($tpv);
    }

    public function testAdminTpvCreateWitInvalidMerchantBalanceId()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000111']);

        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $this->startTest();
    }

    public function testCreateTpvWithDirectBalanceException()
    {
        $this->fixtures->edit('balance', '10000000000000', [
            'account_type'  => 'direct',
        ]);

        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $this->startTest();
    }

    public function testCreateTpvWithPrimaryBalanceException()
    {
        $this->fixtures->create('balance', [
            'id'           => '100Balance1111',
            'balance'      => 10000,
            'type'         => 'primary',
            'merchant_id'  => '10000000000000',
            'account_type' => 'shared',
        ]);

        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $this->startTest();
    }

    public function testCreateTpvWithInvalidBalanceException()
    {
        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $this->startTest();
    }

    public function testAdminTpvCreateDuplicateException()
    {
        $attributes = $this->getTpvInput();

        $attributes[Entity::PAYER_IFSC] = 'CITI5242987';

        $this->fixtures->create('banking_account_tpv', $attributes);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testXDashboardTpvCreateDuplicateException()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $attributes = $this->getTpvInput();

        $fav = $this->getFundAccountValidationInput();

        //Change last digits of IFSC and try to create a new tpv, but this should return a BAD_REQUEST_DUPLICATE_TPV
        $attributes[Entity::PAYER_IFSC] = 'CITI5242987';

        $attributes[Entity::FUND_ACCOUNT_VALIDATION_ID] = FundAccountValidation::verifyIdAndSilentlyStripSign($fav['id']);

        $this->fixtures->create('banking_account_tpv', $attributes);

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '10000000000000',
                'payer_account_number' => '98711120003344',
            ]);

        $initialFavsCount = count($this->getDbEntities("fund_account_validation"));

        $this->ba->proxyAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '10000000000000',
                'payer_account_number' => '98711120003344',
            ]);

        $finalFavsCount = count($this->getDbEntities("fund_account_validation"));

        $this->assertEquals($initialFavsCount, $finalFavsCount);
    }

    public function testGetMerchantTpvs()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $attributes = $this->getTpvInput();

        $this->fixtures->create('banking_account_tpv', $attributes);

        $attributes[Entity::STATUS] = Status::REJECTED;

        $attributes[Entity::IS_ACTIVE] = false;

        $attributes[Entity::REMARKS] = 'Invalid docs';

        $attributes[Entity::PAYER_ACCOUNT_NUMBER] = '8927398273';

        $this->fixtures->create('banking_account_tpv', $attributes);

        $this->ba->proxyAuth();

        $this->startTest();

    }

    public function testFetchMerchantTpvsWithNoRecords()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertTrue(empty($response->count) === true);

    }

    public function testAdminEditTpv()
    {
        $attributes = $this->getTpvInput();

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = $request['url'] . $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::REJECTED,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testAdminEditTpvWitInvalidMerchantBalanceId()
    {
        $this->fixtures->create('merchant', ['id' => '10000000002222']);

        $attributes = $this->getTpvInput();

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = $request['url'] . $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAdminEditTpvInvalidAccountNumber()
    {
        $attributes = $this->getTpvInput();

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = $request['url'] . $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::REJECTED,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNull($tpv);
    }

    public function testAdminEditTpvStatusUpdate()
    {
        $attributes = $this->getTpvInput();

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = $request['url'] . $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'id'     => $tpv->getId(),
                                      'status' => Status::REJECTED,
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testFetchMerchantTpvsWithFavInfo()
    {
        $this->ba->adminAuth();

        for ($i = 0; $i < 10; $i++)
        {
            $attributes = $this->getTpvInput([Entity::STATUS => rand(0, 1) ? Status::APPROVED : Status::PENDING]);

            // Modify payer account number for each request cause payer account number + mid is unique.
            $attributes[Entity::PAYER_ACCOUNT_NUMBER] = (string) ((int) $attributes[Entity::PAYER_ACCOUNT_NUMBER] + $i);

            $fav = $this->getFundAccountValidationInput();

            $attributes[Entity::FUND_ACCOUNT_VALIDATION_ID] = FundAccountValidation::verifyIdAndSilentlyStripSign($fav['id']);

            $this->fixtures->create('banking_account_tpv', $attributes);
        }

        $response = $this->startTest();

        foreach ($response['items'] as $key => $val)
        {
            $this->assertNotNull($val[Entity::STATUS]);

            $this->assertNotNull($val[Entity::FUND_ACCOUNT_VALIDATION_ID]);

            $this->assertNotNull($val[Entity::FUND_ACCOUNT_VALIDATION]);
        }
    }

    public function testManualAutoApproveTpv()
    {
       $this->fixtures->on('live')->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => '10000000000000',
            'entity_id'      => '10000000000000',
            'account_number' => '10010101011',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $this->fixtures->on('live')->create('balance',
                                [
                                    'type'           => 'banking',
                                    'account_type'   => 'shared',
                                    'account_number' => '11122275867',
                                    'merchant_id'    => '10000000000000',
                                    'balance'        => 30000
                                ]);

        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00']);

        $this->fixtures->on('live')->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => '100ghi000ghi00',
            'entity_id'      => '100ghi000ghi00',
            'account_number' => '983672917383',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $this->fixtures->on('live')->create('balance',
                                            [
                                                'type'           => 'banking',
                                                'account_type'   => 'shared',
                                                'account_number' => '983672917383',
                                                'merchant_id'    => '100ghi000ghi00',
                                                'balance'        => 20000
                                            ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        array_push($request['content'], '100ghi000ghi00', '10000000000000', 'invalidMid');

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertEquals($response['total_count'], 3);

        $this->assertEquals($response['success_count'], 2);

        $this->assertEquals($response['failed_count'], 1);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '100ghi000ghi00',
                                      'status'               => Status::APPROVED,
                                      'payer_account_number' => '983672917383',
                                      'payer_ifsc'           => 'RAZRB000000',
                                  ], 'live');

        $this->assertNotNull($tpv);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'    => 'invalidMid',
                                      'status'         => Status::APPROVED,
                                  ], 'live');

        $this->assertNull($tpv);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'status'               => Status::APPROVED,
                                      'payer_account_number' => '10010101011',
                                  ], 'live');

        $this->assertNotNull($tpv);
    }

    public function testManualAutoApproveTpvFail()
    {
        $this->fixtures->on('live')->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => '10000000000000',
            'entity_id'      => '10000000000000',
            'account_number' => '10010101011',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => '11122275867',
                'merchant_id'    => '10000000000000',
                'balance'        => 30000
            ]);

        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00']);

        $this->fixtures->on('live')->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => '100ghi000ghi00',
            'entity_id'      => '100ghi000ghi00',
            'account_number' => '983672917383',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        array_push($request['content'], '100ghi000ghi00', '10000000000000', 'invalidMid');

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertEquals($response['total_count'], 3);

        $this->assertEquals($response['success_count'], 1);

        $this->assertEquals($response['failed_count'], 2);

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '100ghi000ghi00',
                'status'               => Status::APPROVED,
                'payer_account_number' => '983672917383',
                'payer_ifsc'           => 'RAZRB000000',
            ], 'live');

        $this->assertNull($tpv);

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'    => 'invalidMid',
                'status'         => Status::APPROVED,
            ], 'live');

        $this->assertNull($tpv);

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_account_number' => '10010101011',
            ], 'live');

        $this->assertNotNull($tpv);
    }

    public function enableRazorXTreatmentForPGLedgerCutoff()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::FAV_PG_LEDGER_CUTOFF))
                {
                    return 'on';
                }
                return 'control';
            });
    }

    public function testCreateTpvFromXDashboard()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], Role::OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000000', $ownerRoleUser->getId());

        $this->ba->addXOriginHeader();

        $this->enableRazorXTreatmentForPGLedgerCutoff();

        $response = $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $this->assertEquals($response[Entity::CREATED_BY], $merchant['name']);

        $this->assertFalse(isset($response[Entity::FUND_ACCOUNT_VALIDATION_ID]));

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'status'               => Status::PENDING,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);

        $fav = $this->getDbEntity('fund_account_validation',
                                  [
                                      'id' => $tpv->fund_account_validation_id,
                                  ]);

        $this->assertNotNull($fav);

        $this->assertEquals('100000Razorpay', $fav->getMerchantId());
    }

    public function testCreateTpvFromXDashboardWitInvalidMerchantBalanceId()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000099',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000099, ['live' => 1]);

        $ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000099', [], Role::OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000099', $ownerRoleUser->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    //all users should be able to create TPV at X dashboard
    public function testCreateTpvFromXDashboardAdminUser()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $adminRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], Role::ADMIN);

        $this->ba->proxyAuth('rzp_test_10000000000000', $adminRoleUser->getId());

        $this->ba->addXOriginHeader();

        $this->enableRazorXTreatmentForPGLedgerCutoff();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'status'               => Status::PENDING,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);

        $fav = $this->getDbEntity('fund_account_validation',
                                  [
                                      'merchant_id' => '100000Razorpay',
                                  ]);

        $this->assertNotNull($fav);
    }

    // Test creation of tpv with prepended zeros from admin create route
    public function testAdminTpvCreateWithPrependedZerosInPayerAccountNumber()
    {
        $this->ba->adminAuth();

        $fav = $this->getFundAccountValidationInput();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] = $fav['id'];

        $response = & $this->testData[__FUNCTION__]['response'];

        $response['content'][Entity::FUND_ACCOUNT_VALIDATION_ID] =
            FundAccountValidation::verifyIdAndSilentlyStripSign($fav['id']);

        $response = $this->startTest();

        $payerAccountNumber = (string) $request['content'][Entity::PAYER_ACCOUNT_NUMBER];

        $fav = $this->getDbEntity('fund_account_validation',
                                  [
                                      'id' => $response[Entity::FUND_ACCOUNT_VALIDATION_ID],
                                  ]);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'payer_account_number' => $payerAccountNumber,
                                      'status'               => Status::APPROVED,
                                  ]);

        $this->assertNotNull($fav);

        $this->assertNotNull($tpv);

        // Assert that we don't get this key in the response (we don't want to show it to merchants/ops)
        $this->assertArrayNotHasKey('trimmed_payer_account_number', $response);

        $trimmedPayerAccountNumber = $tpv->getTrimmedPayerAccountNumber();

        $this->assertEquals(ltrim($payerAccountNumber, '0'), $trimmedPayerAccountNumber);
    }

    public function testDisableTpvForLiveDisabledMerchant()
    {
        $this->fixtures->edit('merchant', 10000000000000, ['live' => 0]);

        $attribute = [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ];

        $this->fixtures->create('merchant_detail', $attribute);

        $ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], Role::OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000000', $ownerRoleUser->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    // Test creation of tpv with prepended zeros from dashboard
    public function testCreateTpvFromXDashboardAdminUserWithPrependedZerosInPayerAccountNumber()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $adminRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], Role::ADMIN);

        $this->ba->proxyAuth('rzp_test_10000000000000', $adminRoleUser->getId());

        $this->ba->addXOriginHeader();

        $request = & $this->testData[__FUNCTION__]['request'];

        $this->enableRazorXTreatmentForPGLedgerCutoff();

        $response = $this->startTest();

        $payerAccountNumber = (string) $request['content'][Entity::PAYER_ACCOUNT_NUMBER];

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'status'               => Status::PENDING,
                                      'payer_account_number' => $payerAccountNumber,
                                  ]);

        $this->assertNotNull($tpv);

        $fav = $this->getDbEntity('fund_account_validation',
                                  [
                                      'merchant_id' => '100000Razorpay',
                                  ]);

        $this->assertNotNull($fav);

        // Assert that we don't get this key in the response (we don't want to show it to merchants/ops)
        $this->assertArrayNotHasKey('trimmed_payer_account_number', $response);

        $trimmedPayerAccountNumber = $tpv->getTrimmedPayerAccountNumber();

        $this->assertEquals(ltrim($payerAccountNumber, '0'), $trimmedPayerAccountNumber);
    }

    // This is just a functionality check, we don't expect admin to edit the payer account number but if they edit it
    // anyways, we check that the trimmed payer account number column is updated as well.
    public function testAdminEditTpvWithPrependedZerosInPayerAccountNumber()
    {
        $this->testAdminTpvCreateWithPrependedZerosInPayerAccountNumber();

        $tpv = $this->getDbLastEntity('banking_account_tpv');

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = $request['url'] . $tpv->getId();

        $this->ba->adminAuth();

        $response = $this->startTest();

        $payerAccountNumber = (string) $request['content'][Entity::PAYER_ACCOUNT_NUMBER];

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::REJECTED,
                                      'payer_account_number' => $payerAccountNumber,
                                  ]);

        $this->assertNotNull($tpv);

        // Assert that we don't get this key in the response (we don't want to show it to merchants/ops)
        $this->assertArrayNotHasKey('trimmed_payer_account_number', $response);

        $trimmedPayerAccountNumber = $tpv->getTrimmedPayerAccountNumber();

        $this->assertEquals(ltrim($payerAccountNumber, '0'), $trimmedPayerAccountNumber);
    }

    // This test checks that if a duplicate tpv account is added with extra zeros in the payer account column, the tpv
    // creation will fail.
    public function testAdminTpvCreateDuplicateWithPrependedZerosException()
    {
        $this->testAdminTpvCreateWithPrependedZerosInPayerAccountNumber();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function getFundAccountValidationInput()
    {
        $this->fixtures->create('fund_account_validation', [
            FundAccountValidation::ACCOUNT_STATUS => "active",
            FundAccountValidation::NOTES          => [
                FundAccountValidation::MERCHANT_ID => '10000000000000',
            ],
        ]);

        return $this->getLastEntity('fund_account_validation', true, 'test');
    }

    public function getTpvInput(array $input = [])
    {
        $default = [
            Entity::MERCHANT_ID          => '10000000000000',
            Entity::BALANCE_ID           => '10000000000000',
            Entity::STATUS               => Status::APPROVED,
            Entity::PAYER_NAME           => 'Razorpay',
            Entity::PAYER_ACCOUNT_NUMBER => '98711120003344',
            Entity::PAYER_IFSC           => 'CITI0000006',
            Entity::CREATED_BY           => 'OPS_A',
            Entity::TYPE                 => Type::BANK_ACCOUNT,
            Entity::IS_ACTIVE            => true,
        ];

        return array_merge($default, $input);
    }

    public function testLimitOnSourceAccounts()
    {
        $attribute = [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ];

        $this->fixtures->create('merchant_detail', $attribute);

        $attributes = $this->getTpvInput();

        for ($i = 0; $i < 40; $i++)
        {
            $this->fixtures->create('banking_account_tpv', $attributes);
        }

        $ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], Role::OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000000', $ownerRoleUser->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    protected function mockRazorEnableXDenyUauthorisedAndDisableCAC()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'razorpay_x_acl_deny_unauthorised')
                    {
                        return 'on';
                    }
                    if ($feature === 'rx_custom_access_control_enabled')
                    {
                        return 'off';
                    }
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }
                    return 'off';
                }));
    }

    protected function mockSplitzDisableCAC()
    {
        $this->mockSplitzTreatment([
            'id'            => '10000000000000',
            'experiment_id' => env('CAC_BLACKLIST_EXP_ID'),
        ], [
            'response' => [
                'variant' => [
                    'name' => 'active'
                ]
            ]
        ]);
    }

    public function testMerchantTpvCreateRouteViaBankingProductForViewOnlyRole()
    {
        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->mockRazorEnableXDenyUauthorisedAndDisableCAC();

        $this->mockSplitzDisableCAC();

        $this->startTest();
    }

    public function testMerchantTpvCreateRouteViaBankingProductForOperationsRole()
    {
        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'operations');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->mockRazorEnableXDenyUauthorisedAndDisableCAC();

        $this->mockSplitzDisableCAC();

        $this->startTest();
    }

    public function testMerchantTpvCreateRouteViaBankingProductForOwnerRole()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorEnableXDenyUauthorisedAndDisableCAC();

        $this->ba->addXOriginHeader();

        $this->enableRazorXTreatmentForPGLedgerCutoff();

        $response = $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $this->assertEquals($response['created_by'], $merchant['name']);

        $this->assertFalse(isset($response['fund_account_validation_id']));

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '10000000000000',
                'status'               => 'pending',
                'payer_account_number' => '98711120003344',
            ]);

        $this->assertNotNull($tpv);

        $fav = $this->getDbEntity('fund_account_validation',
            [
                'id' => $tpv->fund_account_validation_id,
            ]);

        $this->assertNotNull($fav);

        $this->assertEquals('100000Razorpay', $fav->getMerchantId());
    }

    public function testMerchantTpvCreateRouteViaBankingProductForFinanceL1Role()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l1');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorEnableXDenyUauthorisedAndDisableCAC();

        $this->ba->addXOriginHeader();

        $this->enableRazorXTreatmentForPGLedgerCutoff();

        $response = $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $this->assertEquals($response['created_by'], $merchant['name']);

        $this->assertFalse(isset($response['fund_account_validation_id']));

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '10000000000000',
                'status'               => 'pending',
                'payer_account_number' => '98711120003344',
            ]);

        $this->assertNotNull($tpv);

        $fav = $this->getDbEntity('fund_account_validation',
            [
                'id' => $tpv->fund_account_validation_id,
            ]);

        $this->assertNotNull($fav);

        $this->assertEquals('100000Razorpay', $fav->getMerchantId());
    }

    public function testMerchantTpvCreateRouteViaBankingProductForAdminRole()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 1]);

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'admin');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorEnableXDenyUauthorisedAndDisableCAC();

        $this->ba->addXOriginHeader();

        $this->enableRazorXTreatmentForPGLedgerCutoff();

        $response = $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $this->assertEquals($response['created_by'], $merchant['name']);

        $this->assertFalse(isset($response['fund_account_validation_id']));

        $tpv = $this->getDbEntity('banking_account_tpv',
            [
                'merchant_id'          => '10000000000000',
                'status'               => 'pending',
                'payer_account_number' => '98711120003344',
            ]);

        $this->assertNotNull($tpv);

        $fav = $this->getDbEntity('fund_account_validation',
            [
                'id' => $tpv->fund_account_validation_id,
            ]);

        $this->assertNotNull($fav);

        $this->assertEquals('100000Razorpay', $fav->getMerchantId());
    }

    public function testMerchantFetchTpvsRouteViaBankingProductForViewOnlyRole()
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '10000000000000',
                'business_type'     => '2',
            ];

        $this->fixtures->create('merchant_detail', $attribute);

        $attributes = $this->getTpvInput();

        $this->fixtures->create('banking_account_tpv', $attributes);

        $attributes[Entity::STATUS] = Status::REJECTED;

        $attributes[Entity::IS_ACTIVE] = false;

        $attributes[Entity::REMARKS] = 'Invalid docs';

        $attributes[Entity::PAYER_ACCOUNT_NUMBER] = '8927398273';

        $this->fixtures->create('banking_account_tpv', $attributes);

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorEnableXDenyUauthorisedAndDisableCAC();

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    // Source Account Addition for Rx Wallet flows
    public function testBankingAccountTpvApproval_SuccessfulSourceAccountAdditionForRxWalletBalanceId()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $attributes = $this->getTpvInput(
            [
                Entity::STATUS    => Status::PENDING,
                Entity::IS_ACTIVE => false
            ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $this->fixtures->edit('balance', $this->getTpvInput()[Entity::BALANCE_ID], [
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::APPROVED,
                                      'is_active'            => true,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testBankingAccountTpvApproval_SourceAccountAdditionMozartFailureWithNonActionableErrorCode()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $expectedErrorResponse = [
            "success"           => false,
            "error"             => [
                "gateway_status_code"       => 200,
                "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "description"               => "",
                "gateway_error_code"        => "DCA018",
                "gateway_error_description" => "Source Account Number is already mapped to the client"
            ],
            "data"              => [
                "request_id"        => "24020231264",
                "status"            => "ERROR",
                "response_code"     => "DCA018",
                "response_message"  => "Source account has been added successfully.",
                "client_identifier" => "client_123"
            ],
            "next"              => [],
            "mozart_id"         => "cl7381c2b5po4din5q8g",
            "external_trace_id" => "5a31015763304f49dd130b9e6d4f0363"
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput, $expectedErrorResponse, true);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $attributes = $this->getTpvInput(
            [
                Entity::STATUS    => Status::PENDING,
                Entity::IS_ACTIVE => false
            ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $this->fixtures->edit('balance', $this->getTpvInput()[Entity::BALANCE_ID], [
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $testData = $this->testData['testBankingAccountTpvApproval_SuccessfulSourceAccountAdditionForRxWalletBalanceId'];

        $testData['request']['url'] .= $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest($testData);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::APPROVED,
                                      'is_active'            => true,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testBankingAccountTpvApproval_SourceAccountAdditionMozartFailureWithActionableErrorCode()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $expectedErrorResponse = [
            "success"           => false,
            "error"             => [
                "gateway_status_code"       => 200,
                "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "description"               => "",
                "gateway_error_code"        => "DCA020",
                "gateway_error_description" => "Unable to add source account, please try again later"
            ],
            "data"              => [
                "request_id"        => "24020231264",
                "status"            => "ERROR",
                "response_code"     => "DCA020",
                "response_message"  => "Unable to add source account, please try again later",
                "client_identifier" => "client_123"
            ],
            "next"              => [],
            "mozart_id"         => "cl7381c2b5po4din5q8g",
            "external_trace_id" => "5a31015763304f49dd130b9e6d4f0363"
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput, $expectedErrorResponse, true);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $attributes = $this->getTpvInput(
            [
                Entity::STATUS    => Status::PENDING,
                Entity::IS_ACTIVE => false
            ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $this->fixtures->edit('balance', $this->getTpvInput()[Entity::BALANCE_ID], [
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest();

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::PENDING,
                                      'is_active'            => false,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testBankingAccountTpvApproval_SourceAccountAdditionMozartFailureWithResponseValidationError()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $expectedErrorResponse = [
            "success"           => true,
            "error"             => [],
            "data"              => [
                "request_id"        => "24020231264",
                "status"            => "ERROR",
                "response_code"     => "DCA020",
                "response_message"  => "Unable to add source account, please try again later",
                "client_identifier" => "client_123"
            ],
            "next"              => [],
            "mozart_id"         => "cl7381c2b5po4din5q8g",
            "external_trace_id" => "5a31015763304f49dd130b9e6d4f0363"
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput, $expectedErrorResponse);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $attributes = $this->getTpvInput(
            [
                Entity::STATUS    => Status::PENDING,
                Entity::IS_ACTIVE => false
            ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $this->fixtures->edit('balance', $this->getTpvInput()[Entity::BALANCE_ID], [
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $testData = $this->testData['testBankingAccountTpvApproval_SourceAccountAdditionMozartFailureWithActionableErrorCode'];

        $testData['request']['url'] .= $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest($testData);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::PENDING,
                                      'is_active'            => false,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    public function testBankingAccountTpvApproval_BasCredentialsFetchFailure()
    {
        $this->mockBasFetchCredentialsToThrowError();

        $this->mockMozartResponseForSourceAccountAddition([], [], false, 0);

        $attributes = $this->getTpvInput(
            [
                Entity::STATUS    => Status::PENDING,
                Entity::IS_ACTIVE => false
            ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $attributes);

        $this->fixtures->edit('balance', $this->getTpvInput()[Entity::BALANCE_ID], [
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $testData = $this->testData['testBankingAccountTpvApproval_SourceAccountAdditionMozartFailureWithActionableErrorCode'];

        $testData['request']['url'] .= $tpv->getId();

        $this->ba->adminAuth();

        $this->startTest($testData);

        $tpv = $this->getDbEntity('banking_account_tpv',
                                  [
                                      'merchant_id'          => '10000000000000',
                                      'balance_id'           => '10000000000000',
                                      'payer_ifsc'           => 'CITI0000006',
                                      'status'               => Status::PENDING,
                                      'is_active'            => false,
                                      'payer_account_number' => '98711120003344',
                                  ]);

        $this->assertNotNull($tpv);
    }

    // Source Account Migration to Rx Wallet flows for existing merchants
    public function testBankingAccountTpvMigration_SuccessfullyPushedIntoQueue()
    {
        Queue::fake();

        // Create Entities for merchant: 10000000000000

        $this->fixtures->create('balance', [
            'id'           => 'rxbal000000000',
            'merchant_id'  => '10000000000000',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $this->fixtures->create('banking_account_tpv', $this->getTpvInput(
            [
                Entity::ID => 'tpv00000000001',
            ]
        ));

        $this->fixtures->create('banking_account_tpv', $this->getTpvInput(
            [
                Entity::ID      => 'tpv00000000002',
                Entity::REMARKS => Constants::RX_WALLET_TPV_MIGRATION_FAILURE
            ])
        );

        // Create Entities for merchant: 10000000000001

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->fixtures->create('balance', [
            'id'           => 'bal00000000001',
            'merchant_id'  => '10000000000001',
            'account_type' => AccountType::SHARED,
            'channel'      => Channel::YESBANK,
        ]);

        $this->fixtures->create('balance', [
            'id'           => 'rxbal000000001',
            'merchant_id'  => '10000000000001',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
        ]);

        $this->fixtures->create('banking_account_tpv', $this->getTpvInput(
            [
                Entity::ID          => 'tpv00000000003',
                Entity::BALANCE_ID  => 'bal00000000001',
                Entity::MERCHANT_ID => '10000000000001',
                Entity::REMARKS     => Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL
            ]
        ));

        $this->fixtures->create('banking_account_tpv', $this->getTpvInput(
            [
                Entity::ID          => 'tpv00000000004',
                Entity::BALANCE_ID  => 'rxbal000000001',
                Entity::MERCHANT_ID => '10000000000001',
            ])
        );

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['failure']));
        $this->assertEquals(2, count($response['success']));
        $this->assertEquals(2, count($response['success']['10000000000000']['dispatch_successful']));
        $this->assertEquals(0, count($response['success']['10000000000000']['dispatch_failure']));

        $this->assertEquals(0, count($response['success']['10000000000001']['dispatch_successful']));
        $this->assertEquals(0, count($response['success']['10000000000001']['dispatch_failure']));

        Queue::assertPushed(BankingAccountTpvMigration::class, 2);
    }

    public function testBankingAccountTpvMigrationJob_Success()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $balance = $this->fixtures->create('balance', [
            'id'           => 'bal00000000000',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
            'type'         => 'banking',
        ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $this->getTpvInput());

        (new BankingAccountTpvMigration(Mode::TEST, $tpv->getId(), $balance->getId()))->handle();

        $tpv->reload();

        $migratedTpv = $this->getDbEntity('banking_account_tpv', [
            'balance_id' => $balance->getId(),
        ]);

        $this->assertNotEquals($migratedTpv->getId(), $tpv->getId());
        $this->assertEquals(Status::APPROVED, $migratedTpv->getStatus());
        $this->assertEquals(true, $migratedTpv->getIsActive());
        $this->assertEquals(Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL, $tpv->getRemarks());
    }

    public function testBankingAccountTpvMigrationJob_AlreadyMigrated()
    {
        $tpv = $this->fixtures->create('banking_account_tpv', $this->getTpvInput(
            [
                Entity::REMARKS => Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL,
            ]
        ));

        $balance = $this->fixtures->create('balance', [
            'id'           => 'bal00000000000',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
            'type'         => 'banking',
        ]);

        (new BankingAccountTpvMigration(Mode::TEST, $tpv->getId(), $balance->getId()))->handle();

        $tpv->reload();

        $migratedTpv = $this->getDbEntity('banking_account_tpv', [
            'balance_id' => $balance->getId(),
        ]);

        $this->assertNull($migratedTpv);
        $this->assertEquals(Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL, $tpv->getRemarks());
    }

    public function testBankingAccountTpvMigrationJob_BankingAccountTpvNotSuitableForMigration()
    {
        $tpv = $this->fixtures->create('banking_account_tpv', $this->getTpvInput(
            [
                Entity::STATUS     => Status::PENDING,
                Entity::IS_ACTIVE  => false,
            ]));

        $balance = $this->fixtures->create('balance', [
            'id'           => 'bal00000000000',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
            'type'         => 'banking',
        ]);

        (new BankingAccountTpvMigration(Mode::TEST, $tpv->getId(), $balance->getId()))->handle();

        $tpv->reload();

        $migratedTpv = $this->getDbEntity('banking_account_tpv', [
            'balance_id' => $balance->getId(),
        ]);

        $this->assertNull($migratedTpv);
        $this->assertEquals(null, $tpv->getRemarks());
    }

    public function testBankingAccountTpvMigrationJob_SourceAccountMigrationFailedWithActionableErrorCode()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $expectedErrorResponse = [
            "success"           => false,
            "error"             => [
                "gateway_status_code"       => 200,
                "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "description"               => "",
                "gateway_error_code"        => "DCA020",
                "gateway_error_description" => "Unable to add source account, please try again later"
            ],
            "data"              => [
                "request_id"        => "24020231264",
                "status"            => "ERROR",
                "response_code"     => "DCA020",
                "response_message"  => "Unable to add source account, please try again later",
                "client_identifier" => "client_123"
            ],
            "next"              => [],
            "mozart_id"         => "cl7381c2b5po4din5q8g",
            "external_trace_id" => "5a31015763304f49dd130b9e6d4f0363"
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput, $expectedErrorResponse, true);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $balance = $this->fixtures->create('balance', [
            'id'           => 'bal00000000000',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
            'type'         => 'banking',
        ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $this->getTpvInput());

        (new BankingAccountTpvMigration(Mode::TEST, $tpv->getId(), $balance->getId()))->handle();

        $tpv->reload();

        $migratedTpv = $this->getDbEntity('banking_account_tpv', [
            'balance_id' => $balance->getId(),
        ]);

        $this->assertNull($migratedTpv);
        $this->assertEquals(Status::APPROVED, $tpv->getStatus());
        $this->assertEquals(true, $tpv->getIsActive());
        $this->assertEquals(Constants::RX_WALLET_TPV_MIGRATION_FAILURE, $tpv->getRemarks());
    }

    public function testBankingAccountTpvMigrationJob_SourceAccountMigrationFailedWithNonActionableErrorCode()
    {
        $expectedMozartInput = [
            Constants::CLIENT_IDENTIFIER     => 'client_123',
            Constants::SOURCE_ACCOUNT_NUMBER => $this->getTpvInput()[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $this->getTpvInput()[Entity::PAYER_IFSC],
        ];

        $expectedErrorResponse = [
            "success"           => false,
            "error"             => [
                "gateway_status_code"       => 200,
                "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "description"               => "",
                "gateway_error_code"        => "DCA018",
                "gateway_error_description" => "Source Account Number is already mapped to the client"
            ],
            "data"              => [
                "request_id"        => "24020231264",
                "status"            => "ERROR",
                "response_code"     => "DCA018",
                "response_message"  => "Source account has been added successfully.",
                "client_identifier" => "client_123"
            ],
            "next"              => [],
            "mozart_id"         => "cl7381c2b5po4din5q8g",
            "external_trace_id" => "5a31015763304f49dd130b9e6d4f0363"
        ];

        $this->mockMozartResponseForSourceAccountAddition($expectedMozartInput, $expectedErrorResponse, true);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $balance = $this->fixtures->create('balance', [
            'id'           => 'bal00000000000',
            'account_type' => AccountType::RX_WALLET,
            'channel'      => Channel::YESBANK,
            'type'         => 'banking',
        ]);

        $tpv = $this->fixtures->create('banking_account_tpv', $this->getTpvInput());

        (new BankingAccountTpvMigration(Mode::TEST, $tpv->getId(), $balance->getId()))->handle();

        $tpv->reload();

        $migratedTpv = $this->getDbEntity('banking_account_tpv', [
            'balance_id' => $balance->getId(),
        ]);

        $this->assertNotEquals($migratedTpv->getId(), $tpv->getId());
        $this->assertEquals(Status::APPROVED, $migratedTpv->getStatus());
        $this->assertEquals(true, $migratedTpv->getIsActive());
        $this->assertEquals(Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL, $tpv->getRemarks());
    }
}
