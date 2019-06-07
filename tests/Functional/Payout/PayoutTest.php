<?php

namespace RZP\Tests\Functional\Payout;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use Config;
use Illuminate\Support\Facades\Artisan;

use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PayoutTest extends TestCase
{
    use PaymentTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    public function testCreatePayout(): array
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
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

    public function testCreatePayoutForAmountLessThanMinFee()
    {
        // Minimum fee is INR 5, attempts and asserts success when creating payout for INR 1.
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCreateQueuedPayout()
    {
        $this->fixtures->merchant->addFeatures([Constants::QUEUED_PAYOUTS]);

        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->startTest();

        $summary = $this->makePayoutQueueSummaryRequest();

        $this->assertEquals(2, $summary['count']);
        $this->assertEquals(20000002, $summary['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(998229, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(11000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(1, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(10001771, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNotNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNotNull($fta);
    }

    public function testCreatePayoutToInactiveFundAccount()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000001fa',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba',
                'active'       => 0,
            ]);

        $this->startTest();
    }

    public function testCreatePayoutToCardFundAccount()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->startTest();
    }

    public function testCreatePayoutToInactiveContactFundAccount()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'active' => 0]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000001fa',
                'source_id'    => '1000000contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->startTest();
    }

    public function testCreatePayoutWithOtp()
    {
        $testData = $this->testData['testCreatePayoutWithOtp'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth();
        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals("MerchantUser01", $payout['user_id']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
    }

    public function testCreatePayoutWithInvalidOtp()
    {
        $testData = $this->testData['testCreatePayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '1234';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_INCORRECT_OTP);

        $this->startTest();
    }

    public function testRetryPayout(): array
    {
        $payout = $this->testCreatePayout();
        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::REVERSED
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
        $this->assertEquals(Attempt\Status::PROCESSED, $payoutAttempt['status']);

        // Verify attempt entity
        $this->assertEquals($newPayout['attempts'], 1);
        $this->assertEquals($newPayout['id'], $newPayoutAttempt['source']);
        $this->assertEquals($newPayout['merchant_id'], $newPayoutAttempt['merchant_id']);
        $this->assertEquals($newPayout['fund_account_id'], 'fa_100000000000fa');
        $this->assertNotNull($newPayout['batch_fund_transfer_id']);
        $this->assertNotNull($newPayoutAttempt['batch_fund_transfer_id']);
        $this->assertEquals($newPayout['batch_fund_transfer_id'], $newPayoutAttempt['batch_fund_transfer_id']);

        // ----- End of testing payout retry for failed payouts ------ //

        return $newPayout;
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
        $this->assertEquals(Attempt\Status::PROCESSED, $payoutAttempt['status']);

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

    protected function retryPayout($id)
    {
        $request = [
            'url' => "/payouts/$id/retry",
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['id']);
        $this->assertNotEquals($id, $response['id']);
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
        $this->createEsIndex();

        $payout = $this->testCreatePayout();

        $payout = $this->testCreatePayout();

        $this->ba->privateAuth();

        $payouts = $this->startTest();

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayoutsWithoutAccountNumber()
    {
        $this->testCreatePayout();

        $this->testCreatePayout();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetPayout()
    {
        $this->testCreatePayout();

        $payout = $this->getLastEntity('payout', true);

        $this->ba->privateAuth();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/'. $payout['id'];

        $payout2 = $this->startTest();

        $this->assertArraySelectiveEquals($payout2, $payout);
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
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);

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

    public function setPaymentPayoutUrl($payment, & $request)
    {
        $request['url'] = '/payments/'. $payment->getPublicId() . '/payouts';
    }

    public function testCreatePayoutAttemptSuccess()
    {
        // FTA initiate happens via sync queue

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

            $this->assertNotNull($attempt['utr']);

            $this->assertNotNull($attempt['batch_fund_transfer_id']);
        }

        // Verify payouts
        $payouts = $this->getEntities('payout', [], true);

        $this->assertEquals(2, $payouts['count']);

        $payouts = $payouts['items'];

        foreach ($payouts as $payout)
        {
            $this->assertTestResponse($payout, 'testPayoutEntitySuccess');

            $this->assertNotNull($payout['batch_fund_transfer_id']);

            $this->assertNotNull($payout['utr']);

            $this->assertNotNull($payout['processed_at']);
        }

        Carbon::setTestNow();
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

    public function testSearchPayoutByTransactionId()
    {
        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts?transaction_id=' . $payout['transaction_id'] . '&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByPayoutStatus()
    {
        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => 'processed'
            ]);

        $request['url'] = '/payouts?status=processed&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }


    public function testSearchPayoutByPayoutContactType()
    {
        $contact = $this->fixtures->create('contact', [
            'id' => '1000005contact', 'email' => 'test@test5.com',
            'contact' => '8888888888', 'name' => 'test user',
            'type' => 'customer'
        ]);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_type=customer&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByUtr()
    {
        $payout = $this->testCreatePayout();

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'utr' => '1234567890'
            ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?utr=1234567890&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactId()
    {
        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000010contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_id=cont_1000010contact&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactName()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888', 'name' => 'test']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_name=test&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactPhone()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);


        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_phone=8888888888&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactEmail()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);


        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_email=test@payout.com&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByFundAccountId()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);


        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?fund_account_id=' . $payout['fund_account_id'] . '&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function createEsIndex()
    {
        $esMock = Config::get('database.es_mock');

        if ($esMock === false)
        {
            Artisan::call(
                'rzp:index_create',
                [
                    'mode'         => 'test',
                    'entity'       => 'payout',
                    'index_prefix' => env('ES_ENTITY_INDEX_PREFIX'),
                    'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                    '--reindex'    => true,
                ]);

            Artisan::call(
                'rzp:index_create',
                [
                    'mode'         => 'live',
                    'entity'       => 'payout',
                    'index_prefix' => env('ES_ENTITY_INDEX_PREFIX'),
                    'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                    '--reindex'    => true,
                ]);

            Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'payout']);
            Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'payout']);
        }
    }

    protected function makePayoutQueueSummaryRequest()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payouts/queued/amount',
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayouts()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process',
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }
}
