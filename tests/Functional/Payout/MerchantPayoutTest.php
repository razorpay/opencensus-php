<?php


namespace RZP\Tests\Functional\Payout;

use Mail;
use Config;

use RZP\Models\Payout;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class MerchantPayoutTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use PayoutTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    public function testCreatePayout()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnDemand()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => 'yesbank']);

        $this->ba->proxyAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout',true);

        $txn = $this->getLastEntity('transaction',true);

        $this->assertEquals('payout', $txn['type']);

        $this->assertEquals(398, $txn['amount']);

        $this->assertEquals(602, $txn['fee']);

        $this->assertEquals(1000, $txn['debit']);

        return $payout;
    }

    public function testRetryMerchantOnDemandPayout()
    {
        $payout = $this->testCreateMerchantPayoutOnDemand();

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::REVERSED
            ]);

        $nodalBeneficiary = $this->getLastEntity('nodal_beneficiary', true);

        $this->fixtures->edit(
            'nodal_beneficiary',
            $nodalBeneficiary['id'],
            [
                'updated_at' => $nodalBeneficiary['updated_at'] - 70,
            ]);

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $payoutAttempt['id'],
            [
                'status' => Attempt\Status::FAILED
            ]);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($payout['transaction_id'], $txn['id']);

        $this->retryPayout($payout['id']);

        $newPayout = $this->getLastEntity('payout', true);

        $newPayoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Payout\Status::PROCESSED, $newPayout['status']);
        $this->assertEquals(Attempt\Status::PROCESSED, $newPayoutAttempt['status']);

        // Verify attempt entity
        $this->assertEquals($newPayout['attempts'], 1);
        $this->assertEquals($newPayout['id'], $newPayoutAttempt['source']);
        $this->assertEquals($newPayout['merchant_id'], $newPayoutAttempt['merchant_id']);
        $this->assertNull($newPayout['fund_account_id']);
        $this->assertNotNull($newPayout['batch_fund_transfer_id']);
        $this->assertNotNull($newPayoutAttempt['batch_fund_transfer_id']);
        $this->assertEquals($newPayout['batch_fund_transfer_id'], $newPayoutAttempt['batch_fund_transfer_id']);
        $this->assertEquals($payout['amount'], $newPayout['amount']);

        // ----- End of testing payout retry for failed payouts ------ //

        return $newPayout;
    }

    public function testCreateMerchantPayout()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutWithModulo()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutWithMinAmount()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnDemandOnLowBalance()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 100]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnHoldFunds()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['hold_funds' => true]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnMinAmount()
    {
        $this->fixtures->create('pricing:payout_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYz']);

        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOnDemandPayoutFetchFees()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->ba->proxyAuth();

        $this->startTest();
    }


    public function testCreateMerchantPayoutOnDemandWithFtsRampFailure()
    {
        $this->mockRazorxTreatment();

        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => 'axis2']);

        $this->ba->proxyAuth();

        $this->startTest();

        $fta = $this->getLastEntity('fund_transfer_attempt',true);

        $txn = $this->getLastEntity('transaction',true);

        $this->assertEquals('payout', $txn['type']);

        $this->assertEquals(398, $txn['amount']);

        $this->assertEquals(602, $txn['fee']);

        $this->assertEquals(1000, $txn['debit']);

        $this->assertEquals(0, $fta['is_fts']);
    }

    public function testCreateMerchantPayoutOnDemandWithFtsRampSuccess()
    {
        $this->mockRazorxTreatment();

        $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->startTest();

        $fta = $this->getLastEntity('fund_transfer_attempt',true);

        $this->assertEquals(1, $fta['is_fts']);
    }
}
