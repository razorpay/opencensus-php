<?php

namespace RZP\Tests\Functional\FundAccount;

use Closure;
use Mockery;

use RZP\Models\Merchant\Webhook;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;

class FundAccountValidationTest extends TestCase
{
    use AttemptTrait;
    use FundAccountTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FundAccountValidationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['fund_account_validations']);

        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);

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
        $this->assertEquals('created', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);

        $msg = \RZP\Models\FundAccount\Validation\Processor\BankAccount::PENNY_TESTING_NARRATION;
        $this->assertEquals($msg, $fta['narration']);

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

        $fav = $this->getLastEntity('fund_account_validation', true);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
    }

    public function testFeeForFundAccountValidation()
    {
        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);
        // Default pricing has rule 1zE31zbybacab4 with fixed rate 300
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);
    }

    public function testTransactionForFundAccountValidation()
    {
        $this->createValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
    }

    public function testGetValidations()
    {
        $this->createValidationWithFundAccountEntity();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testWebhookFundAccountValidationCompleted()
    {
        // $this->markTestIncomplete('initiate recon to trigger webhook');

        $this->createWebhook([
            'events' => [
                'fund_account.validation.completed' => '1',
            ]
        ]);

        $testData = $this->testData[__FUNCTION__];

        $this->createValidationWithFundAccountEntity();

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertEquals('fund_account.validation.completed', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->initiateTransferAndReconcile();

        // TODO: Initiate recon to trigger webhook
    }

    protected function initiateTransferAndReconcile()
    {
        $this->initiateTransferAndAssertSuccess('yesbank', 'penny_testing', 1, 'penny_testing');

        $this->reconcileOnlineSettlements('yesbank', false);
    }

    protected function createValidationWithFundAccountEntity(): array
    {
        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('created', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);

        $msg = \RZP\Models\FundAccount\Validation\Processor\BankAccount::PENNY_TESTING_NARRATION;
        $this->assertEquals($msg, $fta['narration']);

        return $response;
    }

    protected function mockInfernoFire(Closure $closure)
    {
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }
}
