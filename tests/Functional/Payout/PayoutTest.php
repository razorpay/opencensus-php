<?php

namespace RZP\Tests\Functional\Payout;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Payout;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Payout\PayoutTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PayoutTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use SettlementTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['payout']);
    }

    public function testCreatePayout(): array
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payout['destination'], 'ba_' . $payoutAttempt['bank_account_id']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('txn_' . $payout['transaction_id'], $txn['id']);

        return $payout;
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


    public function testCreatePayoutFundsOnHold()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->holdFunds();

        $this->startTest();
    }

    public function testCreatePayoutInsufficientBalance()
    {
        return $this->startTest();
    }

    public function testGetPayouts()
    {
        $payout = $this->testCreatePayout();

        $payout = $this->testCreatePayout();

        $this->ba->privateAuth();

        $payouts = $this->startTest();

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayout()
    {
        $this->testCreatePayout();

        $payout = $this->getLastEntity('payout', false);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/'. $payout['id'];

        $payout2 = $this->startTest();

        $this->assertEquals($payout, $payout2);
    }

    public function testCreatePaymentPayout(): array
    {
        $payment = $this->fixtures->create('payment:settled');

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        $payout = $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1000, $payment['amount_paidout']);

        $payout2 = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['id'], $payout2['id']);

        $this->assertEquals($payment['id'], 'pay_' . $payout2['payment_id']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout2['id'], $payoutAttempt['source']);
        $this->assertEquals($payout2['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payout2['destination'], 'ba_' . $payoutAttempt['bank_account_id']);

        return $payout;
    }

    public function testPaymentPayoutAmountGreaterThanCapture()
    {
        $payment = $this->fixtures->create('payment:settled', ['amount' => 2500]);

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        $payout = $this->startTest();
    }

    public function testPaymentPayoutPartial()
    {
        $payment = $this->fixtures->create('payment:settled', ['amount' => 7000]);

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        $payout1 = $this->startTest();

        $payout2 = $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_paidout'], $payout1['amount'] + $payout2['amount']);

        $payoutAttempts = $this->getEntities('fund_transfer_attempt', [], true);

        $this->assertEquals(2, $payoutAttempts['count']);
    }

    public function testCreatePaymentPayoutNotSettledLiveMode()
    {
        $payment = $this->fixtures->on('live')->create('payment:captured');

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateBankAccountPayoutOnCardPayment()
    {
        $card = $this->fixtures->on('live')->create('card', ['type' => 'credit']);

        $payment = $this->fixtures->on('live')->create('payment:captured');
        $this->fixtures->on('live')->edit('payment', $payment['id'], ['card_id' => $card['id']]);

        $this->fixtures->on('live')->edit('transaction', $payment->getTransactionId(), ['settled' => 1]);

        $data['request']['url'] = '/payments/'. $payment->getPublicId() . '/payouts';

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest($data);
    }

    public function setPaymentPayoutUrl($payment, & $request)
    {
        $request['url'] = '/payments/'. $payment->getPublicId() . '/payouts';
    }

    public function testInitiatePayoutSuccess(): array
    {
        $this->ba->privateAuth();
        $p1 = $this->testCreatePayout();

        $this->ba->privateAuth();
        $p2 = $this->testCreatePaymentPayout();

        $createdAt = Carbon::today(Timezone::IST)->addDays(10);

        Carbon::setTestNow($createdAt);

        $this->ba->adminAuth();

        $content = $this->initiatePayouts();

        $this->assertNotNull($content['kotak']['payout_text_file']);

        $this->assertEquals(2, $content['kotak']['count']);

        // Verify attempts
        $attempts = $this->getEntities('fund_transfer_attempt', [], true);

        $this->assertEquals(2, $attempts['count']);

        $attempts = $attempts['items'];

        // Verfiy batch fund transfer
        $bft = $this->getLastEntity('batch_fund_transfer', true);

        foreach ($attempts as $attempt)
        {
            $this->assertTestResponse($attempt, 'testPayoutAttemptSuccess');

            $this->assertEquals($bft['id'], $attempt['batch_fund_transfer_id']);
        }

        // Verify payouts
        $payouts = $this->getEntities('payout', [], true);

        $this->assertEquals(2, $payouts['count']);

        $payouts = $payouts['items'];
        foreach ($payouts as $payout)
        {
            $this->assertTestResponse($payout, 'testPayoutInitiateSuccess');

            $this->assertEquals($bft['id'], $payout['batch_fund_transfer_id']);
        }

        Carbon::setTestNow();

        return $content;
    }

    public function testPayoutReconciliation()
    {
        $payoutFiles = ($this->testInitiatePayoutSuccess())['kotak']['payout_text_file'];

        // Generate reconciliation file, settlement and payout have common implementation
        $payoutReconciliationFile = $this->generateSetlReconciliationFile($payoutFiles);

        // Reconcile settlements, same route is being used as both are h2h
        $content = $this->reconcileSettlements($payoutReconciliationFile);

        $this->assertEquals(2, $content['total_count']);
        $this->assertEquals(0, $content['failures_count']);

        // Verify attempts
        $attempts = $this->getEntities('fund_transfer_attempt', [], true);
        $attempts = $attempts['items'];

        foreach ($attempts as $attempt)
        {
            $this->assertTestResponse($attempt, 'testPayoutAttemptReconSuccess');
            $this->assertNotNull($attempt[Attempt\Entity::UTR]);
        }

        // Verify payouts
        $notNullKeys = [Payout\Entity::UTR, Payout\Entity::SETTLED_ON, Payout\Entity::STATUS];
        $payouts = $this->getEntities('payout', [], true);
        $payouts = $payouts['items'];

        foreach ($payouts as $payout)
        {
            foreach ($notNullKeys as $key)
            {
                $this->assertNotNull($payout[$key]);
            }
        }

        // Verfiy batch fund transfer
        $bft = $this->getLastEntity('batch_fund_transfer', true);
        $this->assertEquals(2, $bft['processed_count']);
    }
}
