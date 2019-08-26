<?php

namespace RZP\Tests\Functional\FundAccount;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

/**
 * @group dns-sensitive
 */
class FundAccountValidationTest extends TestCase
{
    use AttemptTrait;
    use MocksDnsTrait;
    use FundAccountTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;
    use FundAccountValidationTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FundAccountValidationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['fund_account_validations']);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $this->ba->privateAuth();
    }

    public function testCreateValidationWithFundAccountId()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
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
        $this->assertEquals(true, $txn['settled']);
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

    public function testCreateValidationWithWrongFundAccountId()
    {
        $this->startTest();
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
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

        $this->createValidationWithFundAccountEntity();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testGetValidations()
    {
        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFundAccValidationOnPrepaidModelWithFeeCredits()
    {
        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

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
        $this->assertEquals(true, $txn['settled']);
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

        $this->startTest();
    }

    public function testWebhookFundAccountValidationCompleted()
    {
        $this->createWebhook([
            'events' => [
                'fund_account.validation.completed' => '1',
            ]
        ]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertEquals('fund_account.validation.completed', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->createValidationWithFundAccountEntity();
    }

    public function testFundAccValidationRetry()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('created', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals(null, $fav['results']['account_status']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        // Retry At will be calculated and set because
        // insufficient fund is an internal error and can be retried.
        $this->assertNotNull($fav['retry_at']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('failed', $fta['status']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        // Note: because no fee credits are available
        $this->assertEquals(1000000, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
        // To make a success request receipt should be changed
        $this->fixtures->fund_account_validation->editEntity('fund_account_validation', $fav['id'], ['receipt' => 'lol']);

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations/retry',
            'content' => ['fund_account_validation_ids' => [preg_replace('/^fav_/', '', $fav['id'])]]
        ];

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        $fav         = $this->getLastEntity('fund_account_validation', true);
        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals('Someone', $fav['results']['registered_name']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('processed', $fta['status']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);
    }

    public function testFundAccValidationRetryWithCron()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];
        $this->testData[__FUNCTION__]['request']['content']['receipt'] =  'failed_response_insufficient_funds';

        $this->startTest();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        // Queue will be processed by now.
        $this->assertEquals('created', $fav['status']);
        $this->assertEquals(null, $fav['results']['account_status']);

        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('failed', $fta['status']);

        // Retry At will be calculated and set because
        // insufficient fund is an internal error and can be retried.
        $this->assertNotNull($fav['retry_at']);

        // To make a success receipt remark should be changed and
        // retry_at should be changed to some previous time.
        $this->fixtures->fund_account_validation->editEntity('fund_account_validation', $fav['id'], ['receipt' => 'lol', 'retry_at' => $fav['retry_at'] - 3000]);

        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations/retry/all',
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $fav         = $this->getLastEntity('fund_account_validation', true);
        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals(2, $fav['attempts']);
        $this->assertEquals(null, $fav['retry_at']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals('Someone', $fav['results']['registered_name']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals('processed', $fta['status']);
    }

    public function testFundAccValidationWhenFailedDuringReconWithNonInternalError()
    {
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
        $this->enableBusinessBankingForMerchant();

        $this->createMerchantBankingBalance();

        $this->createFAVBankingPricingPlan();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $this->startTest();

        // get database entities
        $balance = $this->getEntityById('balance', 'xbalance000000', true);
        $fav = $this->getLastEntity('fund_account_validation', true);
        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $txn = $this->getLastEntity('transaction', true);

        // validate balance entry in database
        $this->assertEquals(9997, $balance['balance']);


        // validate fund account validation last entry
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($fav['balance_id'], 'xbalance000000');
        $this->assertEquals('10000000000000', $fav['merchant_id']);
        $this->assertEquals('fund_account.validation', $fav['entity']);

        // validate transaction table last entry
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals(100, $txn['amount']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals('xbalance000000', $txn['balance_id']);

        // validate fund transfer attempt table last entry
        $this->assertEquals('penny_testing', $fta['purpose']);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);
    }

    protected function createFAVBankingPricingPlan()
    {
        $pricingPlan = [
            'plan_name'           => 'FAV Plan',
            'percent_rate'        => 290,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'plan_id'             => '1hDYlICobzOCYt',
            'product'             => 'banking',
            "feature"             => 'fund_account_validation',
            'payment_method'      => 'bank_account',
            'account_type'        => 'shared'
        ];

        $this->fixtures->create('pricing', $pricingPlan);
    }

    protected function createMerchantBankingBalance() {
        $balance = [
            'id' => 'xbalance000000',
            'balance' => 10000,
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'currency' => 'INR',
            'account_number' => '2224440041626905',
        ];

        $this->fixtures->create('balance', $balance);
    }

    protected function enableBusinessBankingForMerchant() {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['business_banking' => '1']);
    }
}
