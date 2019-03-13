<?php

namespace RZP\Tests\Functional\FundAccount;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class FundAccountValidationTest extends TestCase
{
    use AttemptTrait;
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

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);

        $this->assertNotNull($fta['narration']);

        return $response;
    }

    public function testCreateValidationWithWrongFundAccountId()
    {
        $this->startTest();
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $this->createValidationWithFundAccountEntity();
    }

    public function testCreateValidationWithWrongFundAccountEntity()
    {
        $this->startTest();
    }

    public function testCreateValidationForCustomerFeeBearer()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->createValidationWithFundAccountEntity();
    }

    public function testGetValidations()
    {
        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFundAccValidationWithReconOnPrepaidModelWithFeeCredits()
    {
        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->ba->privateAuth();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('prepaid', $txn['fee_model']);
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        $this->assertEquals(354, $txn['fee_credits']);
        $this->assertEquals('fee', $txn['credit_type']);
    }

    public function testFundAccValidationWhenFailedDuringRecon()
    {
        $this->markTestSkipped();

        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->ba->privateAuth();

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createValidationWithFundAccountEntity();

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        // Right now we are charging even though failure reason could be internal.TODO: fix
        $this->fixtures->merchant->editEntity('fund_transfer_attempt', $fta['id'], ['status' => 'initiated', 'bank_status_code' => 'FAILED']);

        $this->reconcileEntitiesForChannel('yesbank');

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('invalid', $fav['results']['account_status']);
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('prepaid', $txn['fee_model']);
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        $this->assertEquals(354, $txn['fee_credits']);
        $this->assertEquals('fee', $txn['credit_type']);
    }

    public function testFundAccValidationWithReconOnPostpaidModelWithFeeCredits()
    {
        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

        $this->ba->privateAuth();

        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

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

        $this->assertEquals(354, $txn['fee_credits']);
        $this->assertEquals('fee', $txn['credit_type']);
    }

    public function testFundAccValidationWithCustomerFeeBearer()
    {
        $this->testCreateValidationForCustomerFeeBearer();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        $this->assertEquals(0, $txn['debit']);

        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testFundAccValidationWithReconOnPostpaidModelWithNoFeeCredits()
    {
        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(1000000, $txn['balance']);

        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals('default', $txn['credit_type']);
    }

    public function testFundAccValidationWithReconOnPrepaidModelWithNoFeeCredits()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'prepaid']);

        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('prepaid', $txn['fee_model']);
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals($fav['amount'], $txn['amount']);
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
}
