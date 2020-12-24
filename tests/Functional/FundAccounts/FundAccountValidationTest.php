<?php

namespace RZP\Tests\Functional\FundAccount;

use Queue;
use \RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Jobs\FaVpaValidation;
use RZP\Models\FundAccount\Validation\Entity as Validation;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
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

    public function setUp()
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

        $fundAccountResponse = $this->createFundAccountBankAccount();

        // enabling the feature here for test merchant
        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['utr']);
        $this->assertEquals('10000000000000', $fav['balance_id']);

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

    public function testCreateValidationWithExposeUTRNotSetInResponse()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->enableRazorXTreatmentForRazorX();

        // remove features is not required as by default feature would be disabled
        //$this->fixtures->merchant->removeFeatures(['expose_fa_validation_utr']);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $response = $this->startTest();

        $fav      = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['registered_name']);

        // utr should not be present in response['results'] array
        $this->assertArrayKeysExist($response['results'], ['account_status','registered_name']);

        return $response;
    }

    public function testCreateValidationWithWrongFundAccountId()
    {
        $this->startTest();
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
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

    public function testCreateValidationForCustomerFeeBearer()
    {
        // Fee Bearer doesn't have any effect on Fund Account Validation.
        // Transaction is always created for Fee Bearer: Platform.
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->enableRazorXTreatmentForRazorX();

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

        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFundAccValidationOnPrepaidModelWithFeeCredits()
    {
        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->enableRazorXTreatmentForRazorX();

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

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response';

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
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

        $payoutId = $fav->getId();

        $eventTestDataKey = 'testFiringOfWebhookOnFAVCompletionWithStork';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'utr'    => '933815233814',
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.completed', $eventTestDataKey);

        $this->updateFtaAndSource($payoutId, 'PROCESSED','933815233814','SUCCESS',false);

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('processed', $fta->getStatus());

        $this->assertEquals('completed', $payout->getStatus());

    }

    public function testWebhookFiringFundAccountValidationFailed()
    {
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

        $this->updateFtaAndSource($payoutId, 'FAILED','944926344925','ACCOUNT_INVALID',true);

        $payout = $this->getDbEntityById('fund_account_validation', $payoutId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('failed', $fta->getStatus());

        $this->assertEquals('failed', $payout->getStatus());

    }

    public function testFundAccValidationWhenFailedDuringReconWithNonInternalError()
    {
        $this->enableRazorXTreatmentForRazorX();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_beneficiary_not_accepted';

        $response = $this->startTest();

        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('invalid', $fav['results']['account_status']);

        // Retry At will be calculated and set because
        // beneficiary not accepted is not an internal error
        // and there is not need to retry.
        $this->assertNull($fav['retry_at']);
    }

    public function testFundAccValidationWithAccountNumberAndBankAccount()
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
        $this->assertEquals(9999997, $balance['balance']);

        // validate fund account validation last entry
        $this->assertEquals($balance['id'], $fav['balance_id']);
        $this->assertEquals('10000000000000', $fav['merchant_id']);
        $this->assertEquals(Entity::PUBLIC_ENTITY_NAME, $fav['entity']);

        // validate transaction table last entry
        $this->assertEquals(Constants\Entity::FUND_ACCOUNT_VALIDATION, $txn['type']);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals(100, $txn['amount']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals($balance['id'], $txn['balance_id']);
        $this->assertEquals(9999997, $txn['balance']);

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
    }

    public function testFundAccValidationWithAccountNumberThatIsAlreadyProcessedButUtrNeeded()
    {
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

        $this->makeRequestAndGetContent($request);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(1, $fav['attempts']);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertNotNull($fav['utr']);
    }

    //when ifsc is in the list of oldnewifscmapping and also there is already a completed fav within 30 days present,
    //it should not pick the fav status from cache..instead it should call fts and do a fresh validation
    public function testFundAccValidationWithAccountNumberThatIsAlreadyProcessedWithOldIfsc()
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

    public function testFixTransactionSettledAt()
    {
        $this->enableRazorXTreatmentForRazorX();

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

    public function testFundAccValidationBankingFailedMissingFundAccountId()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->startTest();
    }

    public function testFundAccValidationFailedFundAccountCardType()
    {
        Queue::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->mockCardVault();

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

    public function testFundAccValidationWithFailedStatus()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->enableRazorXTreatmentForRazorX();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

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

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $balance = $this->getLastEntity('balance', true);
        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
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

        $txn = $this->getLastEntity('transaction', true);
        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals($reversal['id'], $txn['entity_id']);
        $this->assertEquals('reversal', $txn['type']);
        // these are na for prepaid.
        $this->assertEquals('na', $txn['fee_bearer']);
        $this->assertEquals('na', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(-3, $txn['fee']);
        $this->assertEquals(-3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(3, $txn['credit']);
        //Not sure if it should be 100 or 0
        $this->assertEquals(0, $txn['amount']);
        $this->assertEquals($balance['id'], $txn['balance_id']);
        $this->assertEquals(10000000, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testFundAccValidationWithFailedStatusOnPostpaid()
    {
        $this->enableRazorXTreatmentForRazorX();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

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

    public function testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalanceNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->fixtures->merchant->editBalance('0');

        $response = $this->startTest();

        $this->assertArrayNotHasKey(Error::STEP, $response['error']);

        $this->assertArrayNotHasKey(Error::METADATA, $response['error']);
    }
}
