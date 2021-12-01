<?php

namespace RZP\Tests\Functional\Order\Transfers;

use Carbon\Carbon;
use RZP\Models\Transfer;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTransferTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/OrderTransferTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->linkedAccountId = $account['id'];
    }

    public function testCreateOrderTransfers()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderTransfersForCredits() {

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international'    => 0]);
        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => 0,
            'business_category'             => 'education',
            'business_subcategory'          => 'college']);

        $order = $this->startTest();

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals($order['id'], 'order_' . $transfer['source_id']);
        $this->assertEquals($transfer['status'], 'created');
        $this->assertEquals($transfer['amount'], $order['amount']);

        // Order transfers are automatically processed post payment capture
        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbEntityById('transfer', $transfer['id']);
        $this->assertEquals($transfer['status'], 'processed');
        $this->assertEquals($transfer['amount'], $payment['amount'] - $payment['fee']);

        $payment_transaction = $this->getDbEntity('transaction', ['entity_id' => substr($payment['id'], 4)]);
        $this->assertEquals($payment['amount'], $payment_transaction['amount']);

        $transfer_transaction = $this->getDbEntity('transaction', ['entity_id' => $transfer['id']]);
        $this->assertEquals($payment_transaction['credit'], $transfer_transaction['amount']);

        $credits = $this->getDbLastEntity('credits');
        $this->assertEquals($credits['value'], $transfer['amount']);
        $this->assertEquals($credits['type'], 'refund');

        // Merchant's refund credit balance is incremented by the transfer amount
        $balance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals($balance['refund_credits'], $transfer['amount']);
    }

    public function testCreateOrderTransfersForReserveBalance() {

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international'    => 0]);
        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => 0,
            'business_category'             => 'education',
            'business_subcategory'          => 'college']);

        $order = $this->startTest();

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals($order['id'], 'order_' . $transfer['source_id']);
        $this->assertEquals($transfer['status'], 'created');
        $this->assertEquals($transfer['amount'], $order['amount']);

        // Order transfers are automatically processed post payment capture
        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbEntityById('transfer', $transfer['id']);
        $this->assertEquals($transfer['status'], 'processed');
        $this->assertEquals($transfer['amount'], $payment['amount'] - $payment['fee']);

        $payment_transaction = $this->getDbEntity('transaction', ['entity_id' => substr($payment['id'], 4)]);
        $this->assertEquals($payment['amount'], $payment_transaction['amount']);

        $transfer_transaction = $this->getDbEntity('transaction', ['entity_id' => $transfer['id']]);
        $this->assertEquals($payment_transaction['credit'], $transfer_transaction['amount']);

        $adjustment = $this->getDbLastEntity('adjustment');

        $transfer_adjustment = $this->getDbEntity('transaction', ['entity_id' => $adjustment['id']]);
        $this->assertEquals($adjustment['amount'], $transfer_adjustment['amount']);

        // Merchant's refund credit balance is incremented by the transfer amount
        $balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'reserve_primary']);
        $this->assertEquals($balance['balance'], $transfer['amount']);
    }

    public function testCreateOrderTransfersForFeeCreditWhenAmountCreditExists() {

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international'    => 0]);
        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => 0,
            'business_category'             => 'education',
            'business_subcategory'          => 'college']);

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $order = $this->startTest();

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals($order['id'], 'order_' . $transfer['source_id']);
        $this->assertEquals($transfer['status'], 'created');
        $this->assertEquals($transfer['amount'], $order['amount']);

        // Order transfers are automatically processed post payment capture
        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbEntityById('transfer', $transfer['id']);
        $this->assertEquals($transfer['status'], 'failed');
    }

    public function testCreateOrderTransfersForInvalidAccount()
    {
        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international'    => 0]);
        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => 0,
            'business_category'             => 'education',
            'business_subcategory'          => 'college']);

        $request = $this->testData['testCreateOrderTransfersForCredits']['request'];
        $request['content']['transfers'][0]['account'] = 'acc_10000000000001';

        $this->makeRequestAndCatchException(
            function() use ($request) {
                $response = $this->makeRequestAndGetContent($request);
                $this->assertEquals($response['error']['internal_error_code'],
                    'BAD_REQUEST_ACCOUNT_ID_INVALID_FOR_BALANCE_TRANSFER');
            },
            BadRequestException::class,
            'Something went wrong, please try again after sometime.'
        );
    }

    public function testCreateOrderTransfersForInvalidAmount()
    {
        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international'    => 0]);
        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => 0,
            'business_category'             => 'education',
            'business_subcategory'          => 'college']);

        $request = $this->testData['testCreateOrderTransfersForCredits']['request'];
        $request['content']['transfers'][0]['amount'] = 500;

        $this->makeRequestAndCatchException(
            function() use ($request) {
                $response = $this->makeRequestAndGetContent($request);
                $this->assertEquals($response['error']['internal_error_code'],
                    'BAD_REQUEST_INVALID_TRANSFER_AMOUNT_FOR_BALANCE_TRANSFER');
            },
            BadRequestException::class,
            'Something went wrong, please try again after sometime.'
        );
    }

    public function testCreateOrderTransfersUsingAccountCode()
    {
        $this->fixtures->merchant->addFeatures('route_code_support');
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderTransfersUsingAccountCodeWhenFeatureDisabled()
    {
        $request = $this->testData['testCreateOrderTransfersUsingAccountCode']['request'];

        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            },
            BadRequestException::class,
            'account_code is not allowed for this merchant.'
        );
    }

    public function testCreateOrderTransfersUsingInvalidAccountCode()
    {
        $this->fixtures->merchant->addFeatures('route_code_support');
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $request = $this->testData['testCreateOrderTransfersUsingAccountCode']['request'];
        $request['content']['transfers'][0]['account_code'] = 'bro_code';

        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            },
            BadRequestException::class,
            'bro_code is an invalid account_code.'
        );
    }

    public function testCreateOrderTransfersInsufficientBalance()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('failed', $transfer['status']);

        $this->assertEquals(PublicErrorDescription::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE, $transfer['message']);
    }

    public function testProcessOrderTransfers()
    {
        $order = $this->testCreateOrderTransfers();

        $this->capturePaymentProcessOrderTransfers($order); // Order transfers are automatically processed post payment capture

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals($order['id'], $transfer['source']);

        $this->assertEquals('processed', $transfer['status']);

        Transfer\Entity::verifyIdAndSilentlyStripSign($transfer['id']);

        $payment = $this->getDbEntity('payment', ['transfer_id' => $transfer['id']]);

        $this->assertArraySelectiveEquals(['roll_no' => 'iec2011025'], $payment->getNotes()->toArray());
    }

    public function testProcessOrderTransfersWithAccountCode()
    {
        $order = $this->testCreateOrderTransfersUsingAccountCode();

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');
        $this->assertEquals($order['id'], 'order_' . $transfer['source_id']);
        $this->assertEquals('processed', $transfer['status']);
        $this->assertEquals('code-007', $transfer['account_code']);
    }

    public function testProcessOrderTransfersPartialPayment()
    {
        $this->startTest();
    }

    public function testGetOrderTransfers()
    {
        $order = $this->testCreateOrderTransfers();

        $this->capturePaymentProcessOrderTransfers($order);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/orders/' . $order['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);
    }

    public function testReverseOrderTransfer($order = null)
    {
        if ($order === null)
        {
            $order = $this->testCreateOrderTransfers();
        }

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/transfers/' . $transfer['id'] . '/reversals';

        $this->ba->privateAuth();

        $reversal = $this->runRequestResponseFlow($data);

        $this->assertEquals($transfer['id'], $reversal['transfer_id']);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('reversed', $transfer['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals(0, $payment['amount_transferred']);
    }

    public function testReverseOrderTransferWithFailedAndCapturedPayments()
    {
        $order = $this->testCreateOrderTransfers();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $response = $this->doAuthPayment($payment);

        $this->fixtures->payment->failPayment($response['razorpay_payment_id']);
        $this->fixtures->order->edit($order['id'], ['authorized' => 0]);

        $this->testReverseOrderTransfer($order);
    }

    public function testWebhookOrderTransferProcessed()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('transfer.processed', $expectedEvent);

        $this->testProcessOrderTransfers();
    }

    public function testCronProcessPendingOrderTransfers()
    {
        $this->markTestSkipped();

        $order = $this->testCreateOrderTransfers();

        // Disable dispatch here

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('pending', $transfer['status']);

        // Enable dispatch here

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $orderIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('processed', $transfer['status']);

        $this->assertEquals($order['id'], 'order_' . $orderIds[0]);
    }

    public function testCronProcessFailedOrderTransfers()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        $this->fixtures->merchant->editBalance(100000);

        $todayTimestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $this->fixtures->transfer->editProcessedAt($todayTimestamp - 10, $transfer['id']);

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $orderIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('processed', $transfer['status']);

        $this->assertEquals(2, $transfer['attempts']);

        $this->assertEquals($order['id'], 'order_' . $orderIds[0]);
    }

    public function testCronProcessFailedOrderTransfersSameDay()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        $this->fixtures->merchant->editBalance(100000);

        $todayTimestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $this->fixtures->transfer->editProcessedAt($todayTimestamp + 10, $transfer['id']);

        $data = $this->testData['testCronProcessFailedOrderTransfers'];

        $this->ba->cronAuth();

        $orderIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        $this->assertEquals(1, $transfer['attempts']);

        $this->assertEmpty($orderIds);
    }

    public function testTransferFailedWebhook()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->expectWebhookEventWithContents('transfer.failed', $this->testData[__FUNCTION__]);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        for ($i = 1; $i < 4; $i++)
        {
            $timestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

            $this->fixtures->transfer->editProcessedAt($timestamp - 10, $transfer['id']);

            $data = $this->testData['testCronProcessFailedOrderTransfers'];

            $this->ba->cronAuth();

            $this->runRequestResponseFlow($data);
        }

        $transfer->reload();

        $this->assertEquals(4, $transfer['attempts']);
    }

    public function testNoTransferFailedWebhookWhenRetriesLeft()
    {
        $attempts = 2;

        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->dontExpectWebhookEvent('transfer.failed');

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        for ($i = 1; $i < $attempts; $i++)
        {
            $timestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

            $this->fixtures->transfer->editProcessedAt($timestamp - 10, $transfer['id']);

            $data = $this->testData['testCronProcessFailedOrderTransfers'];

            $this->ba->cronAuth();

            $this->runRequestResponseFlow($data);
        }

        $transfer->reload();

        $this->assertEquals($attempts, $transfer['attempts']);
    }

    public function testAttemptsIncrementedWhenFailedOrderTransferRetriedWithPaymentRefunded()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        $this->fixtures->merchant->editBalance(100000);

        $this->refundPayment($payment['id']);

        $yesterdayTimestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $this->fixtures->transfer->editProcessedAt($yesterdayTimestamp, $transfer['id']);

        $data = $this->testData['testCronProcessFailedOrderTransfers'];

        $this->ba->cronAuth();

        $orderIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);

        $this->assertEquals(2, $transfer['attempts']);
    }

    public function testSettlementStatusForOrderTransfer()
    {
        $order = $this->testCreateOrderTransfers();

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('pending', $transfer['settlement_status']);
    }

    public function testSettlementStatusForOrderTransferWithOnHold()
    {
        $testData = $this->testData['testCreateOrderTransfers'];
        $testData['request']['content']['transfers'][0]['on_hold'] = true;
        $order = $this->runRequestResponseFlow($testData);

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('on_hold', $transfer['settlement_status']);
    }

    public function testSettlementStatusForOrderTransferWithOnHoldUntil()
    {
        $testData = $this->testData['testCreateOrderTransfers'];
        $testData['request']['content']['transfers'][0]['on_hold'] = true;
        $testData['request']['content']['transfers'][0]['on_hold_until'] = Carbon::tomorrow()->getTimestamp();
        $order = $this->runRequestResponseFlow($testData);

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('on_hold', $transfer['settlement_status']);
    }

    public function testErrorCodeForOrderTransferWithInsufficientBalance()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals('BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE', $transfer['error_code']);

        for ($i = 1; $i < 4; $i++)
        {
            $timestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

            $this->fixtures->transfer->editProcessedAt($timestamp - 10, $transfer['id']);

            $data = $this->testData['testCronProcessFailedOrderTransfers'];

            $this->ba->cronAuth();

            $this->runRequestResponseFlow($data);
        }

        $transfer->reload();

        $this->assertEquals(4, $transfer['attempts']);
        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals('BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE', $transfer['error_code']);
    }

    public function testErrorCodeForOrderTransferWithInsufficientBalanceAfterRetrySuccess()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals('BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE', $transfer['error_code']);

        $this->fixtures->merchant->editBalance(1000000);

        $timestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $this->fixtures->transfer->editProcessedAt($timestamp - 10, $transfer['id']);

        $data = $this->testData['testCronProcessFailedOrderTransfers'];

        $this->ba->cronAuth();

        $this->runRequestResponseFlow($data);

        $transfer->reload();

        $this->assertEquals('processed', $transfer['status']);
        $this->assertNull($transfer['error_code']);
    }

    protected function capturePaymentProcessOrderTransfers($order, $paymentAmount = null)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];

        if ($paymentAmount !== null)
        {
            $payment['amount'] = $paymentAmount;
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
