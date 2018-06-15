<?php

namespace RZP\Tests\Functional\Refund;

use DB;
use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * Tests for refund payments
 *
 * For refund payments, first we need to create a
 * captured payment. By default, an captured payment entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So refund tests which supposedly hit hdfc gateway for refund,
 * should first call for a normal hdfc authorized + captured payment
 * instead of utilizing the default created payment entity.
 */

class RefundTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->privateAuth();
    }

    public function testRefund()
    {
        Mail::fake();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest();

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testRefundEditStatus()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);

        $this->fixtures->base->editEntity('refund', $refund['id'], ['gateway_refunded' => false, 'status' => 'created']);

        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('abcd',$refund['reference1']);
        $this->assertEquals('initiated', $refund['status']);
    }

    public function testRefundEditInvalidStatus()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);

        $this->fixtures->base->editEntity('refund', $refund['id'], ['gateway_refunded' => false, 'status' => 'created']);

        $refund = $this->getLastEntity('refund', true);
        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundEditStatustoFailedFromInitiated()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);

        $this->fixtures->base->editEntity('refund', $refund['id'], ['gateway_refunded' => false, 'status' => 'initiated']);

        $refund = $this->getLastEntity('refund', true);
        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('abcdFailed',$refund['reference1']);
        $this->assertEquals('failed', $refund['status']);
    }

    public function testRefundEditStatustoInitiatedFromFailed()
    {
         $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);

        $this->fixtures->base->editEntity('refund', $refund['id'], ['gateway_refunded' => false, 'status' => 'failed']);

        $refund = $this->getLastEntity('refund', true);
        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('abcdInitiated',$refund['reference1']);
        $this->assertEquals('initiated', $refund['status']);
    }

    public function testRefundEditStatusFailtoProcessedFromFailed()
    {
         $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);
        $this->fixtures->base->editEntity('refund', $refund['id'], ['gateway_refunded' => false, 'status' => 'failed']);

        $refund = $this->getLastEntity('refund', true);
        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
    }

    public function testRefundEditStatusFailed()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);

        $refund = $this->getLastEntity('refund', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
    }

    public function testRefundDisputedPayment()
    {
        $dispute = $this->fixtures->create('dispute');

        $this->startTest(
            $dispute->payment->getPublicId(),
            (string) $dispute->payment->getAmount()
        );
    }

    public function testRefundDirectFraudDisputedPayment()
    {
        $dispute = $this->fixtures->create('dispute', ['phase' => 'fraud']);

        $paymentId = $dispute->payment->getPublicId();

        $refund = $this->refund(
            [
                'payment_id' => $paymentId,
            ]);

        $this->assertEquals('refund', $refund['entity']);
        $this->assertEquals($paymentId, $refund['payment_id']);
        $this->assertEquals(1000000, $refund['amount']);
    }

    public function testRefundDirectPaymentMultipleDisputesFraudOpen()
    {
        $dispute = $this->fixtures->create('dispute', ['phase' => 'fraud']);

        $paymentId = $dispute->payment->getPublicId();

        $this->fixtures->create('dispute', ['payment_id' => Payment::stripDefaultSign($paymentId), 'status' => 'lost']);

        $refund = $this->refund(
            [
                'payment_id' => $paymentId,
            ]);

        $this->assertEquals('refund', $refund['entity']);
        $this->assertEquals($paymentId, $refund['payment_id']);
        $this->assertEquals(1000000, $refund['amount']);
    }

    public function testRefundDirectPaymentMultipleDisputesNonFraudOpen()
    {
        $dispute = $this->fixtures->create('dispute', ['phase' => 'fraud', 'status' => 'lost']);

        $paymentId = $dispute->payment->getPublicId();

        $this->fixtures->create('dispute', ['payment_id' => Payment::stripDefaultSign($paymentId)]);

        $this->startTest($paymentId);
    }

    public function testRefundWithReceipt()
    {
        Mail::fake();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest();

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testRefundDirect()
    {
        $payment = $this->fixtures->create('payment:captured');

        $refund = $this->refund(
            [
                'payment_id' => $payment->getPublicId(),
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);

        $this->assertEquals('refund', $refund['entity']);
    }

    public function testMultipleRefunds()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest(4);

        $this->refundPayment($payment['id'], '10000');
        $this->refundPayment($payment['id'], '20000');
        $this->refundPayment($payment['id'], '12000');
        $this->refundPayment($payment['id'], '8000');

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$payment['id'];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refunds = $this->getEntities('refund', ['payment_id' => $payment['id']]);
        $this->assertEquals($refunds['count'], 4);
    }

    public function testRefundsWithDuplicateReceipt()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'amount'     => '1000',
                'receipt'    => '2544325',
            ]);

        $this->expectException('Illuminate\Database\QueryException');

        $response =  $this->refund(
                    [
                        'payment_id' => $payment['id'],
                        'notes'      => ['a' => 'b'],
                        'amount'     => '1000',
                        'receipt'    => '2544325',
                    ]);
    }

    public function testRefundWithHigherAmount()
    {
        $this->startTest($this->payment['public_id'], 1000001);
    }

    public function testMultipleRefundsWithHigherAmount()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id'], 20000);

        $this->startTest($payment['id'], 30000);
    }

    public function testRefundOnRefundedPayment()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($payment['id']);

        $this->startTest($payment['id'], 100);
    }

    public function testRefundByMerchantOnAuthorizedPayment()
    {
        $payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->startTest($payment['id']);
    }

    public function testRefundWithNegativeAmount()
    {
        $this->startTest($this->payment['public_id'], -1);
    }

    public function testRefundWithZeroAmount()
    {
        $this->startTest($this->payment['public_id'], 0);
    }

    public function testRefundWithBlankAmount()
    {
        $this->startTest($this->payment['public_id'], '');
    }

    public function testRefundWithSpacedAmount()
    {
        $this->startTest($this->payment['public_id'], ' 100');
    }

    public function testRefundWithFloatAmountString()
    {
        $this->startTest($this->payment['public_id'], '100.1');
    }

    public function testRefundWithFloatAmount()
    {
        $this->startTest($this->payment['public_id'], 100.1);
    }

    public function testRefundOfOldAuthorizedPayments()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $payments = $this->fixtures->times(2)->create('payment:authorized');

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(2, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(2, $content['authorized']);
    }

    public function testRefundOfOldAuthorizedEmandatePayments()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(25)->timestamp;

        $oldPayment = $this->fixtures->create(
            'payment:authorized',
            ['method' => 'emandate',
             'created_at' => $createdAt]);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);
    }

    public function testRefundOldAuthorizedEmandatePayments2()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $this->fixtures->create('payment:authorized', ['method' => 'emandate', 'created_at' => $createdAt]);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(0, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);
    }

    public function testRefundOfOldAuthorizedPaymentsContainingDisputed()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $this->fixtures->times(2)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $this->fixtures->create(
            'payment:authorized',
            ['created_at' => $createdAt,
             'disputed'   => 1]);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(2, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(2, $content['authorized']);
    }

    /**
     * Tests if all the authorized payments of only paid order are getting
     * refunded via CRON.
     *
     */
    public function testRefundAuthorizedPaymentsOfPaidOrders()
    {
        $this->ba->appAuth();

        //
        // Order 1: - Created, Partial payment allowed
        //          - 2 Authorized payment exist, 1 Failed payment
        //          - Payments NOT PICKED for refund
        //
        // Order 2: - Created
        //          - 1 Authorized payment exist
        //          - Payment NOT PICKED for refund
        //
        // Order 3: - Paid, Partial payment allowed
        //          - 2 Captured payment exist
        //          - Payments NOT PICKED for refund
        //
        // Order 4: - Paid
        //          - 1 Captured payment exist
        //          - Payment NOT PICKED for refund
        //
        // Order 5: - Paid, Partial payment allowed
        //          - 2 Captured and 3 Authorized payments exist
        //          - 3 Payments PICKED for refund
        //
        // Order 6: - Paid
        //          - 1 Captured and 1 Authorized payment exist, 2 Failed payments
        //          - 1 Payment PICKED for refund
        //
        // Order 7: - Attempted, Partial payment allowed
        //          - 2 Captured and 2 Authorized payment exists
        //          - Payments NOT PICKED for refund
        //
        // Order 8: - Paid
        //          - 1 Captured and 1 Authorized payment exists, 1 Disputed payment
        //          - 1 Payment PICKED for refund
        //

        $order1 = $this->fixtures->order->create(['partial_payment' => true]);

        $this->fixtures->times(2)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order1->getId(),
                                        'amount'   => '500000',
                                    ]);

        $this->fixtures->times(1)->create(
                                    'payment:failed',
                                    [
                                        'order_id' => $order1->getId(),
                                        'amount'   => '500000',
                                        'card_id'  => null,
                                    ]);

        $order2 = $this->fixtures->order->create();

        $this->fixtures->times(1)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order2->getId(),
                                        'amount'   => '1000000',
                                    ]);

        $order3 = $this->fixtures->order->createPaid(['partial_payment' => true]);

        $this->fixtures->times(2)->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order3->getId(),
                                        'amount'   => '500000',
                                    ]);

        $order4 = $this->fixtures->order->createPaid();

        $this->fixtures->times(1)->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order4->getId(),
                                        'amount'   => '1000000',
                                    ]);

        $order5 = $this->fixtures->order->createPaid(['partial_payment' => true]);

        $this->fixtures->times(2)->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order5->getId(),
                                        'amount'   => '500000',
                                    ]);

        $this->fixtures->times(3)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order5->getId(),
                                        'amount'   => '500000',
                                    ]);

        $order6 = $this->fixtures->order->createPaid();

        $this->fixtures->times(1)->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order6->getId(),
                                        'amount'   => '1000000',
                                    ]);

        $this->fixtures->times(1)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order6->getId(),
                                        'amount'   => '1000000',
                                    ]);

        $this->fixtures->times(2)->create(
                                    'payment:failed',
                                    [
                                        'order_id' => $order1->getId(),
                                        'amount'   => '500000',
                                        'card_id'  => null,
                                    ]);

        $order7 = $this->fixtures->order->create(
                                            [
                                                'status'          => 'attempted',
                                                'partial_payment' => true,
                                            ]);

        $this->fixtures->times(2)->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order7->getId(),
                                        'amount'   => '250000',
                                    ]);

        $this->fixtures->times(2)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order7->getId(),
                                        'amount'   => '250000',
                                    ]);

        $order8 = $this->fixtures->order->createPaid();

        $this->fixtures->times(1)->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order8->getId(),
                                        'amount'   => '1000000',
                                    ]);

        $this->fixtures->times(1)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order8->getId(),
                                        'amount'   => '1000000',
                                    ]);

        $this->fixtures->times(1)->create(
                                    'payment:authorized',
                                    [
                                        'order_id' => $order8->getId(),
                                        'amount'   => '1000000',
                                        'disputed' => 1,
                                    ]);

        // Run test

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);

        // Assert payment counts by status

        $payments = $this->getEntities('payment', [], true);

        $authorizedCount = $capturedCount = $failedCount = $refundedCount = $disputedCount = 0;

        foreach ($payments['items'] as $payment)
        {
            if ($payment['disputed'] === true)
            {
                $disputedCount++;
            }

            $holder = $payment['status'] . 'Count';

            $$holder++;
        }

        $this->assertEquals(6, $authorizedCount);
        $this->assertEquals(10, $capturedCount);
        $this->assertEquals(3, $failedCount);
        $this->assertEquals(5, $refundedCount);
        $this->assertEquals(1, $disputedCount);
    }

    public function testRefundCreateOnGatewayForMissingRefunds()
    {
        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);
    }

    public function testRefundPaymentsWithRefundDelay()
    {
        // Change auto refund delay to 2 days
        $this->fixtures->merchant->editAutoRefundDelay('2 days');

        $createdAt = Carbon::today(Timezone::IST)->subDays(2)->timestamp;

        $payments = $this->fixtures->times(3)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;
        $this->fixtures->on('test')->create('balance', ['id' => '1MercShareTerm', 'balance' => '1000000']);

        $payment = $this->fixtures->create(
            'payment:authorized',
            ['created_at' => $createdAt, 'merchant_id' => '1MercShareTerm', 'transaction_id' => null]);

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(4, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(4, $content['authorized']);
    }

    public function testRefundCalledOnPurchaseWithoutCapture()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:purchased',
            ['created_at' => $createdAt]);

        $payments = $this->fixtures->times(2)->create('payment:purchased');

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(2, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(2, $content['authorized']);

        $refundedEntities = $this->getEntities('hdfc', ['count' => 2], true);

        foreach ($refundedEntities['items'] as $entity)
        {
            $this->assertEquals('refunded', $entity['status']);
        }

    }

    // Testing Buggy Case where a payment is captured in hdfc gateway
    // But is in authorised state in RZP db.
    // This will also be picked up for a refund and refunded.
    public function testRefundOnHdfcCapturedPaymentAuthorized()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;
        $authorizedAt = Carbon::today(Timezone::IST)->timestamp;

        $payment = $this->fixtures->create(
            'payment:captured',
            ['authorized_at' => $authorizedAt, 'created_at' => $createdAt]);

        $this->fixtures->payment->edit($payment->getId(), ['status' => 'authorized']);

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);

        $hdfcRefundedEntity = $this->getLastEntity('hdfc', true);

        $this->assertEquals('refunded', $hdfcRefundedEntity['status']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertNotNull($refund['transaction_id']);
    }

    public function testVerifyRefund()
    {
        // Case 1
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund2 = $this->refundPayment($payment['id'], $payment['amount']);

        $refund2 = $this->getLastEntity('refund', true);

        $response = $this->verifyRefund($refund2['id']);

        $this->assertEquals('Refund verified successfully.', $response[0]['verify_refund']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertArraySelectiveEquals($refund2, $refund);
    }

    public function testVerifyBuggyRefund()
    {
        // Case where refunded payment has no entry in hdfc

        $authorizedAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp;

        $payment = $this->fixtures->create(
            'payment:purchased',
            [
                'authorized_at' => $authorizedAt,
                'created_at' => $authorizedAt
            ]);

        $content = $this->refundOldAuthorizedPayments();

        $refund = $this->getDbLastEntityPublic('refund');

        $hdfcEntityForRefund = $this->getDbLastEntityPublic('hdfc');

        $refundTransaction = $this->getDbLastEntityPublic('transaction');

        // Disable foreign key checks to allow testing buggy case
        DB::statement("SET foreign_key_checks = 0");

        $this->fixtures->hdfc->edit($hdfcEntityForRefund['id'], ['payment_id' => 'random_id', 'refund_id' => 'random_id']);

        // Enable foreign key checks
        DB::statement("SET foreign_key_checks = 1");

        $this->fixtures->refund->edit($refund['id'], ['transaction_id' => null, 'gateway_refunded' => false]);
        $this->fixtures->transaction->edit($refundTransaction['id'], ['entity_id' => 'boohooboohooaa']);

        $response = $this->verifyRefund($refund['id']);

        $hdfcEntityForRefund = $this->getDbLastEntityPublic('hdfc');
        $refund = $this->getDbLastEntityPublic('refund');
        $refundTransaction = $this->getDbLastEntityPublic('transaction');

        $this->assertEquals($refund['id'], $refundTransaction['entity_id']);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('txn_' . $refund['transaction_id'], $refundTransaction['id']);

        $this->assertEquals('Refund verification failed and Refund performed.', $response[0]['verify_refund']);

        $this->assertEquals('refunded', $hdfcEntityForRefund['status']);

        $this->assertEquals($payment['id'], $hdfcEntityForRefund['payment_id']);

        $this->assertEquals($refund['id'], 'rfnd_' . $hdfcEntityForRefund['refund_id']);
    }

    public function testCreateMissingRefundTransaction()
    {
        $this->markTestSkipped('Transactions are getting created now');

        $authorizedAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp;

        $payment = $this->fixtures->create(
            'payment:purchased',
            [
                'authorized_at' => $authorizedAt,
                'created_at' => $authorizedAt
            ]);

        $content = $this->refundOldAuthorizedPayments();

        $refund = $this->getLastEntity('refund', true);

        $hdfcEntityForRefund = $this->getLastEntity('hdfc', true);

        $this->assertEquals($refund['id'], 'rfnd_' . $hdfcEntityForRefund['refund_id']);

        $refundTransaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(true, $refund['gateway_refunded']);

        $this->fixtures->refund->edit($refund['id'], ['transaction_id' => null]);
        $this->fixtures->transaction->edit($refundTransaction['id'], ['entity_id' => 'boohooboohooaa']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);

        $refund = $this->getLastEntity('refund', true);

        $refundTransaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(true, $refund['gateway_refunded']);

        $this->assertNotNull($refundTransaction['id'], $refund['transaction_id']);

        $this->assertEquals($refund['id'], $refundTransaction['entity_id']);
    }

    public function testFetchRefundById()
    {
        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $actual = $rfnd->toArrayPublic();
        $actual['acquirer_data'] = $actual['acquirer_data']->toArray();

        $refund = $this->getEntityById('refund', $rfnd['public_id']);
        $this->assertArraySelectiveEquals($actual, $refund);

        $refunds = $this->getEntities('refund');
        $rfnds = ['entity' => 'collection', 'count' => 1, 'items' => [$actual]];
        $this->assertArraySelectiveEquals($rfnds, $refunds);
    }

    public function testFetchRefunds()
    {
        $this->ba->privateAuth();
        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $paymentId = $payment['public_id'];

        $content = $this->fetchRefundsForPayment($paymentId);
        $testData = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'entity' => 'refund',
                    'currency' => 'INR',
                ]
            ]
        ];
    }

    public function testFetchRefundsAdminAuth()
    {
        $this->ba->privateAuth();
        $payment1 = $this->fixtures->create('payment:captured', ['gateway' => 'cybersource']);
        $rfnd1 = $this->fixtures->create('refund:from_payment', ['payment' => $payment1]);
        $payment2 = $this->fixtures->create('payment:captured', ['gateway' => 'hdfc']);
        $rfnd2 = $this->fixtures->create('refund:from_payment', ['payment' => $payment2]);

        $refunds  = $this->getEntities(
                        'refund',
                        [
                            'gateway'     => $payment1->getGateway(),
                            'amount'      => $rfnd1->getAmount(),
                            'method'      => 'card',
                        ],
                        true);

        $this->assertEquals(1, $refunds['count']);

        $this->assertEquals($rfnd1->getPublicId(), $refunds['items'][0]['id']);
    }

    public function testRefundValidationOnWrongGateway()
    {
        $this->ba->appAuth();

        parent::startTest();
    }

    public function testRefundIciciDebitCard()
    {
        Mail::fake();

        $payment = $this->defaultAuthPayment();

        $payment['card']['number'] = '6074667022059103';

        $this->fixtures->create('iin',
            [
                'iin'    => '607466',
                'issuer' => 'ICIC',
                'type'   => 'debit',
            ]);

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest();

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertEquals('refund',$txn['type']);

        $this->assertEquals($refund['id'], $txn['entity_id']);

        Mail::assertQueued(RefundedMail::class);
    }

    public function startTest($paymentId = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $paymentId, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        if ($amount !== null)
        {
            $request['content']['amount'] = $amount;
        }

        $url = '/payments/'.$id.'/refund';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function mockDashboardRequest($times = 1)
    {
        $config = $this->config->get('applications.dashboard');

        if ($config['pretend'] === false)
        {
            return;
        }

        $dashboard = Mockery::mock('RZP\Dashboard\DashboardServiceProvider');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times($times)
              ->with('refund', Mockery::type('RZP\Models\\Base\\PublicEntity'));
    }

    protected function mockRefundEmail($times = 1)
    {
        \Mail::shouldReceive('queue')
            ->twice()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data)
                    {
                        $testData = [
                            'payment' => [
                                'amount' => 'INR 500.00'
                            ],
                            'merchant' => [],
                            'customer' => [
                                'email' => 'a@b.com',
                                'phone' => '9918899029'
                            ],
                            'refund'  => [
                                'amount' => 'INR 500.00'
                            ]
                        ];

                        $this->assertArraySelectiveEquals($testData, $data);

                        return true;
                }),
                Mockery::any());
    }
}
