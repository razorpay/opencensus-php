<?php

namespace RZP\Tests\Functional\Payout;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Payout;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PayoutTest extends TestCase
{
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

    public function testRetryPayout(): array
    {
        $payout = $this->testCreatePayout();

        // ----- start testing payout retry for non failed payouts ------ //

        $this->retryPayout((array) $payout['id'], false);

        $payoutAfterRetry = $this->getLastEntity('payout', true);
        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($payout['status'], $payoutAfterRetry['status']);
        $this->assertNotEquals($payoutAttempt['status'], Attempt\Status::FAILED);

        // Verify attempt entity
        $this->assertEquals($payoutAfterRetry['id'], $payoutAttempt['source']);
        $this->assertEquals($payoutAfterRetry['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payoutAfterRetry['destination'], 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payoutAfterRetry['batch_fund_transfer_id'], $payoutAttempt['batch_fund_transfer_id']);

        // ----- End of testing payout retry for non failed payouts ------ //

        // ----- start testing payout retry for failed payouts ------- //

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::FAILED
            ]);

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $payoutAttempt['id'],
            [
                'status' => Attempt\Status::FAILED
            ]);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('txn_' . $payout['transaction_id'], $txn['id']);

        $this->retryPayout((array) $payout['id']);

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($payout['status'], Payout\Status::CREATED);
        $this->assertEquals($payoutAttempt['status'], Attempt\Status::CREATED);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payout['destination'], 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertNull($payout['batch_fund_transfer_id']);
        $this->assertNull($payoutAttempt['batch_fund_transfer_id']);

        // ----- End of testing payout retry for failed payouts ------ //

        return $payout;
    }

    protected function retryPayout(array $ids, $success = true)
    {
        $request = [
            'url' => '/payouts/retry',
            'method' => 'POST',
            'content' => [
                'ids' => $ids
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $key = ($success === true)? 'payouts_retried' : 'not_attempted';

        $this->assertEquals(
            $ids,
            array_map(
                function($val)
                {
                    return 'pout_' . $val;
                },  $response[$key]));
    }

    public function testCreateMerchantPayout()
    {
        $this->ba->appAuth();

        $response = $this->startTest();
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

    public function testCreatePayoutAttemptSuccess()
    {
        $this->ba->privateAuth();
        $p1 = $this->testCreatePayout();

        $this->ba->privateAuth();
        $p2 = $this->testCreatePaymentPayout();

        $createdAt = Carbon::today(Timezone::IST)->addDays(10);

        Carbon::setTestNow($createdAt);

        $this->ba->adminAuth();

        // Verify attempts
        $attempts = $this->getEntities('fund_transfer_attempt', [], true);

        $this->assertEquals(2, $attempts['count']);

        $attempts = $attempts['items'];

        foreach ($attempts as $attempt)
        {
            $this->assertTestResponse($attempt, 'testPayoutAttemptSuccess');

            $this->assertNull($attempt['batch_fund_transfer_id']);
        }

        // Verify payouts
        $payouts = $this->getEntities('payout', [], true);

        $this->assertEquals(2, $payouts['count']);

        $payouts = $payouts['items'];
        foreach ($payouts as $payout)
        {
            $this->assertTestResponse($payout, 'testPayoutEntitySuccess');

            $this->assertNull($payout['batch_fund_transfer_id']);
        }

        Carbon::setTestNow();
    }
}
