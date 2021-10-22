<?php


namespace RZP\Tests\Functional\Payout;

use DB;
use Mail;
use Config;

use Carbon\Carbon;
use RZP\Models\Payout;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Feature\Constants;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class MerchantPayoutTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use PayoutTrait;
    use WorkflowTrait;

    protected function setUp(): void
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

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreatePayout()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnDemand()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => Channel::ICICI]);

        $this->ba->proxyAuth();

        // We want the test to be during banking hours as es on demand can switch to different channel during non banking hours.
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $payout = $this->getLastEntity('payout',true);

        $this->assertEquals(Mode::NEFT, $payout['mode']);

        $txn = $this->getLastEntity('transaction',true);

        $this->assertEquals('payout', $txn['type']);

        $this->assertEquals(Channel::ICICI, $txn['channel']);

        $this->assertEquals(398, $txn['amount']);

        $this->assertEquals(602, $txn['fee']);

        $this->assertEquals(1000, $txn['debit']);

        return $payout;
    }

    public function testCreateMerchantPayoutOnDemandAmountInCrores()
    {
        // Es on demand should be immune to 80 L limit applied to merchant/payouts. Tests during banking hours.
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->ba->proxyAuth();

        // We want the test to be during banking hours as es on demand can switch to different channel during non banking hours.
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnDemandNonBankingHoursWithLessThan2Lakhs()
    {
        // Es on demand should be given to merchants during non banking hours.
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => Channel::AXIS2]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->ba->proxyAuth();

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 18, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals(Channel::ICICI, $txn[Transaction\Entity::CHANNEL]);

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals(Channel::ICICI, $payout[Payout\Entity::CHANNEL]);
    }

    public function testCreateMerchantPayoutOnDemandHoliday()
    {
        // Es on demand should be given to merchants during holidays.
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => Channel::AXIS2]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->ba->proxyAuth();

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 16, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals(Channel::ICICI, $txn[Transaction\Entity::CHANNEL]);

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals(Channel::ICICI, $payout[Payout\Entity::CHANNEL]);
    }

    public function testCreateMerchantPayoutOnDemandExceedAmountLimit()
    {
        // Es on demand should fail for amounts that exceed 2 cr.
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->ba->proxyAuth();

        // We want the test to be during banking hours as es on demand can switch to different channel during non banking hours.
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnDemandExceedAmountLimitNonBankingHours()
    {
        // Es on demand 24x7 should fail for amounts that exceed 2 L.
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->ba->proxyAuth();

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 18, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();
    }

    public function testRetryMerchantOnDemandPayout()
    {
        $this->markTestSkipped();

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
        $this->ba->cronAuth() ;

        $this->startTest();
    }

    public function testCreateMerchantPayoutWithModulo()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutWithMinAmount()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnDemandOnLowBalance()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 100]);

        $this->ba->proxyAuth();

        // We want the test to be during banking hours as es on demand can switch to different channel during non banking hours.
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnHoldFunds()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->on('live')->base->editEntity('merchant', '10000000000000', ['hold_funds' => true]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnHoldFundsOnTestMode()
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

    public function testCreateMerchantPayoutExceedAmountLimit()
    {
        // Merchant payouts should fail for amounts that exceed 80 L.
        $this->ba->cronAuth();

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
        $this->mockRazorxTreatment('yesbank','on');

        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => 'axis2']);

        $this->ba->proxyAuth();

        // We want the test to be during banking hours as es on demand can switch to different channel during non banking hours.
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

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
        $this->mockRazorxTreatment('yesbank','on');

        $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->startTest();

        $fta = $this->getLastEntity('fund_transfer_attempt',true);

        $this->assertEquals(1, $fta['is_fts']);
    }

    public function testCreateMerchantPayoutOnDemandDoesNotTriggerWorkflow()
    {
        $this->fixtures->on('live')->edit('balance','10000000000000',['balance' => '1000000']);

        // We want the test to be during banking hours as es on demand can switch to different channel during non banking hours.
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->app['config']->set('heimdall.workflows.mock', false);

        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => Channel::ICICI]);

        // Create payout with owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $payout = $this->getLastEntity('payout',true,'live');

        $this->assertEquals(Mode::NEFT, $payout['mode']);

        $txn = $this->getLastEntity('transaction',true,'live');

        $this->assertEquals('payout', $txn['type']);

        $this->assertEquals(Channel::ICICI, $txn['channel']);

        $this->assertEquals(398, $txn['amount']);

        $this->assertEquals(602, $txn['fee']);

        $this->assertEquals(1000, $txn['debit']);

        $wfAction = $this->getLastEntity('workflow_action',true,'live');

        $this->assertEquals(null, $wfAction);

        return $payout;
    }

    public function testCreateMerchantPayoutDoesntTriggerWorkflow()
    {
        $this->fixtures->on('live')->edit('balance','10000000000000',['balance' => '1000000']);

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->app['config']->set('heimdall.workflows.mock', false);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => Channel::ICICI]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $txn = $this->getLastEntity('transaction',true,'live');

        $this->assertEquals('payout', $txn['type']);

        $this->assertEquals(Channel::ICICI, $txn['channel']);

        $this->assertEquals(1602, $txn['amount']);

        $this->assertEquals(602, $txn['fee']);

        $this->assertEquals(1602, $txn['debit']);

        $wfAction = $this->getLastEntity('workflow_action',true,'live');

        $this->assertEquals(null, $wfAction);
    }
}
