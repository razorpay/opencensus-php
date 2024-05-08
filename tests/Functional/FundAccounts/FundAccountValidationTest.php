<?php

namespace RZP\Tests\Functional\FundAccount;

use App;
use Mockery;
use \RZP\Constants;
use RZP\Error\Error;
use RZP\Jobs\PayoutUsageEventProcessing;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Jobs\Transactions;
use RZP\Models\Admin\Admin;
use RZP\Services\DiagClient;
use RZP\Models\Pricing\Fee;
use RZP\Jobs\FavQueueForFTS;
use RZP\Jobs\FaVpaValidation;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\FavService\Fetch;
use RZP\Services\FavService\Update;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\RuntimeException;
use Illuminate\Support\Facades\Queue;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\FundAccount\Validation\Entity;
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

        $this->setMockRazorxTreatment([
            RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control',
            RazorxTreatment::SEND_CHARGE_COLLECTION_EVENT_RX => 'on',
        ]);

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

        $this->setMockRazorxTreatment([
            RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control',
            RazorxTreatment::SEND_CHARGE_COLLECTION_EVENT_RX => 'on',
        ]);

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

        Queue::assertPushed(PayoutUsageEventProcessing::class, function($job) use ($fav)
        {
            $this->assertEquals($fav['id'], $job->getEntityID());

            $this->assertEquals('fund_account_validation', $job->getEntityType());

            $expectedParams = [
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_ID                    => $fav['id'],
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_MERCHANT_ID           => $fav['merchant_id'],
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_MODE                  => 'imps',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_STATUS                => 'completed',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_AMOUNT                =>  1.0,
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_INTERFACE             => 'api',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_AGGREGATION           => 'single',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_EVENT_TYPE            => "bank_account_validation",
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_PAYLOAD_SOURCE        => "vanilla",
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_FEATURE               => 'pennydrop',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_SOURCE_ACCOUNT_NUMBER => '',
            ];

            $this->assertArraySelectiveEquals($expectedParams, $job->getParams());

            return true;
        });

        return $response;
    }

    private function mockDiag()
    {
        $diagMock = $this->getMockBuilder(DiagClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['trackEvent'])
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

        // Test worker
        $favQueueForFts = new FavQueueForFTS('test', preg_replace('/^fav_/', '', $fav['id']));
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

        // Test worker
        $favQueueForFts = new FavQueueForFTS('test', preg_replace('/^fav_/', '', $fav['id']));
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

        // Test worker
        $favQueueForFts = new FavQueueForFTS('test', preg_replace('/^fav_/', '', $fav['id']));
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

        // Test worker
        $favQueueForFts = new FavQueueForFTS('test', preg_replace('/^fav_/', '', $fav['id']));
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

        // Test worker
        $favQueueForFts = new FavQueueForFTS('test', preg_replace('/^fav_/', '', $fav['id']));
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

        $this->ba->payoutInternalAppAuth();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $content = $this->testData[__FUNCTION__]['request']['content'];

        $mock = Mockery::mock(FavServiceUpdate::class);

        $this->app->instance(FavServiceUpdate::FAV_SERVICE_UPDATE, $mock);

        $mock->shouldReceive('updateFavInMicroservice')
            ->withArgs(function ($fav_id, $fund_transfer_id, $type){

                return ($fav_id === '1234567890' && empty($fund_transfer_id) == false);})
            ->times(1);

        $this->startTest();

        Queue::assertPushed(FavQueueForFTS::class);

        $favQueueForFts = new FavQueueForFTS('test', $content['fav_id'], $content);

        $favQueueForFts->handle();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'test');

        // Penny drop assertion
        $this->assertEquals("1234567890", $fta['source_id']);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($content['bank_account']['id'], $fta['bank_account_id']);
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

    public function createBankResponseForFavServiceMock($status='created', $contact = false)
    {
        $response = new \WpOrg\Requests\Response();

        $content = [
            'id'=> 'fav_00000000000001',
            'entity'=> 'fund_account.validation',
            'status'=> 'created',
            'validation_results'=> [
                'account_status'=> null,
                'registered_name'=> null,
                'details'=> null,
                'name_match_score'=> null
            ],
            'status_details'=> [
                'description'=> 'Validation request is created',
                'source'=> 'internal',
                'reason'=> 'validation_request_created'
            ],
            'reference_id'=> '112233',
            'notes'=> [
                'random_key_1'=> 'Make it so.',
                'random_key_2'=> 'Tea. Earl Grey. Hot.'
            ],
            'fund_account'=> [
                'id'=> 'fa_00000000000001',
                'entity'=> 'fund_account',
                'account_type'=> 'bank_account',
                'bank_account'=> [
                    'name'=> 'Gaurav Kumar',
                    'bank_name'=> 'HDFC',
                    'ifsc'=> 'HDFC0000053',
                    'account_number'=> '765432123456789'
                ],
                'active'=> true,
                'created_at'=> 1567064019,
            ]
        ];

        if ($contact === true)
        {
            $content['fund_account']['contact'] = [
                    'id'=> 'cont_00000000000001',
                    'entity'=> 'contact',
                    'name'=> 'Gaurav Kumar',
                    'email'=> 'gaurav.kumar@example.com',
                    'contact'=> '9123456789',
                    'type'=> 'employee',
                    'reference_id'=> 'Acme Contact ID 12345',
                    'active'=> true,
                    'created_at'=> 1567064019,
                    'notes'=> [
                        'notes_key_1'=> 'Tea, Earl Grey, Hot',
                        'notes_key_2'=> 'Tea, Earl Grey... decaf.']
                ];
        }

        $response->body = json_encode($content);
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }

    public function createVpaResponseForFavServiceMock($status='created', $contact = false)
    {
        $response = new \WpOrg\Requests\Response();

        $content = [
            'id'=> 'fav_00000000000001',
            'entity'=> 'fund_account.validation',
            'status'=> 'created',
            'validation_results'=> [
                'account_status'=> null,
                'registered_name'=> null,
                'details'=> null,
                'name_match_score'=> null
            ],
            'status_details'=> [
                'description'=> 'Validation request is created',
                'source'=> 'internal',
                'reason'=> 'validation_request_created'
            ],
            'reference_id'=> '112233',
            'notes'=> [
                'random_key_1'=> 'Make it so.',
                'random_key_2'=> 'Tea. Earl Grey. Hot.'
            ],
            'fund_account'=> [
                'id'=> 'fa_00000000000001',
                'entity'=> 'fund_account',
                'account_type'=> 'vpa',
                'vpa'=> [
                    'address' => 'gaurav.kumar@exampleupi'
                ],
                'active'=> true,
                'created_at'=> 1567064019,
            ]
        ];

        if ($contact === true)
        {
            $content['fund_account']['contact'] = [
                'id'=> 'cont_00000000000001',
                'entity'=> 'contact',
                'name'=> 'Gaurav Kumar',
                'email'=> 'gaurav.kumar@example.com',
                'contact'=> '9123456789',
                'type'=> 'employee',
                'reference_id'=> 'Acme Contact ID 12345',
                'active'=> true,
                'created_at'=> 1567064019,
                'notes'=> [
                    'notes_key_1'=> 'Tea, Earl Grey, Hot',
                    'notes_key_2'=> 'Tea, Earl Grey... decaf.'
                ]
            ];
        }

        $response->body = json_encode($content);
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }

    public function mockFavServiceCreate($account_type, $status = 'created', $contact = false)
    {
        $FavServiceCreateMock = $this->getMockBuilder(FavServiceCreate::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(FavServiceCreate::FAV_SERVICE_CREATE, $FavServiceCreateMock);

        if ($status === 'failed')
        {
            $this->app->fav_service_create
                ->expects($this->once())
                ->method('sendRequest')
                ->willReturn(new \RZP\Exception\ServerErrorException(
                    'server error',
                    code: ErrorCode::SERVER_ERROR

                ));
            return;
        }
        else if ($account_type === 'bank_account') {
            $response = $this->createBankResponseForFavServiceMock($status, $contact);
        }
        else
        {
            $response = $this->createVpaResponseForFavServiceMock($status, $contact);
        }

        $this->app->fav_service_create
                  ->expects($this->once())
                  ->method('sendRequest')
                  ->willReturn($response);
    }

    public function testCreateFaOfTypeBankAndSendRequestToFavService()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->mockFavServiceCreate('bank_account', status: 'created');

        $response = $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account', 'test');

        $bankAccount = $this->getDbLastEntity('bank_account', mode: 'test');

        $this->assertEquals('HDFC0000053', $bankAccount['ifsc']);

        $this->assertEquals('765432123456789', $bankAccount['account_number']);

        $this->assertNotNull($fundAccount);

        $this->assertNotNull($response['fund_account']['id']);
    }

    public function testCreateFaOfTypeVpaAndSendRequestToFavService()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->mockFavServiceCreate('vpa', status: 'created');

        $response = $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $vpa = $this->getLastEntity('vpa', true);

        $this->assertEquals('gaurav.kumar@exampleupi', $vpa['address']);

        $this->assertNotNull($fundAccount);

        $this->assertNotNull($response['fund_account']['id']);
    }

    public function testCreateFaAndContactOfTypeBankAndSendRequestToFavService(){

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->mockFavServiceCreate('bank_account', status: 'created', contact:true);

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

    public function testCreateFaOfTypeBankAndSendRequestToFavServiceFailure()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->expectException('\RZP\Exception\ServerErrorException');

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR);

        $this->mockFavServiceCreate('bank_account', status: 'failed');

        $this->startTest();
    }

    public function testCreateFaAndContactOfTypeVpaAndSendRequestToFavService()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

        $this->mockFavServiceCreate('vpa', status: 'created', contact: true);

        $response = $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $vpa = $this->getLastEntity('vpa', true);

        $this->assertEquals('gaurav.kumar@exampleupi', $vpa['address']);

        $contact = $this->getDbLastEntity('contact');

        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        $this->assertNotNull($vpa);

        $this->assertNotNull($contact);

        $this->assertNotNull($fundAccount);

        $this->assertNotNull($response['fund_account']['id']);
    }

    public function testCreateValidationWithExposeUTRNotSetInResponse()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_COMPOSITE_API_HANDLING => 'on']);

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
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_COMPOSITE_API_HANDLING => 'on']);

        Queue::fake();

        (new AdminService())->setConfigKeys([ConfigKey::PENNILESS_WHITELISTED_BANKS_LIST => ['SBIN']]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::PENNILESS_VALIDATION]);

        $response = $this->startTest();

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
        $this->startTest();
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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
        $this->startTest();
    }

    public function testCreateValidationWithAmountInDecimalString()
    {
        $this->startTest();
    }

    public function testCreateValidationWithAmountInDecimalFloat()
    {
        $this->startTest();
    }

    public function testCreateValidationForCustomerFeeBearer()
    {
        // Fee Bearer doesn't have any effect on Fund Account Validation.
        // Transaction is always created for Fee Bearer: Platform.
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['items'][0]['results']['registered_name']);
    }

    public function testFundAccValidationOnPrepaidModelWithFeeCredits()
    {
        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

    public function createGetApiResponseForFavServiceMock()
    {
        $content = [
        'entity' => "fund_account.validation",
        'fund_account' => [
            'entity' => "fund_account",
            'contact_id' => "cont_1000000contact",
            'account_type' => "bank_account",
            'bank_account' => [
                'ifsc' => "SBIN0007105",
                'bank_name' => "State Bank of India",
                'name' => "Amit M",
                'notes' => [],
                'account_number' => "111000111"
            ],
            'batch_id' => null,
            'active' => true,
            'details' => [
                'ifsc' => "SBIN0007105",
                'bank_name' => "State Bank of India",
                'name' => "Amit M",
                'notes' => [],
                'account_number' => "111000111"
            ],
        ],
        'status' => "completed",
        'amount' => 100,
        'currency' => "INR",
        'notes' => [],
        'results' => [
            'account_status' => "active",
            'registered_name' => "Razorpay Test"
        ],
        ];

        return $content;
    }

    public function testGetFavByIdFromMicroservice()
    {
        $mock = Mockery::mock(Fetch::class);

        $this->app->instance(FavServiceFetch::FAV_SERVICE_FETCH, $mock);

        $this->fixtures->merchant->addFeatures([Feature\Constants::FAV_SERVICE_ENABLED]);

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

        $request['url'] = sprintf($request['url'], $fav['id']);

        $this->ba->privateAuth();

        $mock->shouldReceive('fetch')
            ->withArgs(["fund_account_validation", $fav['id'], []])
            ->andReturn($this->createGetApiResponseForFavServiceMock())
            ->times(1);

        $this->startTest();
    }

    public function testFundAccValidationWithFailedStatus()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->merchant->on('test')->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->fixtures->merchant->on('test')->editBalance('0');

        $this->startTest();
    }

    public function testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalanceNewApiErrorOnLiveMode()
    {
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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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
        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);
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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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

        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'control']);

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
        $this->setMockRazorxTreatment([RazorxTreatment::FAV_PG_LEDGER_CUTOFF => 'on']);

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
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createFundAccountBankAccount();

        $this->startTest();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $balance = $this->getDbEntityById('balance', $fav['balance_id']);

        $this->assertEquals('created', $fav['status']);
        $this->assertEquals('primary', $balance['type']);
    }


}
