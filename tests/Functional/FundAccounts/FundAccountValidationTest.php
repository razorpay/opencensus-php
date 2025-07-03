<?php

namespace RZP\Tests\Functional\FundAccount;

use App;
use Mockery;
use \RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Services\Mozart;
use RZP\Jobs\Transactions;
use RZP\Models\Admin\Admin;
use RZP\Services\DiagClient;
use RZP\Models\Pricing\Fee;
use RZP\Jobs\FavQueueForFTS;
use RZP\Jobs\FaVpaValidation;
use RZP\Gateway\Mozart\Action;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Detail;
use RZP\Services\Stork;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Services\FavService\Fetch;
use RZP\Services\FTS\FundTransfer;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Queue;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Exception\ServerNotFoundException;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Services\FavService\Create as FavServiceCreate;
use RZP\Services\FavService\Update as FavServiceUpdate;
use RZP\Services\FavService\Fetch as FavServiceFetch;
use RZP\Models\FundAccount\Validation\Entity as Validation;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class FundAccountValidationTest extends TestCase
{
    use MocksSplitz;
    use WebhookTrait;
    use AttemptTrait;
    use FundAccountTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use AttemptReconcileTrait;
    use FundAccountValidationTrait;

    const VALIDATION_UPDATE_MUTEX = "FUND_ACCOUNT_VALIDATION_BEING_UPDATED";

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FundAccountValidationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $this->ba->privateAuth();

        $this->mockStorkService();
    }

    public function testCreateValidationWithFundAccountId()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF=>'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $response = $this->startTest();

        $isEventValidated = false;

        $expectedProperties = [
            'fav'   => [
                'merchant_id'     => '10000000000000',
                'account_status'  => 'active',
                'status'          => 'completed'
            ]
        ];

        $this->verifyFAVStatusEvent('fund_account_validation.status', $expectedProperties, $isEventValidated);

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        $this->assertTrue($isEventValidated);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['utr']);
        $this->assertEquals('10000000000000', $fav['balance_id']);
        $this->assertEquals('INR', $fav['currency']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(1000000, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // utr should be present in response['results'] array
        $this->assertArrayKeysExist($response['results'], ['utr','account_status','registered_name']);

        return $response;
    }

    public function testCreateValidationWithChargeCollectionEventPush()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable',RazorxTreatment::SEND_CHARGE_COLLECTION_EVENT_RX => 'enable']);


        $fundAccountResponse = $this->createFundAccountBankAccount();

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        Queue::fake();

        $response = $this->startTest();

        $isEventValidated = false;

        $expectedProperties = [
            'fav'   => [
                'merchant_id'     => '10000000000000',
                'account_status'  => 'active',
                'status'          => 'completed'
            ]
        ];

        $this->verifyFAVStatusEvent('fund_account_validation.status', $expectedProperties, $isEventValidated);

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        $this->assertTrue($isEventValidated);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['utr']);
        $this->assertEquals('10000000000000', $fav['balance_id']);
        $this->assertEquals('INR', $fav['currency']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        // utr should be present in response['results'] array
        $this->assertArrayKeysExist($response['results'], ['utr','account_status','registered_name']);

        return $response;
    }

    private function mockDiag()
    {
        $diagMock = $this->getMockBuilder(DiagClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['trackEvent'])
            ->getMock();

        $this->app->instance('diag', $diagMock);
    }

    private function verifyFAVStatusEvent($eventName, $expectedProperties, &$isEventValidated)
    {
        $this->mockDiag();

        $this->app->diag->method('trackEvent')
            ->will($this->returnCallback(
                function (string $eventType,
                          string $eventVersion,
                          array $event,
                          array $properties) use ($eventName, $expectedProperties, &$isEventValidated)
                {
                    if ($event['group'] === 'fund_account_validation')
                    {
                        $this->assertEquals($eventName, $event['name']);
                        $this->assertArraySelectiveEquals($expectedProperties, $properties);
                        $isEventValidated = true;
                    }

                    return;
                }));
    }

    public function testPennilessVpaValidationWithBeneNameNotAllowedInResponse()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_RESPONSE_BENE_NAME_BLACKLIST => ['XX','Razorpay']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $bank_account = $this->getLastEntity('bank_account', true);

        $this->fixtures->edit('bank_account', $bank_account['id'], ['ifsc_code' => "SBIN0007109"]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] = $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] = $fundAccountResponse['id'];

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);
        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_' . $favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertEquals($bankAccount['id'], 'ba_' . $fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
    }

    public function testPennilessVpaValidationSuccess()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);


        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Penniless Customer', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('Penniless', $favUpdated[Entity::ERROR_DESCRIPTION]);
    }

    protected function mockBASResponseForFetchingBankingCredentials($exception = null): void
    {
        $basMock = $this->getMockBuilder(\RZP\Services\Mock\BankingAccountService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['fetchBankingCredentials'])
            ->getMock();

        $basMock->method('fetchBankingCredentials')
            ->willReturn([
                "id"            => "1234",
                "credentials"   => [
                    "bank_reference_number" => "123456",
                    "auth_password"         => "johndoe123",
                    "auth_username"         => "johndoe",
                    "client_id"             => "client_id",
                    "client_secret"         => "client_secret",
                    "corp_id"               => "123456",
                ],
                "extra_field_1" => "extra_field_1" // Added this to verify the strict validation
            ]);

        $this->app->instance('banking_account_service', $basMock);
    }

    protected function setUpForFavUsingRblValidateVpaApi(
        int $balance = 0,
        string $balanceType = AccountType::DIRECT,
        $channel = 'rbl',
        $merchantID = 'Fr3lebmRmT0Cpy',
        $status = 'migrated')
    {
        $this->fixtures->merchant->create(['id' => $merchantID]);
        $this->fixtures->merchant->edit($merchantID, ['business_banking' => 1]);
        $this->fixtures->merchant->activate();

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balance, $merchantID ,$balanceType, $channel);

        $bankingBalance->setAccountNumber(1234567890);
        $bankingBalance->save();

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde123456789',
            'account_number'        =>  $bankingBalance['account_number'],
            'balance_id'            =>  $bankingBalance['id'],
            'account_type'          =>  'current',
            'channel'               =>  $bankingBalance['channel'],
            'reference1'            =>  '123456',
            'merchant_id'           =>  $merchantID,
            'status'                =>  $status,
        ];

        $bankingaccount = $this->createBankingAccount($bankingAccountAttributes);

        $this->fixtures->merchant->addFeatures(['penniless_validation', 'vpa_bank_info_enabled']);

        (new AdminService())->setConfigKeys(
            [
                ConfigKey::RBL_VPA_VALIDATE_API_SESSION_TOKEN => "session_1234567890",
                configKey::RBL_VPA_VALIDATE_API_GATEWAY_AUTH_TOKEN => "gateway_auth_1234567890"
            ]);

        $this->mockBASResponseForFetchingBankingCredentials();

        $keys = ['bcagent_username', 'bcagent_password', 'hmacKey', 'payerVpa', 'aggrOrgId', 'bcagent', 'mrchOrgId'];

        $count = 0;
        foreach ($keys as $key)
        {
            $this->fixtures->create('banking_account_detail', [
                'id' => 'ABCde12345677' . $count,
                'banking_account_id' => $bankingaccount['id'],
                'merchant_id' => $merchantID,
                'gateway_key' => $key,
                'gateway_value' => $key . '_value'
            ]);

            $count += 1;
        }
    }

    public function mockMozart_SuccessResponse()
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $validateVpaMozartSuccessResponse = [
            "data"=> [
                "bank_status_code"=> "SUCCESS",
                "description"=> "Test User",
                "ifsc_code"=> "HDFC0000705"
            ],
            "error"=> null,
            "external_trace_id"=> "DUMMY_REQUEST_ID",
            "mozart_id"=> "DUMMY_REQUEST_ID",
            "next"=> [],
            "success"=> true
        ];

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) {
                $validateVpaMozartRequest = [
                    'fund_account' =>
                        [
                            'vpa' =>
                                [
                                    'handle' => 'razorpay',
                                    'username' => 'withname',
                                ],
                        ],
                    'source_account' =>
                        [
                            'credentials' =>
                                [
                                    'auth_username' => 'johndoe',
                                    'auth_password' => 'johndoe123',
                                    'client_id' => 'client_id',
                                    'client_secret' => 'client_secret',
                                    'corp_id' => '123456',
                                    'payerVpa' => 'payerVpa_value',
                                    'bcagent' => 'bcagent_value',
                                    'bcagent_username' => 'bcagent_username_value',
                                    'bcagent_password' => 'bcagent_password_value',
                                    'hmacKey' => 'hmacKey_value',
                                    'mrchOrgId' => 'mrchOrgId_value',
                                    'aggrOrgId' => 'aggrOrgId_value',
                                ],
                        ],
                    'gateway_auth' =>
                        [
                            'token' => 'gateway_auth_1234567890',
                        ],
                    'gateway_session' =>
                        [
                            'token' => 'session_1234567890',
                        ],
                ];

                $this->assertArraySelectiveEquals($validateVpaMozartRequest, $input);

                return true;

            })->andReturn($validateVpaMozartSuccessResponse);

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_Success()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_SuccessResponse();

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Test User', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
        $this->assertEquals('HDFC0000705', $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals('HDFC Bank', $favUpdated->toArrayPublic()['results']['bank_name']);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['notes']['result_ifsc_code']);
    }

    public function testVpaCompositeValidationUsingRblValidateApi_Success()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->testData[__FUNCTION__] = $this->testData['testCompositeFavValidationUsingRblValidateApi'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_SuccessResponse();

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $favUpdated->setIsCompositeResponse(true);

        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('Test User', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
        $this->assertEquals('HDFC Bank', $favUpdated->toArrayPublic()['validation_results']['bank_name']);
        $this->assertEquals('HDFC0000705', $favUpdated->toArrayPublic()['validation_results']['ifsc']);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['notes']['result_ifsc_code']);
    }

    public function testGetFavCreatedUsingRblValidateApi()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->testVpaValidationUsingRblValidateApi_Success();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCompositeFavCreatedUsingRblValidateApi()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->testVpaCompositeValidationUsingRblValidateApi_Success();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function mockMozart_InvalidSuccessToken(): void
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $retryCount = 0;

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) use (& $retryCount) {
                if ($action === Action::VALIDATE_VPA) {
                    $mozartRequest = [
                        'fund_account' =>
                            [
                                'vpa' =>
                                    [
                                        'handle' => 'razorpay',
                                        'username' => 'withname',
                                    ],
                            ],
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                        'gateway_auth' =>
                            [
                                'token' => 'gateway_auth_1234567890',
                            ],
                        'gateway_session' =>
                            [
                                'token' => ($retryCount === 0) ? 'session_1234567890' : 'session_token_2'
                            ],
                    ];
                }
                elseif ($action === Action::GATEWAY_SESSION)
                {
                    $mozartRequest = [
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                    ];
                }

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) use (& $retryCount) {
                if ($action == Action::VALIDATE_VPA)
                {
                    if ($retryCount === 0) {
                        throw new GatewayErrorException(
                            ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                            "Your Session has been Expired or Invalid.Please Relogin the Application",
                            "Your Session has been Expired or Invalid.Please Relogin the Application",
                            [
                                'error' => [
                                    "description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "gateway_error_code" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "gateway_error_description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "gateway_status_code" => 200,
                                    "internal_error_code" => "AUTHORIZATION_FAILED_RETRIABLE"
                                ],
                                'data' => [
                                    "bank_status_code" => "FAILED",
                                    "description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "status" => 0
                                ],
                            ]);
                    }
                    elseif ($retryCount === 1)
                    {
                        return [
                            "data"=> [
                                "bank_status_code"=> "SUCCESS",
                                "description"=> "Test User",
                                "ifsc_code"=> "HDFC0000705"
                            ],
                            "error"=> null,
                            "external_trace_id"=> "DUMMY_REQUEST_ID",
                            "mozart_id"=> "DUMMY_REQUEST_ID",
                            "next"=> [],
                            "success"=> true
                        ];
                    }
                }
                else if ($action == Action::GATEWAY_SESSION)
                {
                    $retryCount += 1;

                    return [
                        "data"=> [
                            "gateway_session"=> [
                                "token"=> "session_token_2",
                                "token_type"=> "sessionToken",
                                "validity_duration"=> "9/16/2020 9=>50=>05 PM"
                            ]
                        ],
                        "error"=> null,
                        "external_trace_id"=> "DUMMY_REQUEST_ID",
                        "mozart_id"=> "DUMMY_REQUEST_ID",
                        "next"=> [],
                        "success"=> true
                    ];
                }
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_InvalidSessionToken()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_InvalidSuccessToken();

        $this->startTest();

        $fav     = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));
        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $sessionToken = (new AdminService())->getConfigKey(
            ['key' => configKey::RBL_VPA_VALIDATE_API_SESSION_TOKEN]
        );

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Test User', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
        $this->assertEquals('HDFC0000705', $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['notes']['result_ifsc_code']);
        $this->assertEquals('session_token_2', $sessionToken);
    }

    public function mockMozart_ValidateVpa_RetryExhausted(): void
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $retryCount = 0;

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) use (& $retryCount) {
                if ($action === Action::VALIDATE_VPA) {
                    $mozartRequest = [
                        'fund_account' =>
                            [
                                'vpa' =>
                                    [
                                        'handle' => 'razorpay',
                                        'username' => 'withname',
                                    ],
                            ],
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                        'gateway_auth' =>
                            [
                                'token' => 'gateway_auth_1234567890',
                            ],
                        'gateway_session' =>
                            [
                                'token' => ($retryCount === 0) ? 'session_1234567890' : 'session_token_2'
                            ],
                    ];
                }
                elseif ($action === Action::GATEWAY_SESSION)
                {
                    $mozartRequest = [
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                    ];
                }

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) use (& $retryCount) {
                if ($action == Action::VALIDATE_VPA)
                {
                    throw new GatewayErrorException(
                        ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                        "Your Session has been Expired or Invalid.Please Relogin the Application",
                        "Your Session has been Expired or Invalid.Please Relogin the Application",
                        [
                            'error' => [
                                "description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                "gateway_error_code" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                "gateway_error_description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                "gateway_status_code" => 200,
                                "internal_error_code" => "AUTHORIZATION_FAILED_RETRIABLE"
                            ],
                            'data' => [
                                "bank_status_code" => "FAILED",
                                "description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                "status" => 0
                            ],
                        ]);

                }
                else if ($action == Action::GATEWAY_SESSION)
                {
                    $retryCount += 1;

                    return [
                        "data"=> [
                            "gateway_session"=> [
                                "token"=> "session_token_2",
                                "token_type"=> "sessionToken",
                                "validity_duration"=> "9/16/2020 9=>50=>05 PM"
                            ]
                        ],
                        "error"=> null,
                        "external_trace_id"=> "DUMMY_REQUEST_ID",
                        "mozart_id"=> "DUMMY_REQUEST_ID",
                        "next"=> [],
                        "success"=> true
                    ];
                }
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_RetryExhausted()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_ValidateVpa_RetryExhausted();

        $this->startTest();

        $fav     = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));
        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $this->assertEquals(null, $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['bank_name']);
        $this->assertEquals('failed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR: Your Session has been Expired or Invalid.Please Relogin the Application', $favUpdated['internal_error_code']);
    }

    public function mockMozart_InvalidSuccessToken_FetchSessionTokenFailure(): void
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $retryCount = 0;

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) use (& $retryCount) {
                if ($action === Action::VALIDATE_VPA) {
                    $mozartRequest = [
                        'fund_account' =>
                            [
                                'vpa' =>
                                    [
                                        'handle' => 'razorpay',
                                        'username' => 'withname',
                                    ],
                            ],
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                        'gateway_auth' =>
                            [
                                'token' => 'gateway_auth_1234567890',
                            ],
                        'gateway_session' =>
                            [
                                'token' => ($retryCount === 0) ? 'session_1234567890' : 'session_token_2'
                            ],
                    ];
                }
                elseif ($action === Action::GATEWAY_SESSION)
                {
                    $mozartRequest = [
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                    ];
                }

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) use (& $retryCount) {
                if ($action == Action::VALIDATE_VPA)
                {
                    if ($retryCount === 0) {
                        throw new GatewayErrorException(
                            ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                            "Your Session has been Expired or Invalid.Please Relogin the Application",
                            "Your Session has been Expired or Invalid.Please Relogin the Application",
                            [
                                'error' => [
                                    "description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "gateway_error_code" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "gateway_error_description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "gateway_status_code" => 200,
                                    "internal_error_code" => "AUTHORIZATION_FAILED_RETRIABLE"
                                ],
                                'data' => [
                                    "bank_status_code" => "FAILED",
                                    "description" => "Your Session has been Expired or Invalid.Please Relogin the Application",
                                    "status" => 0
                                ],
                            ]);
                    }
                    elseif ($retryCount === 1)
                    {
                        return [
                            "data"=> [
                                "bank_status_code"=> "SUCCESS",
                                "description"=> "Test User",
                                "ifsc_code"=> "HDFC0000705"
                            ],
                            "error"=> null,
                            "external_trace_id"=> "DUMMY_REQUEST_ID",
                            "mozart_id"=> "DUMMY_REQUEST_ID",
                            "next"=> [],
                            "success"=> true
                        ];
                    }
                }
                else if ($action == Action::GATEWAY_SESSION)
                {
                    throw new GatewayErrorException(
                        ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                        "401",
                        "Unauthorized",
                        [
                            'error' => [
                                "description" => "Unauthorized",
                                "gateway_error_code" => "401",
                                "gateway_error_description" => "Unauthorized",
                                "gateway_status_code" => 200,
                                "internal_error_code" => "AUTHENTICATION_FAILED"
                            ],
                            'data' => [
                                "gateway_session"=> [
                                "token"=> null,
                                "token_type"=> "sessionToken",
                                "validity_duration"=> null
                                ]
                            ],
                        ]);
                }
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_InvalidSessionToken_FetchSessionTokenFailure()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_InvalidSuccessToken_FetchSessionTokenFailure();

        $this->startTest();

        $fav     = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $this->assertEquals(null, $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals('failed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR: Unauthorized', $favUpdated['internal_error_code']);
    }

    public function mockMozart_InvalidGatewayAuthToken(): void
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $retryCount = 0;

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) use (& $retryCount) {
                if ($action === Action::VALIDATE_VPA) {
                    $mozartRequest = [
                        'fund_account' =>
                            [
                                'vpa' =>
                                    [
                                        'handle' => 'razorpay',
                                        'username' => 'withname',
                                    ],
                            ],
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                        'gateway_auth' =>
                            [
                                'token' => ($retryCount === 0) ? 'gateway_auth_1234567890' : "Z2F0ZXdheV9hdXRoX3Rva2VuXzI=",
                            ],
                        'gateway_session' =>
                            [
                                'token' => 'session_1234567890'
                            ],
                    ];
                }
                elseif ($action === Action::GATEWAY_AUTH)
                {
                    $mozartRequest = [
                        'source_account' =>
                            [
                                'credentials' =>
                                    [
                                        'auth_username' => 'johndoe',
                                        'auth_password' => 'johndoe123',
                                        'client_id' => 'client_id',
                                        'client_secret' => 'client_secret',
                                        'corp_id' => '123456',
                                        'payerVpa' => 'payerVpa_value',
                                        'bcagent' => 'bcagent_value',
                                        'bcagent_username' => 'bcagent_username_value',
                                        'bcagent_password' => 'bcagent_password_value',
                                        'hmacKey' => 'hmacKey_value',
                                        'mrchOrgId' => 'mrchOrgId_value',
                                        'aggrOrgId' => 'aggrOrgId_value',
                                    ],
                            ],
                        "gateway_session" => [
                            "token" => 'session_1234567890'
                        ]
                    ];
                }

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) use (& $retryCount) {
                if ($action == Action::VALIDATE_VPA)
                {
                    if ($retryCount === 0) {
                        throw new GatewayErrorException(
                            ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                            "E001:Invalid Auth token",
                            "Invalid Auth token",
                            [
                                'error' => [
                                    "description" => "Invalid Auth token",
                                    "gateway_error_code" => "E001:Invalid Auth token",
                                    "gateway_error_description" => "Invalid Auth token",
                                    "gateway_status_code" => 200,
                                    "internal_error_code" => "AUTHORIZATION_FAILED"
                                ],
                                'data' => [
                                    "bank_status_code" => "FAILED",
                                    "description" => "E001:Invalid Auth token"
                                ],
                            ]);
                    }
                    elseif ($retryCount === 1)
                    {
                        return [
                            "data"=> [
                                "bank_status_code"=> "SUCCESS",
                                "description"=> "Test User",
                                "ifsc_code"=> "HDFC0000705"
                            ],
                            "error"=> null,
                            "external_trace_id"=> "DUMMY_REQUEST_ID",
                            "mozart_id"=> "DUMMY_REQUEST_ID",
                            "next"=> [],
                            "success"=> true
                        ];
                    }
                }
                else if ($action == Action::GATEWAY_AUTH)
                {
                    $retryCount += 1;

                    return [
                        "data"=> [
                            "gateway_auth"=> [
                                "token"=> "gateway_auth_token_2",
                                "token_type"=> "authToken",
                                "validity_duration"=> "30"
                            ]
                        ],
                        "error"=> null,
                        "external_trace_id"=> "DUMMY_REQUEST_ID",
                        "mozart_id"=> "DUMMY_REQUEST_ID",
                        "next"=> [],
                        "success"=> true
                    ];
                }
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_InvalidGatewayAuthToken()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_InvalidGatewayAuthToken();

        $this->startTest();

        $fav     = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));
        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $authToken = (new AdminService())->getConfigKey(
            ['key' => configKey::RBL_VPA_VALIDATE_API_GATEWAY_AUTH_TOKEN]
        );

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Test User', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
        $this->assertEquals('HDFC0000705', $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['notes']['result_ifsc_code']);
        $this->assertEquals('Z2F0ZXdheV9hdXRoX3Rva2VuXzI=', $authToken);
    }

    public function mockMozart_UnknownGatewayError(): void
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) {
                $mozartRequest = [
                    'fund_account' =>
                        [
                            'vpa' =>
                                [
                                    'handle' => 'razorpay',
                                    'username' => 'withname',
                                ],
                        ],
                    'source_account' =>
                        [
                            'credentials' =>
                                [
                                    'auth_username' => 'johndoe',
                                    'auth_password' => 'johndoe123',
                                    'client_id' => 'client_id',
                                    'client_secret' => 'client_secret',
                                    'corp_id' => '123456',
                                    'payerVpa' => 'payerVpa_value',
                                    'bcagent' => 'bcagent_value',
                                    'bcagent_username' => 'bcagent_username_value',
                                    'bcagent_password' => 'bcagent_password_value',
                                    'hmacKey' => 'hmacKey_value',
                                    'mrchOrgId' => 'mrchOrgId_value',
                                    'aggrOrgId' => 'aggrOrgId_value',
                                ],
                        ],
                    'gateway_auth' =>
                        [
                            'token' => 'gateway_auth_1234567890',
                        ],
                    'gateway_session' =>
                        [
                            'token' => 'session_1234567890'
                        ],
                ];

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) {

                throw new GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
                    "SUCCESS",
                    "(No error description was mapped for this error code)",
                    [
                        'error' => [
                            "description" => "",
                            "gateway_error_code" => "SUCCESS",
                            "gateway_error_description" => "(No error description was mapped for this error code)",
                            "gateway_status_code" => 200,
                            "internal_error_code" => "GATEWAY_ERROR_UNKNOWN_ERROR"
                        ],
                        'data' => [
                            "bank_status_code" => "FAILED",
                            "description" => "",
                            "status" => ""
                        ],
                    ]);
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_UnknownGatewayError()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_UnknownGatewayError();

        $this->startTest();

        $fav     = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));
        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $this->assertEquals(null, $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals('failed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR: (No error description was mapped for this error code)', $favUpdated['internal_error_code']);
    }

    public function mockMozart_InvalidVpa(): void
    {
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) {
                $mozartRequest = [
                    'fund_account' =>
                        [
                            'vpa' =>
                                [
                                    'handle' => 'razorpay',
                                    'username' => 'withname',
                                ],
                        ],
                    'source_account' =>
                        [
                            'credentials' =>
                                [
                                    'auth_username' => 'johndoe',
                                    'auth_password' => 'johndoe123',
                                    'client_id' => 'client_id',
                                    'client_secret' => 'client_secret',
                                    'corp_id' => '123456',
                                    'payerVpa' => 'payerVpa_value',
                                    'bcagent' => 'bcagent_value',
                                    'bcagent_username' => 'bcagent_username_value',
                                    'bcagent_password' => 'bcagent_password_value',
                                    'hmacKey' => 'hmacKey_value',
                                    'mrchOrgId' => 'mrchOrgId_value',
                                    'aggrOrgId' => 'aggrOrgId_value',
                                ],
                        ],
                    'gateway_auth' =>
                        [
                            'token' => 'gateway_auth_1234567890',
                        ],
                    'gateway_session' =>
                        [
                            'token' => 'session_1234567890'
                        ],
                ];

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) {
                throw new GatewayErrorException(
                    ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
                    "",
                    "",
                    [
                        'error' => [
                            "description" => "",
                            "gateway_error_code" => "",
                            "gateway_error_description" => "(No error description was mapped for this error code)",
                            "gateway_status_code" => 200,
                            "internal_error_code" => "GATEWAY_ERROR_UNKNOWN_ERROR"
                        ],
                        'data' => [
                            "bank_status_code" => "FAILED",
                            "description" => "",
                            "status" => "0"
                        ],
                    ]);
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_InvalidVpa()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_InvalidVpa();

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $this->assertEquals('invalid', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['bank_name']);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('BAD_REQUEST_PAYMENT_UPI_INVALID_VPA', $favUpdated['internal_error_code']);
    }

    public function mockMozart_ServiceError(): void
    {
        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $mozartServiceMock->shouldReceive('sendMozartRequest')
            ->withArgs(function($namespace, $gateway, $action, $input) {
                $mozartRequest = [
                    'fund_account' =>
                        [
                            'vpa' =>
                                [
                                    'handle' => 'razorpay',
                                    'username' => 'withname',
                                ],
                        ],
                    'source_account' =>
                        [
                            'credentials' =>
                                [
                                    'auth_username' => 'johndoe',
                                    'auth_password' => 'johndoe123',
                                    'client_id' => 'client_id',
                                    'client_secret' => 'client_secret',
                                    'corp_id' => '123456',
                                    'payerVpa' => 'payerVpa_value',
                                    'bcagent' => 'bcagent_value',
                                    'bcagent_username' => 'bcagent_username_value',
                                    'bcagent_password' => 'bcagent_password_value',
                                    'hmacKey' => 'hmacKey_value',
                                    'mrchOrgId' => 'mrchOrgId_value',
                                    'aggrOrgId' => 'aggrOrgId_value',
                                ],
                        ],
                    'gateway_auth' =>
                        [
                            'token' => 'gateway_auth_1234567890',
                        ],
                    'gateway_session' =>
                        [
                            'token' => 'session_1234567890'
                        ],
                ];

                $this->assertArraySelectiveEquals($mozartRequest, $input);

                return true;
            })
            ->andReturnUsing(function(string $namespace, string $gateway, string $action, array  $input) {
                throw new ServerNotFoundException(
                    'Mozart Service Not Available',
                    ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_CALLED);
            });

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testVpaValidationUsingRblValidateApi_MozartServiceError()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__] = $this->testData['testFavValidationUsingRblValidateApi'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->setUpForFavUsingRblValidateVpaApi();

        $this->mockMozart_ServiceError();

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));

        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $favUpdated->setIsVpaBankInfoEnabledFlag();

        $this->assertEquals(null, $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals(null, $favUpdated->toArrayPublic()['results']['ifsc']);
        $this->assertEquals('created', $favUpdated[Entity::STATUS]);
    }

    public function testPennilessVpaValidationWithInvalidAccountStatus()
    {

        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $bank_account = $this->getLastEntity('bank_account', true);

        $this->fixtures->edit('bank_account', $bank_account['id'], ['ifsc_code' => "SBIN0007106"]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);
        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_'.$favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
    }

    public function testPennilessVpaValidationWithNameNull()
    {

        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $bank_account = $this->getLastEntity('bank_account', true);

        $this->fixtures->edit('bank_account', $bank_account['id'], ['ifsc_code' => "SBIN0007107"]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);
        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_'.$favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
    }

    public function testPennilessVpaValidationFailed()
    {

        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $bank_account = $this->getLastEntity('bank_account', true);

        $this->fixtures->edit('bank_account', $bank_account['id'], ['ifsc_code' => "SBIN0007108"]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);


        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);
        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_'.$favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
        $this->assertEquals(null, $favUpdated[Entity::ERROR_DESCRIPTION]);
    }

    public function testPennilessValidationWithWhitelistedBeneBank()
    {
        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);


        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Penniless Customer', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

    }

    public function testPennilessValidationWithBlacklistedBeneBank()
    {

        Queue::fake();

//        test case if of sbi account, but only hdfc is whitelisted
        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['HDFC']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);
        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_'.$favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
    }

    public function testValidateTypeVpaInternal()
    {
        Queue::fake();

        $this->ba->payoutInternalAppAuth();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $content = $this->testData[__FUNCTION__]['request']['content'];

        $mock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $mock);

        $input = [
            'account_status' => "active",
            'name' => "Razorpay Customer",
            'success' => true,
            'ifsc_code' => null,
            'fav_status' => "completed",
            'error_code' => null,
        ];

        $mock->shouldReceive('updateFavInMicroservice')
            ->withArgs(["fav_000000000000", $input, 'vpa'])
            ->times(1);

        $this->startTest();

        Queue::assertPushed(FaVpaValidation::class);

        $faVpaValidation = new FaVpaValidation('test', $content['fav_id'], $content);

        $faVpaValidation->handle();
    }

    public function testValidateTypeBankAccountInternal()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $mock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $mock);

        $mock->shouldReceive('handleBankWebhook')
            ->withArgs(function ($input, $bank) {
                $this->assertEquals('fts', $bank);
                $this->assertEquals('created', $input['status']);
                $this->assertNotEmpty($input['fund_transfer_id']);
                $this->assertNotEmpty($input['fund_account_id']);
                $this->assertEquals('fund transfer sent to fts.', $input['message']);
                return true;
            })
            ->andReturn(["status" => "success"]);

        // Create queue message
        $queueMessage = [
            'mode' => 'test',
            'id' => '12345678901234',
            'merchant_id' => '10000000000000',
            'amount' => 100,
            'is_validx' => true,
            'status' => 'initiated',
            'fund_account' => [
                'id' => $fundAccountResponse['id']
            ]
        ];

        // Dispatch the job
        FavQueueForFTS::dispatch($queueMessage);

        // Assert the job was pushed
        Queue::assertPushed(FavQueueForFTS::class);

        // Get the job and process it
        $job = Queue::pushed(FavQueueForFTS::class)[0];
        $job->handle();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'test');

        // Penny drop assertion
        $this->assertEquals("12345678901234", $fta['source_id']);
        $this->assertEquals('penny_testing', $fta['purpose']);
    }

    public function testFavFtsRequestWithRemitterDetails()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testPennilessValidationWithBlacklistedBeneBank'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn         = $this->getLastEntity('transaction', true);
        $balance     = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->edit('merchant_detail', '10000000000000', [
            Detail\Entity::COMPANY_PAN                    => "companyPAN",
            Detail\Entity::BUSINESS_NAME                  => "businessNAME",
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS    => "Line 1 Address",
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2 => "Line 2 Address",
            Detail\Entity::BUSINESS_REGISTERED_CITY       => "Bhubaneswar",
            Detail\Entity::BUSINESS_REGISTERED_PIN        => "751490",
        ]);

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
            'shouldAllowTransfersViaFts' => [true, 'Dummy'],
        ]);

        $mock->shouldReceive('createAndSendRequest')
            ->andReturnUsing(function(string $endpoint, string $method, array $input) {

                self::assertEquals('/transfer', $endpoint);
                self::assertEquals('POST', $method);

                self::assertEquals([
                    'merchant_detail' => [
                        'merchant_name'     =>  'businessNAME',
                        'merchant_pan'      =>  'companyPAN',
                        'merchant_address'  =>  'Line 1 Address, Line 2 Address, Bhubaneswar - 751490'
                    ],
                ], $input['transfer']['request_meta']);

                return [
                    'body' => [
                        'status'           => 'created',
                        'message'          => 'fund transfer sent to fts.',
                        'fund_transfer_id' => 11,
                        'fund_account_id'  => '12'
                    ],
                    'code' => 201,
                ];
            })->times(1);

        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_'.$favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
    }

    public function testFavFtsRequestWithoutRemitterDetails()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testPennilessValidationWithBlacklistedBeneBank'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);
        $txn         = $this->getLastEntity('transaction', true);
        $balance     = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        Queue::assertPushed(FavQueueForFTS::class);

        $payload = [
            'mode' => 'test',
            'id' => preg_replace('/^fav_/', '', $fav['id']),
        ];

        // Test worker
        $favQueueForFts = new FavQueueForFTS($payload);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
            'shouldAllowTransfersViaFts' => [true, 'Dummy'],
        ]);

        $mock->shouldReceive('createAndSendRequest')
            ->andReturnUsing(function(string $endpoint, string $method, array $input) {

                self::assertEquals('/transfer', $endpoint);
                self::assertEquals('POST', $method);
                self::assertNull($input['transfer']['request_meta']['merchant_detail']['name']);
                self::assertNull($input['transfer']['request_meta']['merchant_detail']['pan']);

                return [
                    'body' => [
                        'status'           => 'created',
                        'message'          => 'fund transfer sent to fts.',
                        'fund_transfer_id' => 11,
                        'fund_account_id'  => '12'
                    ],
                    'code' => 201,
                ];
            })->times(1);

        $favQueueForFts->handle();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'COMPLETED');

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Test', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);

        // Penny drop assertion
        $this->assertEquals('fav_'.$favUpdated['id'], $fta['source']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
    }

    public function testFavMicroServiceFtsRequestWithRemitterDetails()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->edit('merchant_detail', '10000000000000', [
            Detail\Entity::COMPANY_PAN                    => "companyPAN",
            Detail\Entity::BUSINESS_NAME                  => "businessNAME",
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS    => "Line 1 Address",
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2 => "Line 2 Address",
            Detail\Entity::BUSINESS_REGISTERED_CITY       => "Bhubaneswar",
            Detail\Entity::BUSINESS_REGISTERED_PIN        => "751490",
        ]);

        $mock = Mockery::mock(FundTransfer::class)->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
            'shouldAllowTransfersViaFts' => [true, 'Dummy'],
        ]);

        $mock->shouldReceive('createAndSendRequest')
            ->andReturnUsing(function(string $endpoint, string $method, array $input) {

                self::assertEquals('/transfer', $endpoint);
                self::assertEquals('POST', $method);

                self::assertEquals([
                    'merchant_detail' => [
                        'merchant_name'     =>  'businessNAME',
                        'merchant_pan'      =>  'companyPAN',
                        'merchant_address'  => 'Line 1 Address, Line 2 Address, Bhubaneswar - 751490'
                    ],
                ], $input['transfer']['request_meta']);

                return [
                    'body' => [
                        'status'           => 'created',
                        'message'          => 'fund transfer sent to fts.',
                        'fund_transfer_id' => 11,
                        'fund_account_id'  => '12'
                    ],
                    'code' => 201,
                ];
            })->times(1);

        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $favMock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $favMock);

        $favMock->shouldReceive('handleBankWebhook')
            ->withArgs(function ($input, $bank) {
                $this->assertEquals('fts', $bank);
                $this->assertEquals('created', $input['status']);
                $this->assertNotEmpty($input['fund_transfer_id']);
                $this->assertNotEmpty($input['fund_account_id']);
                $this->assertEquals('fund transfer sent to fts.', $input['message']);
                return true;
            })
            ->andReturn(["status" => "success"])
            ->times(1);

        // Create queue message
        $queueMessage = [
            'mode' => 'test',
            'id' => '12345678901234',
            'merchant_id' => '10000000000000',
            'amount' => 100,
            'is_validx' => true,
            'status' => 'initiated',
            'fund_account' => [
                'id' => $fundAccountResponse['id']
            ]
        ];

        // Dispatch the job
        FavQueueForFTS::dispatch($queueMessage);

        // Assert the job was pushed
        Queue::assertPushed(FavQueueForFTS::class);

        // Get the job and process it
        $job = Queue::pushed(FavQueueForFTS::class)[0];
        $job->handle();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'test');

        // Penny drop assertion
        $this->assertEquals("12345678901234", $fta['source_id']);
        $this->assertEquals('penny_testing', $fta['purpose']);
    }

    public function testFavMicroServiceFtsRequestWithoutRemitterDetails()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $mock = Mockery::mock(FundTransfer::class)->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
            'shouldAllowTransfersViaFts' => [true, 'Dummy'],
        ]);

        $mock->shouldReceive('createAndSendRequest')
            ->andReturnUsing(function(string $endpoint, string $method, array $input) {

                self::assertEquals('/transfer', $endpoint);
                self::assertEquals('POST', $method);
                self::assertNull($input['transfer']['request_meta']['merchant_detail']['name']);
                self::assertNull($input['transfer']['request_meta']['merchant_detail']['pan']);

                return [
                    'body' => [
                        'status'           => 'created',
                        'message'          => 'fund transfer sent to fts.',
                        'fund_transfer_id' => 11,
                        'fund_account_id'  => '12'
                    ],
                    'code' => 201,
                ];
            })->times(1);

        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $favMock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $favMock);

        $favMock->shouldReceive('handleBankWebhook')
            ->withArgs(function ($input, $bank) {
                $this->assertEquals('fts', $bank);
                $this->assertEquals('created', $input['status']);
                $this->assertNotEmpty($input['fund_transfer_id']);
                $this->assertNotEmpty($input['fund_account_id']);
                $this->assertEquals('fund transfer sent to fts.', $input['message']);
                return true;
            })
            ->andReturn(["status" => "success"])
            ->times(1);

        // Create queue message
        $queueMessage = [
            'mode' => 'test',
            'id' => '12345678901234',
            'merchant_id' => '10000000000000',
            'amount' => 100,
            'is_validx' => true,
            'status' => 'initiated',
            'fund_account' => [
                'id' => $fundAccountResponse['id']
            ]
        ];

        // Dispatch the job
        FavQueueForFTS::dispatch($queueMessage);

        // Assert the job was pushed
        Queue::assertPushed(FavQueueForFTS::class);

        // Get the job and process it
        $job = Queue::pushed(FavQueueForFTS::class)[0];
        $job->handle();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'test');

        // Penny drop assertion
        $this->assertEquals("12345678901234", $fta['source_id']);
        $this->assertEquals('penny_testing', $fta['purpose']);
    }


    public function testFetchPricingInfoForFavService()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] =  substr($fundAccountResponse['id'], 3);

        $this->ba->payoutInternalAppAuth();

        $response = $this->startTest();

        $this->assertEquals(354, $response['fees']);

        $this->assertEquals(54, $response['tax']);
    }

    public function testFetchPricingInfoForPostpaidMerchantForFavService()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] =  substr($fundAccountResponse['id'], 3);

        $this->ba->payoutInternalAppAuth();

        $response = $this->startTest();

        $this->assertEquals(0, $response['fees']);

        $this->assertEquals(0, $response['tax']);
    }

    public function createBankResponseForFavServiceMock(
        $status='created',
        $errorCode = null,
        $utr = null,
        $amount = null,
        $favType = 'composite')
    {
        $response = new \WpOrg\Requests\Response();

        $content = [
            'id'=> 'fav_00000000000001',
            'status'=> $status,
            'reference_id'=> '112233',
            'type' => $favType,
            'notes'=> [
                'random_key_1'=> 'Make it so.',
                'random_key_2'=> 'Tea. Earl Grey. Hot.'
            ],
            'created_at' => 1567064019,
            'error_code' => $errorCode,
            'utr' => $utr,
            'amount' => $amount,
            'merchant_id' => 'merch_123456',
            'fund_account' => [
                'id' => '',
            ],
            'currency' => 'INR',
            'validation_method' => 'penniless',
        ];

        $response->body = json_encode($content);
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }

    public function mockFavServiceCreate($case): void
    {
        $FavServiceCreateMock = $this->getMockBuilder(FavServiceCreate::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(FavServiceCreate::FAV_SERVICE_CREATE, $FavServiceCreateMock);

        switch ($case)
        {
            case 'ba_fav_composite_created_state':
                $response = $this->createBankResponseForFavServiceMock();
                break;
            case 'ba_fav_non_composite_created_state':
                $response = $this->createBankResponseForFavServiceMock(favType: 'non_composite', amount: 100);
                break;
        }

        $this->app->fav_service_create
                  ->expects($this->once())
                  ->method('sendRequest')
                  ->willReturnCallback(function() use ($response) {
                      $responseArray = json_decode($response->body, true);
                      $fundAccount = $this->getDbLastEntity('fund_account', 'test');
                      $responseArray['fund_account']['id'] = $fundAccount['id'];
                      $response->body = json_encode($responseArray);
                      return $response;
                  });
    }

    public function testCreateFaOfTypeBankAndSendRequestToFavService_Composite()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $this->mockFavServiceCreate('ba_fav_composite_created_state');

        $response = $this->startTest();
    }

    public function testCreateFavInNewService_WithContact_CreatedState_Composite(){

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $this->mockFavServiceCreate('ba_fav_composite_created_state');

        $response = $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('HDFC0000053', $bankAccount['ifsc']);

        $this->assertEquals('765432123456789', $bankAccount['account_number']);

        $contact = $this->getDbLastEntity('contact');

        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        $this->assertNotNull($contact);

        $this->assertNotNull($fundAccount);

        $this->assertNotNull($response['fund_account']['id']);
    }

    public function testCreateFavInNewService_WithoutContact_CreatedState_Composite(){

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $this->mockFavServiceCreate('ba_fav_composite_created_state');

        $response = $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('HDFC0000053', $bankAccount['ifsc']);

        $this->assertEquals('765432123456789', $bankAccount['account_number']);

        $contact = $this->getDbLastEntity('contact');

        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        $this->assertNull($contact);

        $this->assertNotNull($fundAccount);

        $this->assertNotNull($response['fund_account']['id']);
    }

    public function testCreateFavInNewService_WithContact_CreatedState_NonComposite(){

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] = $fundAccountResponse['id'];

        $this->mockFavServiceCreate('ba_fav_non_composite_created_state');

        $response = $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $contact = $this->getDbLastEntity('contact');

        $this->assertEquals('SBIN0007105', $bankAccount['ifsc']);

        $this->assertEquals('111000111', $bankAccount['account_number']);

        $contact = $this->getDbLastEntity('contact');

        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        $this->assertNotNull($contact);

        $this->assertNotNull($fundAccount);

        $this->assertNotNull($response['fund_account']['id']);
    }

    public function testCreateFaOfTypeBankAndSendRequestToFavServiceFailure()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $this->expectException('\RZP\Exception\ServerErrorException');

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR);

        $FavServiceCreateMock = $this->getMockBuilder(FavServiceCreate::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(FavServiceCreate::FAV_SERVICE_CREATE, $FavServiceCreateMock);

        $this->app->fav_service_create
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new \RZP\Exception\ServerErrorException(
                'server error',
                code: ErrorCode::SERVER_ERROR

            ));

        $this->startTest();
    }

    public function testCreateValidationWithExposeUTRNotSetInResponse()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        // remove features is not required as by default feature would be disabled
        //$this->fixtures->merchant->removeFeatures(['expose_fa_validation_utr']);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $response = $this->startTest();

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

        // Queue will be processed by now.
        $fav      = $this->getLastEntity('fund_account_validation', true);

        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['registered_name']);

        // utr should not be present in response['results'] array
        $this->assertArrayKeysExist($response['results'], ['account_status','registered_name']);

        return $response;
    }

    public function testCreateValidationWithComposite()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $response = $this->startTest();

        $isEventValidated = false;

        $expectedProperties = [
            'fav'   => [
                'merchant_id'     => '10000000000000',
                'account_status'  => 'active',
                'status'          => 'completed'
            ]
        ];

        $this->verifyFAVStatusEvent('fund_account_validation.status', $expectedProperties, $isEventValidated);

        $fav = $this->getDbLastEntity('fund_account_validation');

        $this->assertEquals('new_fav_composite'."_".$fav->getId(), $fav['receipt']);

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        $this->assertTrue($isEventValidated);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['utr']);
        $this->assertEquals('INR', $fav['currency']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('prepaid', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);
        return $response;
    }

    public function testPennilessVpaValidationCompositeSuccess()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $response = $this->startTest();

        $fav = $this->getDbLastEntity('fund_account_validation');

        self::assertEquals("new_fav_composite"."_".$fav->getId(), $fav['receipt']);

        $fav     = $this->getLastEntity('fund_account_validation', true);
        $txn     = $this->getLastEntity('transaction', true);
        $balance = $this->getLastEntity('balance', true);
        $fta     = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        // validate balance entry in database
        $this->assertEquals(9999997, $balance['balance']);


        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Penniless Customer', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
        $this->assertEquals('Penniless', $favUpdated[Entity::ERROR_DESCRIPTION]);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/fund_accounts/validations/' . 'fav_' . $favUpdated['id'] ,
            'content' => [
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);
    }

    public function testCreateValidationWithWrongFundAccountId()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->startTest();
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testCreateValidationWithFundAccountEntityFromAdmin()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $admin = $this->ba->getAdmin();

        $admin->setAllowAllMerchants();

        (new Admin\Repository)->saveOrFail($admin);

        $this->ba->adminAuth();

        $this->createValidationWithFundAccountEntityFromAdmin();
    }

    public function testCreateValidationForBankNotAllowed()
    {
        $this->markTestSkipped();

        $this->startTest();
    }

    public function testCreateValidationWithWrongFundAccountEntity()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->startTest();
    }

    public function testCreateValidationWithAmountInDecimalString()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->startTest();
    }

    public function testCreateValidationWithAmountInDecimalFloat()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->startTest();
    }

    public function testCreateValidationForCustomerFeeBearer()
    {
        // Fee Bearer doesn't have any effect on Fund Account Validation.
        // Transaction is always created for Fee Bearer: Platform.
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testGetValidations()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['items'][0]['results']['registered_name']);
    }

    public function testFundAccValidationOnPrepaidModelWithFeeCredits()
    {
        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->ba->privateAuth();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('prepaid', $txn['fee_model']);
        // When Fee credits are available, they should get used.
        $this->assertEquals(354, $txn['fee_credits']);
        $this->assertEquals('fee', $txn['credit_type']);
    }

    public function testFundAccValidationWhenFailedDuringRecon()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $receipt = 'failed_resp_beneficiary_details_invalid';

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] = $receipt ;

        $response = $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'FAILED', ['receipt' => $receipt]);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        // Reloading FAV to account for changes after call to processFavToTerminalState()
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('invalid', $fav['results']['account_status']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(1000000, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
        return $response;
    }

    public function testFundAccValidationOnPostpaidModelWithFeeCredits()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->ba->privateAuth();

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        // When Fee credits are available, they should get used even in postpaid Model.
        $this->assertEquals(354, $txn['fee_credits']);
        $this->assertEquals('fee', $txn['credit_type']);
    }

    public function testFundAccValidationOnPostpaidModelWithNoFeeCredits()
    {
        $this->markTestSkipped();

        // Already done as part of testCreateValidationWithFundAccountId
        // and testCreateValidationWithFundAccountEntity
    }

    public function testFundAccValidationOnPrepaidModelWithNoFeeCredits()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('prepaid', $txn['fee_model']);
        $this->assertEquals(354, $txn['debit']);
        $this->assertEquals(1000000 - 354, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalance()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->fixtures->merchant->editBalance('0');

        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testWebhookFiringFundAccountValidationCompleted()
    {
        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $eventTestDataKey = 'testFiringOfWebhookOnFAVCompletionWithStork';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'utr'    => '933815233814',
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.completed', $eventTestDataKey);

        $this->triggerFlowToUpdateFavWithNewState($favId, 'COMPLETED');

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('processed', $fta->getStatus());

        $this->assertEquals('completed', $fav->getStatus());

        return $favId;
    }

    public function testFavFtsWebhookForwardToNewService_FavNotFound()
    {
        $mock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $mock);

        $mock->shouldReceive('handleBankWebhook')
            ->withArgs([Mockery::any(), 'fts'])
            ->times(1);

        $this->triggerFlowToUpdateFavWithNewState('fav_00000000000001', 'INITIATED', beneName: 'TestName');
    }

    public function testFavFtsWebhookForwardToNewService_FavFoundCreatedInNewService()
    {
        $mock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $mock);

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        /** @var Entity $fav */
        $fav = $this->getDbLastEntity('fund_account_validation');

        $favId = $fav->getId();

        $fav->setReceipt('_validx_'.$favId);

        $fav->save();

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED], $fav->getMerchantId());

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $mock->shouldReceive('handleBankWebhook')
            ->withArgs([Mockery::any(), 'fts'])
            ->times(1);

        $this->triggerFlowToUpdateFavWithNewState($favId, 'INITIATED', beneName: 'TestName');
    }

    public function testFavNameNotSet_FtsWebhookWithBeneNameAndInInitiatedState()
    {
        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $eventTestDataKey = 'testFiringOfWebhookOnFAVCompletionWithStork';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'utr'    => '933815233814',
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.completed', $eventTestDataKey);

        $this->triggerFlowToUpdateFavWithNewState($favId, 'INITIATED', beneName: 'TestName');

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('initiated', $fta->getStatus());

        $this->assertEquals('created', $fav->getStatus());

        $this->assertNull($fav->getRegisteredName());

        return $favId;
    }

    public function testWebhookFiringFundAccountValidationFailed()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $payoutId = $fav->getId();

        $eventTestDataKey = 'testWebhookFiringFundAccountValidationFailed';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.failed', $eventTestDataKey);

        $isEventValidated = false;

        $expectedProperties = [
            'fav'   => [
                'merchant_id'     => '10000000000000',
                'account_status'  => null,
                'status'          => 'failed'
            ]
        ];

        $this->verifyFAVStatusEvent('fund_account_validation.status', $expectedProperties, $isEventValidated);

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'FAILED', [
            'bank_status_code' => 'ACCOUNT_INVALID',
            'extra_info'       => ['internal_error' => true],
        ]);

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('failed', $fta->getStatus());

        $this->assertEquals('failed', $payout->getStatus());

        $favCreated = $this->getDbLastEntity('fund_account_validation');

        $reversalCreated = $this->getDbLastEntity('reversal');

        $this->assertTrue($isEventValidated);

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'fav_initiated',
            'fav_failed',
        ];

        // Since there are multiple events within the flow,
        // following is a list of transactor Ids for which these events occured
        $transactorIdArray = [
            $favCreated->getPublicId(),
            $reversalCreated->getPublicId(),
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('3', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in fund account validation initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertEquals($favCreated->transaction->getId(), $ledgerSnsPayloadArray[0]['api_transaction_id']);
        $this->assertEquals('bacc_ABCde1234ABCde', $ledgerSnsPayloadArray[0]['identifiers']['banking_account_id']);

        // Passed in fund account validation failed payload
        $this->assertEquals('100000000', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('nodal', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);
        $this->assertEquals($reversalCreated->transaction->getId(), $ledgerSnsPayloadArray[1]['api_transaction_id']);
        $this->assertEquals('bacc_ABCde1234ABCde', $ledgerSnsPayloadArray[1]['identifiers']['banking_account_id']);
    }

    public function testIfFundAccountValidationAlreadyInFinalStateBeforeFTAUpdate()
    {
        $this->mockRazorxTreatment();

        $ledgerSnsPayloadArray = [];

        // During FAV creation, there has been push to SNS topic for creating this transaction in Ledger service.
        // Mocking ledger sns because call to ledger is currently async via SNS. Once it is in sync, this will be removed.
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->fund_account_validation->editEntity('fund_account_validation', $fav['id'], ['status' => 'completed']);

        $favId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($favId, 'INITIATED');

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('initiated', $fta->getStatus());

        $this->assertEquals('completed', $fav->getStatus());

        $fundAccountValidationsCreated = $this->getDbEntities('fund_account_validation');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($fundAccountValidationsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('3', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('fav_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }


    public function testFundAccValidationWhenFtaStillInitiatedDuringRecon()
    {
        $this->mockRazorxTreatment();

        $ledgerSnsPayloadArray = [];

        // During FAV creation, there has been push to SNS topic for creating this transaction in Ledger service.
        // Mocking ledger sns because call to ledger is currently async via SNS. Once it is in sync, this will be removed.
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($favId, 'INITIATED');

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('initiated', $fta->getStatus());

        $this->assertEquals('created', $fav->getStatus());

        $fundAccountValidationsCreated = $this->getDbEntities('fund_account_validation');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($fundAccountValidationsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('3', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('fav_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testFundAccValidationWhenFailedDuringReconWithNonInternalError()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $receipt = 'failed_resp_beneficiary_details_invalid';

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] = $receipt ;

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'FAILED', ['receipt' => $receipt]);

        // Reloading FAV to account for changes after call to processFavToTerminalState()
        $fav = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('invalid', $fav['results']['account_status']);

        // Retry At will be calculated and set because
        // beneficiary not accepted is not an internal error
        // and there is not need to retry.
        $this->assertNull($fav['retry_at']);
    }

    public function testFundAccValidationWhenFailedDuringReconWithInternalError()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $receipt = 'failed_response_insufficient_funds';

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] = $receipt ;

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id'], 'FAILED', ['receipt' => $receipt]);

        // Reloading FAV to account for changes after call to processFavToTerminalState()
        $fav = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('failed', $fav['status']);
        $this->assertEquals(null, $fav['results']['account_status']);

        // Retry At will be calculated and set because
        // beneficiary not accepted is not an internal error
        // and there is not need to retry.
        $this->assertNull($fav['retry_at']);
    }

    public function testFundAccValidationWithAccountNumberAndBankAccount($skipTxnCheck = false)
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        // get database entities
        $balance = $this->getLastEntity('balance', true);
        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $txn = $this->getLastEntity('transaction', true);

        // validate balance entry in database
        if ($skipTxnCheck === false) {
            $this->assertEquals(9999997, $balance['balance']);
        }

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav['balance_id']);
        $this->assertEquals('10000000000000', $fav['merchant_id']);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav['entity']);

        // validate transaction table last entry
        if ($skipTxnCheck === false) {
            $this->assertEquals(Constants\Entity::FUND_ACCOUNT_VALIDATION, $txn['type']);
            $this->assertEquals($fav['id'], $txn['entity_id']);
            $this->assertEquals(100, $txn['amount']);
            $this->assertEquals(3, $txn['fee']);
            $this->assertEquals($balance['id'], $txn['balance_id']);
            $this->assertEquals(9999997, $txn['balance']);

            $this->assertEquals($txn['entity_id'], $fav['id']);
            $this->assertEquals($txn['id'], 'txn_'.$fav['transaction_id']);
        }

        // validate fund transfer attempt table last entry
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
    }

    public function testFundAccValidationWithAccountNumberAndBankAccountOnLiveMode($skipTxnCheck = false)
    {
        $this->setUpMerchantForBusinessBankingLive(false, 10000000);

        $this->createFAVBankingPricingPlan('live');

        $this->fixtures->on('live')->merchant->editEntity('merchant', '10000000000000', [
            'fee_model' => 'prepaid',
            'pricing_plan_id' => '1hDYlICobzOCYt'
        ]);

        $fundAccountResponse = $this->createFundAccountBankAccount('rzp_live_TheLiveAuthKey', 'live');

        $this->testData[__FUNCTION__] = $this->testData['testFundAccValidationWithAccountNumberAndBankAccount'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        // get database entities
        $balance = $this->getLastEntity('balance', true, 'live');
        $fav = $this->getLastEntity('fund_account_validation', true, 'live');
        $fta = $this->getLastEntity('fund_transfer_attempt', true, 'live');
        $txn = $this->getLastEntity('transaction', true, 'live');

        // validate balance entry in database
        if ($skipTxnCheck === false) {
            $this->assertEquals(9999997, $balance['balance']);
        }

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav['balance_id']);
        $this->assertEquals('10000000000000', $fav['merchant_id']);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav['entity']);

        // validate transaction table last entry
        if ($skipTxnCheck === false) {
            $this->assertEquals(Constants\Entity::FUND_ACCOUNT_VALIDATION, $txn['type']);
            $this->assertEquals($fav['id'], $txn['entity_id']);
            $this->assertEquals(100, $txn['amount']);
            $this->assertEquals(3, $txn['fee']);
            $this->assertEquals($balance['id'], $txn['balance_id']);
            $this->assertEquals(9999997, $txn['balance']);
        }

        // validate fund transfer attempt table last entry
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
    }

    public function testFundAccValidationWithAccountNumberThatIsAlreadyProcessed()
    {
        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(0, $fav['attempts']);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $ftaEntity = $this->getDbEntityById('fund_transfer_attempt',  preg_replace('/^fta_/', '', $fta['id']));
        $this->assertEquals('penny_testing', $fta['purpose']);
        // There won't be FTA for second FAV
        $this->assertNotEquals($fav['id'], $fta['source']);
        $this->assertNotNull($ftaEntity->getUtr());
        $this->assertEquals('Cache', $fav[Entity::ERROR_DESCRIPTION]);
    }

    public function testFundAccValidationWithAccountNumberThatIsAlreadyProcessedButUtrNeeded()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

        $fav = $this->getDbLastEntity('fund_account_validation');

        $this->assertEquals(1, $fav['attempts']);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['account_status']);

        $fta = $this->getDbLastEntity('fund_transfer_attempt');
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source_id']);
        $this->assertNotNull($fav['utr']);
    }

    //when ifsc is in the list of oldnewifscmapping and also there is already a completed fav within 30 days present,
    //it should not pick the fav status from cache..instead it should call fts and do a fresh validation
    public function testFundAccValidationWithAccountNumberThatIsAlreadyProcessedWithOldIfsc()
    {
        $this->markTestSkipped('The IFSC ORBC0101753 is invalid, skipping this test case for now ');

        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'ORBC0101753',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];


        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(1, $fav['attempts']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($fav['id'], $fta['source']);
    }

    //testcase to check if for fav same account number and different ifscs
    //it should not pick the fav status from cache..instead it should call fts and do a fresh validation
    public function testFundAccValidationWithSameAccountNumberAndDifferentIfscBank()
    {
        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'HDFC0000133',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(1, $fav['attempts']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($fav['id'], $fta['source']);
    }

    //testcase to check if for fav details but bene name is not a valid one according to penniless redis key
    //it should not pick the fav status from cache..instead it should call fts and do a fresh validation
    public function testFundAccValidationWithSameDetailsButBeneNameNotAllowed()
    {
        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_RESPONSE_BENE_NAME_BLACKLIST => ['XX','Razorpay']]);

        $this->fixtures->merchant->editEntity('fund_account_validation', $fav['id'], ['registered_name' => 'xxx yyy']);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(1, $fav['attempts']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($fav['id'], $fta['source']);
    }

    //checks if first 4 char of ifsc are same, then don't go to bank and fav should be picked from cache
    public function testFundAccValidationWithSameAccountNumberIfscBankButDifferentIfscCode()
    {
        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0000813',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(0, $fav['attempts']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNotEquals($fav['id'], $fta['source']);
    }


    public function testFundAccValidationWithAccountNumberAndVpa()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        // get database entities
        $balance = $this->getLastEntity('balance', true);
        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $txn = $this->getLastEntity('transaction', true);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no transaction should be created for 0 fee
        $this->assertNotEquals($fav['id'], $txn['entity_id']);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Razorpay Customer', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
    }

    public function testFundAccValidationWithAccountNumberAndInvalidVpa()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa(null, 'invalidvpa@razorpay');

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        // get database entities
        $balance = $this->getLastEntity('balance', true);
        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $txn = $this->getLastEntity('transaction', true);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('invalid', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
    }

    public function testFundAccValidationWithAccountNumberAndInvalidVpaHandle()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa(null, 'invalidhandle@razor');

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        // get database entities
        $balance = $this->getLastEntity('balance', true);
        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $txn = $this->getLastEntity('transaction', true);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals('invalid', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
    }

    public function testVpaFundAccValidationForFailedStatus()
    {
        Queue::fake();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa(null, 'vpagatewayerror@razorpay');


        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        // get database entities
        $balance = $this->getLastEntity('balance', true);
        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $txn = $this->getLastEntity('transaction', true);

        // validate balance entry in database
        $this->assertEquals(10000000, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav[Entity::BALANCE_ID]);
        $this->assertEquals('10000000000000', $fav[Entity::MERCHANT_ID]);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav[Entity::ENTITY]);

        // no fta
        $this->assertNotEquals($fav['id'], $fta['source']);

        Queue::assertPushed(FaVpaValidation::class);

        // Test worker
        $faVpaValidation = new FaVpaValidation('test', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']));

        $this->assertEquals(null, $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals(null, $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('failed', $favUpdated[Entity::STATUS]);
    }

    public function testFixTransactionSettledAt()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(false, $txn['settled']);

        $this->fixtures->merchant->editEntity('transaction', $txn['id'], ['settled' => true]);

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(false, $txn['settled']);
    }

    public function testFundAccValidationBankingFailedAccountTypeDirect()
    {
        $this->setUpMerchantForBusinessBanking(false,
            10000000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->createFAVBankingPricingPlan();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();
    }

    public function testFundAccValidationBankingFailedAmountVpa()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();
    }

    public function testFundAccValidationBankingFailedCurrencyVpa()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();
    }

//    public function testFundAccValidationBankingFailedMissingFundAccountId()
//    {
//        $this->setUpMerchantForBusinessBanking(false, 10000000);
//
//        $this->startTest();
//    }

    public function testFundAccValidationFailedFundAccountCardType()
    {
        Queue::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->mockCardVault(null, true);

        $fundAccountResponse = $this->createFundAccountCard();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();
    }

    public function testGetFavByIdAndMerchantId()
    {
        $attribute = [
            Entity::MERCHANT_ID     => '100000Razorpay',
            Entity::REGISTERED_NAME => "random name",
            Entity::ACCOUNT_STATUS  => "active",
            Entity::NOTES           => [
                Entity::MERCHANT_ID => '10000000000000',
            ],
        ];

        $this->fixtures->on('live')->create('fund_account_validation', $attribute);

        $fav = $this->getLastEntity('fund_account_validation', true, 'live');

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testGetFavByIdInAPI()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->testCreateValidationWithFundAccountId();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCompositeFavByIdInAPI()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->testCreateValidationWithComposite();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCompositeFavByIdInAPI_withRegisteredNameAsNull()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->testCreateValidationWithComposite();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->fixtures->edit(
            'fund_account_validation',
            $fav['id'],
            [
                'registered_name'    => null,
            ]);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function createGetApiResponseForFavServiceMock($favType = 'composite'): array
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $content = [
            'id'=> 'fav_00000000000001',
            'status'=> 'completed',
            'reference_id'=> '112233',
            'type' => $favType,
            'notes'=> [
                'random_key_1'=> 'Make it so.',
                'random_key_2'=> 'Tea. Earl Grey. Hot.'
            ],
            'created_at' => 1567064019,
            'error_code' => null,
            'utr' => null,
            'registered_name' => "Test User",
            'amount' => 200,
            'merchant_id' => '10000000000000',
            'account_status' => 'active',
            'fund_account' => [
                'id' => $fundAccountResponse['id'],
            ],
            'currency' => 'INR',
            'validation_method' => 'penniless',
            'name_match_score' => 95.5
        ];

        return $content;
    }

    public function testGetFavByIdFromMicroservice_Composite()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $attribute = [
            Entity::MERCHANT_ID     => '10000000000000',
            Entity::REGISTERED_NAME => "Razorpay Test",
            Entity::ACCOUNT_STATUS  => "active",
            Entity::NOTES           => [
            ],
        ];

        $this->fixtures->on('live')->create('fund_account_validation', $attribute);

        $fav = $this->getLastEntity('fund_account_validation', true, 'live');

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], 'fav_00000000000001');

        $this->ba->privateAuth();

        $mock->shouldReceive('fetchById')
            ->withArgs(['00000000000001'])
            ->andReturn($this->createGetApiResponseForFavServiceMock())
            ->times(1);

        $this->startTest();
    }

    public function testGetFavByIdFromMicroservice_NonComposite()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], 'fav_00000000000001');

        $this->ba->privateAuth();

        $mock->shouldReceive('fetchById')
            ->withArgs(['00000000000001'])
            ->andReturn($this->createGetApiResponseForFavServiceMock('non_composite'))
            ->times(1);

        $this->startTest();
    }

    public function testGetFavByIdFromMicroservice_NotFoundError()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING => 'enable']);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], 'fav_00000000000001');

        $this->ba->privateAuth();

        // Mock service to return FAV000003 error
        $mock->shouldReceive('fetchById')
            ->withArgs(['00000000000001'])
            ->andThrow(new BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_FOUND,
                null,
                [],
                'The requested entity is not found',
            ))
            ->times(1);

        $this->startTest();
    }
    public function testFundAccValidationWithFailedStatus()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        // Reloading FAV to account for changes after call to processFavToTerminalState()
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('failed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals(null, $fav['results']['account_status']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('failed', $fta['status']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals($reversal['id'], $txn['entity_id']);
        $this->assertEquals('reversal', $txn['type']);
        // these are na for prepaid.
        $this->assertEquals('na', $txn['fee_bearer']);
        $this->assertEquals('na', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(-354, $txn['fee']);
        $this->assertEquals(-354, $txn['mdr']);
        $this->assertEquals(-54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(354, $txn['credit']);
        //Not sure if it should be 100 or 0
        $this->assertEquals(0, $txn['amount']);
        $this->assertEquals(1000000, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testFundAccValidationWithFailedStatusForBusinessBanking()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id']);

        $balance     = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        // Reloading FAV to account for changes after call to processFavToTerminalState()
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('failed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals(null, $fav['results']['account_status']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        $this->assertEquals($balance['id'], $fav['balance_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('failed', $fta['status']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

    }

    public function testFundAccValidationWithFailedStatusOnPostpaid()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $response = $this->startTest();

        $this->triggerFlowToUpdateFavWithNewState($response['id']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('failed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals(null, $fav['results']['account_status']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('failed', $fta['status']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals($reversal['id'], $txn['entity_id']);
        $this->assertEquals('reversal', $txn['type']);
        $this->assertEquals('na', $txn['fee_bearer']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(-354, $txn['fee']);
        $this->assertEquals(-354, $txn['mdr']);
        $this->assertEquals(-54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(0, $txn['credit']);
        //Not sure if it should be 100 or 0
        $this->assertEquals(0, $txn['amount']);
        $this->assertEquals(1000000, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testFundAccValidationMarkAsFailed()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->assertEquals('created', $fav['status']);
        $this->assertEquals(null, $fav['results']['account_status']);

        $request = [
            'method'  => 'PATCH',
            'url'     => '/fund_accounts/validations/bulk/fail',
            'content' => [
                Validation::FUND_ACCOUNT_VALIDATION_IDS => [
                    $fav['id']
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        // Queue will be processed by now.
        $this->assertEquals('failed', $fav['status']);
    }

    public function testFinalStateReachedFundAccValidationNotMarkAsFailed()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->fixtures->fund_account_validation->editEntity('fund_account_validation', $fav['id'], ['status' => 'completed']);

        $this->assertEquals(null, $fav['results']['account_status']);

        $request = [
            'method'  => 'PATCH',
            'url'     => '/fund_accounts/validations/bulk/fail',
            'content' => [
                Validation::FUND_ACCOUNT_VALIDATION_IDS => [
                    $fav['id']
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        // Queue will be processed by now.
        $this->assertNotEquals('failed', $fav['status']);
    }

    public function testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalanceNewApiError()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);;

        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->merchant->on('test')->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->fixtures->merchant->on('test')->editBalance('0');

        $this->startTest();
    }

    public function testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalanceNewApiErrorOnLiveMode()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('live')->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->fixtures->on('live')->merchant->editBalance('0');

        $this->fixtures->on('live')->merchant->edit('10000000000000',
            [
                'activated' => 1,
                'pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID
            ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testMutexRetryForFundAccountValidationStatusUpdate()
    {
        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $favId = $fav->getId();

        $app = App::getFacadeRoot();

        $mutex = $app['api.mutex'];

        //testing mutex retry when unable to take the lock since its already reserved by another event
        $mutex->acquireAndRelease(
            self::VALIDATION_UPDATE_MUTEX . $favId,
            function () use ($favId)
            {
                $this->triggerFlowToUpdateFavWithNewState($favId, 'COMPLETED');
            },
            0.02);
    }


    public function testFundAccValidationForSpecialCharacterRemovalForNaration()
    {
        $this->markTestSkipped('the IFSC being used is invalid, skipping the test till its replaced with valid value');

        $this->fixtures->merchant->editEntity('merchant', '10000000000000',
            ['name' => 'L&!T @L and T', 'billing_label' => '']);

        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'ORBC0101753',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(1, $fav['attempts']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($fta['narration'],'LT L and T Acc Validation');
        $this->assertEquals($fav['id'], $fta['source']);
    }

    public function testExtraFieldsInWebhookShouldNotDisruptFavBackwardFlow()
    {
        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $eventTestDataKey = 'testFiringOfWebhookOnFAVCompletionWithStork';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.completed', $eventTestDataKey);

        // Add random key value pair to FTS webhook body and trigger webhook callback here
        $this->triggerFlowToUpdateFavWithNewState($favId, 'COMPLETED', ['RandomKey' => 'RandomValue']);

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('processed', $fta->getStatus());

        $this->assertEquals('completed', $fav->getStatus());
        $this->assertEquals('Razorpay Test', $fav->getRegisteredName());

        return $favId;
    }

    public function testFundAccountValidationReversed()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(3, $ledgerSnsPayloadArray);

        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccount();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $payoutId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'COMPLETED');

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'REVERSED');

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('reversed', $fta->getStatus());

        $this->assertEquals('completed', $payout->getStatus());

        $fundAccountValidationCreated = $this->getDbLastEntity('fund_account_validation');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'fav_initiated',
            'fav_processed',
            'fav_reversed'
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($fundAccountValidationCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('3', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);
        $ledgerSnsPayloadArray[2]['identifiers'] = json_decode($ledgerSnsPayloadArray[2]['identifiers'], true);

        // Not passed in fund account validation initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Passed in fund account validation processed payload
        $this->assertEquals('100000000', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('nodal', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);

        // Passed in fund account validation reversed payload
        $this->assertEquals('100000000', $ledgerSnsPayloadArray[2]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('nodal', $ledgerSnsPayloadArray[2]['identifiers']['fts_account_type']);
    }

    public function testFundAccountValidationReversedOnLiveMode($skipTxnCheck = false)
    {
        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccountOnLiveMode($skipTxnCheck);

        $fav = $this->getDbLastEntity('fund_account_validation', 'live');

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $payoutId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'COMPLETED', [], 'live');

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'REVERSED', [], 'live');

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId, 'live');

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId(), 'live');

        $this->assertEquals('reversed', $fta->getStatus());

        $this->assertEquals('completed', $payout->getStatus());
    }

    public function testFundAccountValidationReversedOnLiveModeWithLedgerFeature()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(3, $ledgerSnsPayloadArray);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccountOnLiveMode();

        $fav = $this->getDbLastEntity('fund_account_validation', 'live');

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $payoutId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'COMPLETED', [], 'live');

        $this->triggerFlowToUpdateFavWithNewState($payoutId, 'REVERSED', [], 'live');

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId, 'live');

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId(), 'live');

        $this->assertEquals('reversed', $fta->getStatus());

        $this->assertEquals('completed', $payout->getStatus());

        $fundAccountValidationCreated = $this->getDbLastEntity('fund_account_validation', 'live');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'fav_initiated',
            'fav_processed',
            'fav_reversed'
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($fundAccountValidationCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('3', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);
        $ledgerSnsPayloadArray[2]['identifiers'] = json_decode($ledgerSnsPayloadArray[2]['identifiers'], true);

        // Not passed in fund account validation initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Passed in fund account validation processed payload
        $this->assertEquals('1111111', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('current', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);

        // Passed in fund account validation reversed payload
        $this->assertEquals('1111111', $ledgerSnsPayloadArray[2]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('current', $ledgerSnsPayloadArray[2]['identifiers']['fts_account_type']);
    }

    /**
     * In this test, we are not faking the queue class.
     * Tests run the dispatch calls in sync mode, thus a txn is actually created in API DB.
     */
    public function testFundAccountValidationCreationInLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testFundAccValidationWithAccountNumberAndBankAccount(true);
    }

    /**
     * In this test, we are not faking the queue class.
     * Tests run the dispatch calls in sync mode, thus a txn is actually created in API DB.
     */
    public function testFundAccountValidationCreationInLedgerReverseShadowOnLiveMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testFundAccValidationWithAccountNumberAndBankAccountOnLiveMode(true);
    }

    public function testDispatchOfTransactionsJobInLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();
    }

    public function testDispatchOfTransactionsJobInLedgerReverseShadowInLiveMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->setUpMerchantForBusinessBankingLive(false, 10000000);

        $this->createFAVBankingPricingPlan('live');

        $this->fixtures->on('live')->merchant->editEntity('merchant', '10000000000000', [
            'fee_model' => 'prepaid',
            'pricing_plan_id' => '1hDYlICobzOCYt'
        ]);

        $fundAccountResponse = $this->createFundAccountBankAccount('rzp_live_TheLiveAuthKey', 'live');

        $this->testData[__FUNCTION__] = $this->testData['testDispatchOfTransactionsJobInLedgerReverseShadow'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFundAccountValidationReversedInLedgerReverseShadowInLiveMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testFundAccountValidationReversedOnLiveMode(true);
    }

    public function testDispatchOfTransactionsJobForFundAccountValidationReversedInLedgerReverseShadowInLiveMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithAccountNumberAndBankAccountOnLiveMode(true);

        Queue::fake();

        $fav = $this->getDbLastEntity('fund_account_validation', 'live');

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $favId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($favId, 'COMPLETED', [], 'live');

        $this->triggerFlowToUpdateFavWithNewState($favId, 'REVERSED', [], 'live');

        $fav = $this->getDbEntityById('fund_account_validation', $favId, 'live');

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId(), 'live');

        $this->assertEquals('reversed', $fta->getStatus());

        $this->assertEquals('completed', $fav->getStatus());

        Queue::assertPushed(Transactions::class, 0);
    }

    public function testFundAccValidationWithFailedStatusForBusinessBankingInLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->mockRazorxTreatment();

        $this->testFundAccValidationWithFailedStatusForBusinessBanking();
    }

    public function testDispatchOfTransactionsJobForFundAccValidationWithFailedStatusForBusinessBankingInLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->mockRazorxTreatment();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__] = $this->testData['testFundAccValidationWithFailedStatusForBusinessBanking'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        //$this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $this->triggerFlowToUpdateFavWithNewState($fav['id']);
    }

    public function testCreateFundAccountValidationWithBalanceInLedgerReverseShadow()
    {
        $this->markTestSkipped();
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal');

        $mockLedger->shouldReceive('fetchMerchantAccounts')
            ->andReturn([
                "merchant_id"      => "10000000000000",
                "merchant_balance" => [
                    "balance"      => "10000.000000",
                    "min_balance"  => "0.000000"
                ],
                "reward_balance"  => [
                    "balance"     => "20.000000",
                    "min_balance" => "-20.000000"
                ],
            ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->mockRazorxTreatment();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__] = $this->testData['testFundAccValidationWithFailedStatusForBusinessBanking'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();
    }

    public function testCreateFailedFundAccountValidationWithInsufficientBalanceInLedgerReverseShadow()
    {
        $this->markTestSkipped();
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);
        $mockLedger->shouldReceive('fetchMerchantAccounts')
            ->andReturn([
                "merchant_id"      => "10000000000000",
                "merchant_balance" => [
                    "balance"      => "0.000000",
                    "min_balance"  => "0.000000"
                ],
                "reward_balance"  => [
                    "balance"     => "20.000000",
                    "min_balance" => "-20.000000"
                ],
            ]);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);
        $this->mockRazorxTreatment();
        $this->setUpMerchantForBusinessBanking(false, 0);
        $this->createFAVBankingPricingPlan();
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);
        $this->enableRazorXTreatmentForRazorX();
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);
        $fundAccountResponse = $this->createFundAccountBankAccount();
        $this->testData[__FUNCTION__]['request'] = $this->testData['testFundAccValidationWithFailedStatusForBusinessBanking']['request'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->startTest();
    }

    public function testFundAccountValidationFailedInZeroPricingInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->mockRazorxTreatment();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $pricingPlan = [
            'plan_name'           => 'FAV Plan',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'plan_id'             => '1hDYlICobzOCYt',
            'product'             => 'banking',
            'feature'             => 'fund_account_validation',
            'payment_method'      => 'bank_account',
            'account_type'        => 'shared'
        ];

        $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__] = $this->testData['testFundAccValidationWithFailedStatusForBusinessBanking'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        //$this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);

        Queue::fake();

        $this->triggerFlowToUpdateFavWithNewState($fav['id']);

        // We don't create a transaction for 0 pricing reversals in FAV
        Queue::assertPushed(Transactions::class, 0);
    }

    public function testFundAccountValidationFailedOnLiveModeInLedgerShadowModeWithZeroPricing()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        $this->mockRazorxTreatment();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        //Zero pricing
        $pricingPlan = [
            'plan_name'           => 'FAV Plan',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'plan_id'             => '1hDYlICobzOCYt',
            'product'             => 'banking',
            'feature'             => 'fund_account_validation',
            'payment_method'      => 'bank_account',
            'account_type'        => 'shared'
        ];

        $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__] = $this->testData['testFundAccValidationWithFailedStatusForBusinessBanking'];
        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        //$this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $payoutId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->triggerFlowToUpdateFavWithNewState($payoutId);

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('failed', $fta->getStatus());

        $this->assertEquals('failed', $payout->getStatus());

        $fundAccountValidationCreated = $this->getDbLastEntity('fund_account_validation');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'fav_initiated',
            'fav_failed',
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($fundAccountValidationCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in fund account validation initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Passed in fund account validation failed payload
        $this->assertEquals('100000000', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('nodal', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);
    }

    public function testFundAccountValidationBlockedOnShadowSharedBalance()
    {
        $this->setUpMerchantForBusinessBankingLive(false, 10000000);

        $this->fixtures->merchant->addFeatures([Feature\Constants::BLOCK_FAV]);

        $fundAccountResponse = $this->createFundAccountBankAccount('rzp_live_TheLiveAuthKey', 'live');

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['content']['fund_account']['id'] = $fundAccountResponse['id'];

        $this->startTest();

        $this->fixtures->merchant->addFeatures([Feature\Constants::SUB_VA_FOR_DIRECT_BANKING]);

        $this->startTest();
    }

    public function testFundAccValidationForVPAWithNonActivatedRxMerchant()
    {
        Queue::fake();

        config()->set('gateway.validate_vpa_terminal_ids.live', '100UPIICICITml');

        $this->fixtures->on('live')->create('terminal:shared_upi_icici_terminal', ['used' => true]);

        $this->setUpMerchantForBusinessBankingLive(false, 10000000);

        $this->fixtures->on('live')->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountVpa('rzp_live_TheLiveAuthKey', 'withname@razorpay', 'live');

        $this->testData[__FUNCTION__] = $this->testData['testFundAccValidationWithAccountNumberAndVpa'];

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] = $fundAccountResponse['id'];

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true, 'live');

        Queue::assertPushed(FaVpaValidation::class);

        $this->fixtures->on('live')->edit(
            'merchant',
            '10000000000000',
            [
                'activated' => false,
            ]);

        $this->fixtures->on('live')->create(
            'merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'products_enabled',
                'type'        => 'X',
                'value'       => 'true'
            ]);

        $faVpaValidation = new FaVpaValidation('live', preg_replace('/^fav_/', '', $fav['id']));
        $faVpaValidation->handle();

        $favUpdated = $this->getDbEntityById('fund_account_validation', preg_replace('/^fav_/', '', $fav['id']), 'live');

        $this->assertEquals('active', $favUpdated[Entity::ACCOUNT_STATUS]);
        $this->assertEquals('Rohit', $favUpdated[Entity::REGISTERED_NAME]);
        $this->assertEquals('completed', $favUpdated[Entity::STATUS]);
    }

    public function testCreateValidationForPGMerchantWithNoXLiteAccountAfterCutoff()
    {
        $this->createFundAccountBankAccount();

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $this->startTest();
    }

//    public function testCreateValidationForPGMerchantWithXLiteAccountAfterCutoff()
//    {
//        $this->setUpMerchantForBusinessBanking(false, 10000000);
//
//        $this->createFAVBankingPricingPlan();
//
//        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);
//
//        $this->createFundAccountBankAccount();
//
//        $this->enableRazorXTreatmentForRazorX();
//
//        $this->startTest();
//
//        $fav = $this->getDbLastEntity('fund_account_validation');
//
//        $balance = $this->getDbEntityById('balance', $fav['balance_id']);
//
//        $this->assertEquals('created', $fav['status']);
//        $this->assertEquals('shared', $balance['account_type']);
//        $this->assertEquals('banking', $balance['type']);
//    }

    public function testCreateValidationForPGMerchantBeforeCutoff()
    {
        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createFundAccountBankAccount();

        $this->startTest();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        $this->assertEquals('created', $fav['status']);
        $this->assertEquals('banking', $balance['type']);
    }

    public function testFavWithInsufficientBalance()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')->andThrow(
            new BadRequestException(
            ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
            null,
            [],
            "The fees calculated for fund account validation is greater than available fee credits or balance."
        ));

        $this->mockRazorxTreatment();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->setMockSplitzTreatmnt([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'enable']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $this->assertEquals(ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE, $fav['error_code']);

        $this->assertEquals('failed_due_to_low_balance', $fav['error_description']);
    }

    public function testSendWebhookToMerchantFromFavService_Composite_CompletedState()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  substr($fundAccountResponse['id'], 3);

        $this->ba->payoutInternalAppAuth();

        $expectedPayload = [
            'entity' => 'event',
            'account_id' => 'acc_10000000000000',
            'event' => 'fund_account.validation.completed',
            'contains' => ['fund_account.validation'],
            'payload' => [
                'fund_account.validation' => [
                    'entity' => [
                        'id' => 'fav_00000000000001',
                        'entity' => 'fund_account.validation',
                        'fund_account' => [
                            'id' => $fundAccountResponse['id'],
                            'entity' => 'fund_account',
                            'contact_id' => 'cont_1000000contact',
                            'contact' => [
                                'id' => 'cont_1000000contact',
                                'entity' => 'contact',
                                'contact' => '9123456789',
                                'batch_id' => null,
                                'active' => true,
                                'notes' => [],
                                'gstin' => null
                            ],
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'ifsc' => 'SBIN0007105',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Amit M',
                                'notes' => [],
                                'account_number' => '111000111'
                            ],
                            'batch_id' => null,
                            'active' => true,
                        ],
                        'status' => 'completed',
                        'notes' => [
                            'random_key_1' => 'Make it so.',
                            'random_key_2' => 'Tea. Earl Grey. Hot.'
                        ],
                        'created_at' => 1234567890,
                        'validation_results' => [
                            'account_status' => 'active',
                            'registered_name' => 'Test User',
                            'name_match_score' => '80.23',
                            'details' => "The beneficiary account is valid"
                        ],
                        'status_details' => [
                            'description' => 'validation request is completed',
                            'source' => 'beneficiary_bank',
                            'reason' => 'validation_completed'
                        ],
                        'reference_id' => 'ref_123456'
                    ]
                ]
            ],
            'created_at' => 0
        ];

        $storkMock = Mockery::mock(Stork::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('request')
            ->once()
            ->with(Mockery::any(), Mockery::on(function($payload) use ($expectedPayload) {
                $payloadData = json_decode($payload['event']['payload'], true);
                $this->assertArraySelectiveEquals($expectedPayload, $payloadData);
                return true;
            }), Mockery::any())
            ->andReturn(new \WpOrg\Requests\Response);

        $this->startTest();
    }

    public function testSendWebhookToMerchantFromFavService_Composite_FailedState()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  substr($fundAccountResponse['id'], 3);

        $this->ba->payoutInternalAppAuth();

        $expectedPayload = [
            'entity' => 'event',
            'account_id' => 'acc_10000000000000',
            'event' => 'fund_account.validation.failed',
            'contains' => ['fund_account.validation'],
            'payload' => [
                'fund_account.validation' => [
                    'entity' => [
                        'id' => 'fav_00000000000001',
                        'entity' => 'fund_account.validation',
                        'fund_account' => [
                            'id' => $fundAccountResponse['id'],
                            'entity' => 'fund_account',
                            'contact_id' => 'cont_1000000contact',
                            'contact' => [
                                'id' => 'cont_1000000contact',
                                'entity' => 'contact',
                                'contact' => '9123456789',
                                'batch_id' => null,
                                'active' => true,
                                'notes' => [],
                                'gstin' => null
                            ],
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'ifsc' => 'SBIN0007105',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Amit M',
                                'notes' => [],
                                'account_number' => '111000111'
                            ],
                            'batch_id' => null,
                            'active' => true,
                        ],
                        'status' => 'failed',
                        'notes' => [
                            'random_key_1' => 'Make it so.',
                            'random_key_2' => 'Tea. Earl Grey. Hot.'
                        ],
                        'created_at' => 1234567890,
                        'validation_results' => [
                            'account_status' => null,
                            'registered_name' => null,
                            'name_match_score' => null,
                            'details' => null
                        ],
                        'status_details' => [
                            'description' => 'Account Validation failed due to insufficient funds in your account.',
                            'source' => 'business',
                            'reason' => 'insufficient_funds'
                        ],
                        'reference_id' => 'ref_123456'
                    ]
                ]
            ],
            'created_at' => 0
        ];

        $storkMock = Mockery::mock(Stork::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('request')
            ->once()
            ->with(Mockery::any(), Mockery::on(function($payload) use ($expectedPayload) {
                $payloadData = json_decode($payload['event']['payload'], true);
                $this->assertArraySelectiveEquals($expectedPayload, $payloadData);
                return true;
            }), Mockery::any())
            ->andReturn(new \WpOrg\Requests\Response);

        $this->startTest();
    }

    public function testSendWebhookToMerchantFromFavService_NonComposite_CompletedState()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  substr($fundAccountResponse['id'], 3);

        $this->ba->payoutInternalAppAuth();

        $expectedPayload = [
            'entity' => 'event',
            'account_id' => 'acc_10000000000000',
            'event' => 'fund_account.validation.completed',
            'contains' => ['fund_account.validation'],
            'payload' => [
                'fund_account.validation' => [
                    'entity' => [
                        'id' => 'fav_00000000000001',
                        'entity' => 'fund_account.validation',
                        'fund_account' => [
                            'id' => $fundAccountResponse['id'],
                            'entity' => 'fund_account',
                            'contact_id' => 'cont_1000000contact',
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'ifsc' => 'SBIN0007105',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Amit M',
                                'notes' => [],
                                'account_number' => '111000111'
                            ],
                            'batch_id' => null,
                            'active' => true,
                            'details' => [
                                'ifsc' => 'SBIN0007105',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Amit M',
                                'notes' => [],
                                'account_number' => '111000111'
                            ],
                        ],
                        'status' => 'completed',
                        'amount' => 100,
                        'currency' => 'INR',
                        'notes' => [
                            'random_key_1' => 'Make it so.',
                            'random_key_2' => 'Tea. Earl Grey. Hot.'
                        ],
                        'created_at' => 1234567890,
                        'results' => [
                            'account_status' => 'valid',
                            'registered_name' => 'Test User',
                        ],
                        'utr' => "1245",
                    ]
                ]
            ],
            'created_at' => 0
        ];

        $storkMock = Mockery::mock(Stork::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('request')
            ->once()
            ->with(Mockery::any(), Mockery::on(function($payload) use ($expectedPayload) {
                $payloadData = json_decode($payload['event']['payload'], true);
                $this->assertArraySelectiveEquals($expectedPayload, $payloadData);
                return true;
            }), Mockery::any())
            ->andReturn(new \WpOrg\Requests\Response);

        $this->startTest();
    }

    public function testSendWebhookToMerchantFromFavService_NonComposite_FailedState()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  substr($fundAccountResponse['id'], 3);

        $this->ba->payoutInternalAppAuth();

        $expectedPayload = [
            'entity' => 'event',
            'account_id' => 'acc_10000000000000',
            'event' => 'fund_account.validation.failed',
            'contains' => ['fund_account.validation'],
            'payload' => [
                'fund_account.validation' => [
                    'entity' => [
                        'id' => 'fav_00000000000001',
                        'entity' => 'fund_account.validation',
                        'fund_account' => [
                            'id' => $fundAccountResponse['id'],
                            'entity' => 'fund_account',
                            'contact_id' => 'cont_1000000contact',
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'ifsc' => 'SBIN0007105',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Amit M',
                                'notes' => [],
                                'account_number' => '111000111'
                            ],
                            'batch_id' => null,
                            'active' => true,
                            'details' => [
                                'ifsc' => 'SBIN0007105',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Amit M',
                                'notes' => [],
                                'account_number' => '111000111'
                            ],
                        ],
                        'status' => 'failed',
                        'amount' => 100,
                        'currency' => 'INR',
                        'notes' => [
                            'random_key_1' => 'Make it so.',
                            'random_key_2' => 'Tea. Earl Grey. Hot.'
                        ],
                        'created_at' => 1234567890,
                        'results' => [
                            'account_status' => null,
                            'registered_name' => null,
                        ]
                    ]
                ]
            ],
            'created_at' => 0
        ];

        $storkMock = Mockery::mock(Stork::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('request')
            ->once()
            ->with(Mockery::any(), Mockery::on(function($payload) use ($expectedPayload) {
                $payloadData = json_decode($payload['event']['payload'], true);
                $this->assertArraySelectiveEquals($expectedPayload, $payloadData);
                return true;
            }), Mockery::any())
            ->andReturn(new \WpOrg\Requests\Response);

        $this->startTest();
    }

    public function testFavCitiWebhookForwardToNewService()
    {
        $mock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $mock);

        $mock->shouldReceive('handleBankWebhook')
            ->withArgs([Mockery::any(), 'citi'])
            ->times(1);

        $this->startTest();
    }
}

