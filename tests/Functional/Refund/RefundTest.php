<?php

namespace RZP\Tests\Functional\Refund;

use DB;
use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
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

    protected $sharedTerminal;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->ba->privateAuth();
    }

    public function testRefund()
    {
        Mail::fake();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testRefundWhenDisabledOnMerchant()
    {
        $this->fixtures->merchant->addFeatures('disable_refunds');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testRefundWhenDisabledOnMerchantForCards()
    {
        $this->fixtures->merchant->addFeatures('disable_card_refunds');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testNetBankRefundWhenDisabledOnMerchantForCards()
    {
        $this->fixtures->merchant->addFeatures('disable_card_refunds');

        $payment = $this->getDefaultNetbankingPaymentArray('CORP');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testVoidRefundFeatureDeactivated()
    {
        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testFailedVoidRefundGatewayReversalAbsent()
    {
        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testSuccessfulRefundOnCapturedPaymentWithVoidRefund()
    {
        $this->fixtures->merchant->addFeatures('void_refunds');

        // With gateway that doesn't support reversal
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testSuccessfulPartialRefundOnCapturedPaymentWithVoidRefund()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
            ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = [
            'number'       => CardNumber::VALID_ENROLL_NUMBER,
            'expiry_month' => '02',
            'expiry_year'  => '21',
            'cvv'          => 123,
            'name'         => 'Test Card'
        ];

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) ($payment['amount'] / 2));
    }

    public function testSuccessfulVoidRefund()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
            ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testFailVoidPartialRefund()
    {
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
            ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) ($payment['amount']/2));
    }

    public function testRefundEditStatus()
    {
        $this->markTestSkipped('HDFC on scrooge - only created to processed edit status supported');

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

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('abcd', $refund['reference1']);
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

    public function testRefundEditStatusToFailedFromInitiated()
    {
        $this->markTestSkipped('HDFC on scrooge - only created to processed edit status supported');

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

        $this->assertEquals('abcdFailed', $refund['reference1']);
        $this->assertEquals('failed', $refund['status']);
    }

    public function testRefundEditStatustoInitiatedFromFailed()
    {
        $this->markTestSkipped('HDFC on scrooge - only created to processed edit status supported');

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

        $this->assertEquals('abcdInitiated', $refund['reference1']);
        $this->assertEquals('initiated', $refund['status']);
    }

    public function testRefundEditStatusToProcessedFromFailed()
    {
        $this->markTestSkipped('HDFC on scrooge - only created to processed edit status supported');

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
            ]);
        $this->fixtures->base->editEntity('refund', $refund['id'], ['gateway_refunded' => false, 'status' => 'failed',
            'error_code' => 'test', 'error_description' => 'test', 'internal_error_code' => 'test']);

        $refund = $this->getLastEntity('refund', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'] . '/status';

        $this->ba->adminAuth('test');
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('abcdProcessed', $refund['reference1']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertNull($refund['error_code']);
        $this->assertNull($refund['error_description']);
        $this->assertNull($refund['internal_error_code']);
    }

    public function testRefundEditStatusWithoutReference()
    {
        $this->markTestSkipped('HDFC on scrooge - only created to processed edit status supported');

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

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(null, $refund['reference1']);
        $this->assertEquals('initiated', $refund['status']);
    }

    public function testRefundEditStatusFailed()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

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

        $this->assertEquals('created', $refund['status']);
    }

    public function testRefundFetchDetailsForCustomerFromRefundIdAndPaymentId()
    {
        $this->fixtures->merchant->addFeatures(['expose_arn_refund']);

        $this->gateway = 'hdfc';

        $payment = $this->fixtures->create(
                                    'payment:captured',
                                    [
                                        'amount'   => 50000,
                                    ]);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund1 = $this->refundPayment('pay_' . $payment['id'], $payment['amount']/2);
        $refund2 = $this->refundPayment('pay_' . $payment['id'], $payment['amount']/2);

        $refund2 = $this->getDbLastEntity('refund');

        $this->fixtures->edit('refund', $refund2->getId(), [
            'reference1' => 'random_arn'
        ]);

        $this->testData[__FUNCTION__]['request']['content']['refund_id'] = $refund1['id'];

        $this->ba->directAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertEquals($refund1['id'], $response['refunds'][0]['id']);
        $this->assertEquals($refund1['payment_id'], $response['refunds'][0]['payment_id']);
        $this->assertEquals('processed', $response['refunds'][0]['status']);
        $this->assertEquals('Test Merchant', $response['refunds'][0]['merchant_name']);

        $this->assertEquals($refund2->getPublicId(), $response['refunds'][1]['id']);
        $this->assertEquals('pay_' . $refund2->getPaymentId(), $response['refunds'][1]['payment_id']);

        $this->assertEquals($refund1['acquirer_data']['arn'], $response['refunds'][0]['acquirer_data']['arn']);

        $this->fixtures->edit('refund', $refund2->getId(), [
            'status' => 'failed'
        ]);

        $this->testData[__FUNCTION__]['request']['content']['payment_id'] = 'pay_' . $payment['id'];
        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertEquals($refund1['id'], $response['refunds'][0]['id']);
        $this->assertEquals($refund1['payment_id'], $response['refunds'][0]['payment_id']);

        $this->assertEquals($refund2->getPublicId(), $response['refunds'][1]['id']);
        $this->assertEquals('pay_' . $refund2->getPaymentId(), $response['refunds'][1]['payment_id']);
        $this->assertEquals('initiated', $response['refunds'][1]['status']);

        // just resetting
        $this->ba->adminAuth('test');
    }

    public function testRefundFetchDetailsForCustomerFromReservationId()
    {
        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures(['expose_arn_refund', 'irctc_report']);

        $order = $this->fixtures->order->create(['receipt' => 'check123', 'authorized' => true]);

        $payment = $this->fixtures->create(
                                    'payment:captured',
                                    [
                                        'order_id' => $order->getId(),
                                        'amount'   => 50000,
                                    ]);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund1 = $this->refundPayment('pay_' . $payment['id'], $payment['amount']/2);
        $refund2 = $this->refundPayment('pay_' . $payment['id'], $payment['amount']/2);

        $refund2 = $this->getDbLastEntity('refund');

        $this->fixtures->edit('refund', $refund2->getId(), [
            'reference1' => 'random_arn'
        ]);

        $this->testData[__FUNCTION__]['request']['content']['reservation_id'] = $order->getReceipt();

        $this->ba->directAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertEquals($refund1['id'], $response['refunds'][0]['id']);
        $this->assertEquals($refund1['payment_id'], $response['refunds'][0]['payment_id']);
        $this->assertEquals('processed', $response['refunds'][0]['status']);
        $this->assertEquals('Test Merchant', $response['refunds'][0]['merchant_name']);

        $this->assertEquals($refund2->getPublicId(), $response['refunds'][1]['id']);
        $this->assertEquals('pay_' . $refund2->getPaymentId(), $response['refunds'][1]['payment_id']);

        $this->assertEquals($refund1['acquirer_data']['arn'], $response['refunds'][0]['acquirer_data']['arn']);
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

        $this->gateway = 'hdfc';

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

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
        $this->fixtures->on('test')->create('balance', ['id' => '1MercShareTerm', 'balance' => '1000000', 'merchant_id' => '1MercShareTerm']);

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

        $payments = $this->fixtures->times(1)->create(
            'payment:purchased',
            ['created_at' => $createdAt]);

        $payments = $this->fixtures->times(1)->create('payment:purchased');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $content = $this->refundOldAuthorizedPayments();

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);

        $refundedEntities = $this->getEntities('hdfc', ['count' => 1], true);

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

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

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
        $this->markTestSkipped('HDFC on scrooge - verify Refund is called before first refund call -
        so verify refund related transaction is already created');

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
        $this->markTestSkipped('Failing occasionally - to be fixed');
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

        $this->fixtures->hdfc->edit($hdfcEntityForRefund['id'],
            ['payment_id' => 'random_id', 'refund_id' => 'random_id']);

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
        $this->fixtures->merchant->addFeatures(['expose_arn_refund']);
        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $actual = $rfnd->toArrayPublic();
        $actual['acquirer_data'] = $rfnd->getAcquirerData()->toArray();

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

    public function testCreateRefundProxyAuth()
    {
        $payment = $this->fixtures->create('payment:captured');
        $user = $this->fixtures->user->createUserForMerchant('10000000000000');
        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'operations');
        $this->startTest($payment->getPublicId(), $payment->getAmount());

        $payment = $this->getLastEntity('payment', true);
        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($payment['amount_refunded'], 1000000);
        $this->assertEquals($payment['amount'], $refund['amount']);
    }

    public function testCreateRefundProxyAuthInvalidRole()
    {
        $payment = $this->fixtures->create('payment:captured');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'finance');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest($payment->getPublicId(), $payment->getAmount());
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

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertEquals('refund',$txn['type']);

        $this->assertEquals($refund['id'], $txn['entity_id']);
        $this->assertEquals($txn['balance_id'], '10000000000000');

        Mail::assertQueued(RefundedMail::class);
    }

    public function testTpvPaymentRefundNetbanking()
    {
        list($payment, $order) = $this->tpvPayment();

        $this->fixtures->merchant->addFeatures(['bank_transfer_refund']);

        $response = $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        // Atom has been on boarded to Scrooge,
        // Changing this since in scrooge flow it will remain in created until cron picks up FTA for processing
        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals($bankAccount['id'], 'ba_' . $refund['bank_account_id']);
        $this->assertEquals('test', $bankAccount['beneficiary_name']);
        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testTpvPaymentRefundNetbankingOld()
    {
        list($payment, $order) = $this->tpvPayment();

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->fixtures->merchant->addFeatures(['bank_transfer_refund']);

        $attr = [
            'payment' => $payment,
            'status' => 'failed',
            'error_code' => 'test',
            'error_description' => 'test',
            'internal_error_code' => 'test',
            'gateway_refunded' => false,
            'attempts' => 1,
        ];

        $refund = $this->fixtures->create('refund:from_payment', $attr);

        $refund  = $this->getLastEntity('refund', true);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        $this->assertEquals('initiated', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals($bankAccount['id'], 'ba_' . $refund['bank_account_id']);

        $this->assertEquals('refund', $bankAccount['type']);

        $this->assertNull($refund['error_code']);
        $this->assertNull($refund['error_description']);
        $this->assertNull($refund['internal_error_code']);
    }

    public function testTpvPaymentRefundFailedAttempt()
    {
        list($payment, $order) = $this->tpvPayment();

        $this->fixtures->merchant->addFeatures(['bank_transfer_refund']);

        $response = $this->refundPayment($payment['id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed']);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund  = $this->getLastEntity('refund', true);

         $this->assertEquals($payment['id'], $refund['payment_id']);

        $this->assertEquals('initiated', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals($bankAccount['id'], 'ba_' . $refund['bank_account_id']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function tpvPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->gateway = 'atom';

        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order =  $this->runRequestResponseFlow($data);

        $payment['order_id'] = $order['id'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['bank_txn'] = '99999999';
            $content['bank_name'] = 'SBIN';
        });

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('atom', true);

        $this->assertArraySelectiveEquals(
            $this->testData['tpvPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
                            $data['request']['content']['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);

        return [$payment, $order];
    }

    public function testFetchRefundReversal()
    {
        $this->ba->proxyAuth();

        parent::startTest();
    }

    // Direct settlement without refund
    public function testRefundSettledBy()
    {
        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');

        $this->ba->privateAuth();

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment = $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $refund = $this->startTest($payment['razorpay_payment_id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('Razorpay', $refund['settled_by']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($refund['id'], $transaction['entity_id']);

        $this->assertEquals(50000, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
    }

    // Direct settlement with refund
    public function testDirectSettlementRefundSettledBy()
    {
        $this->fixtures->create('terminal:direct_settlement_refund_hdfc_terminal');

        $this->ba->privateAuth();

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment = $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $refund = $this->startTest($payment['razorpay_payment_id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('hdfc', $refund['settled_by']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($refund['id'], $transaction['entity_id']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
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

    public function testRefundReversal()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3459);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_'.$reversal['entity_id'], $refund['id']);
        $this->assertNotNull($reversal['balance_id']);
    }

    public function testRefundEditNotes()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => [
                    'key' => 'value',
                ],
                'receipt'    => '2544325',
            ]);

        $refund = $this->getLastEntity('refund', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundOnHdfcPaymentCaptureTimedOut()
    {
        $this->defaultAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'capture')
            {
                $content['result']          = '!ERROR!-GW00177-Failed capture greater than auth check';
                $content['error_code_tag']  = 'GW00177';
            }

            return $content;
        });

        $this->makeRequestAndCatchException(function () use ($payment) {
            $payment = $this->capturePayment($payment['public_id'], $payment['amount']);
        });

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals($hdfc['status'], 'capture_failed');

        $this->assertEquals($hdfc['error_code2'], 'GW00177');

        $this->fixtures->edit('payment', $payment['id'], ['status' => 'captured', 'gateway_captured' => true]);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
            }

            return $content;
        });

        $this->refundPayment($payment['id'], $payment['amount']);

        $refund = $this->getLastEntity('refund', 'true');

        $this->assertEquals($refund['status'], 'processed');
    }

    public function testFetchRefundPublicStatus()
    {
        $this->fixtures->merchant->addFeatures('show_refund_public_status');

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundBalanceId()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertNotNull($refund['balance_id']);

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_'.$reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
    }

    public function testInstantRefundFailureCapturedPaymentReversal()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->gateway = 'hdfc';

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3459, ['speed' => 'normal', 'is_fta' => true]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('normal', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3459, $transaction['amount']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

//        $this->assertEquals('refund', $feesBreakup[0]['name']);
//        $this->assertEquals('tax', $feesBreakup[1]['name']);
//        $this->assertEquals(100, $feesBreakup[0]['amount']);
//        $this->assertEquals(18, $feesBreakup[1]['amount']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals('refund', $reversal['entity_type']);
        $this->assertEquals($refund['id'], 'rfnd_' . $reversal['entity_id']);
        $this->assertNotNull($reversal['balance_id']);
        $this->assertEquals(3459, $reversal['amount']);
        $this->assertEquals(0, $reversal['fee']);
        $this->assertEquals(0, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();

        $this->assertEquals(3459, $transaction['amount']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals($transaction['amount'] - ($transaction['fee']), $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

//        $this->assertEquals('refund', $feesBreakup[0]['name']);
//        $this->assertEquals('tax', $feesBreakup[1]['name']);
//        $this->assertEquals(0, $feesBreakup[0]['amount']);
//        $this->assertEquals(0, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['refund_status']);
        $this->assertEquals(0, $payment['amount_refunded']);
    }

    public function testOptimumRefundFeeReversal()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            if ($action === 'refund') {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3470, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(100, $feesBreakup[0]['amount']);
        $this->assertEquals(18, $feesBreakup[1]['amount']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals('refund', $reversal['entity_type']);
        $this->assertEquals($refund['id'], 'rfnd_' . $reversal['entity_id']);
        $this->assertNotNull($reversal['balance_id']);
        $this->assertEquals(0, $reversal['amount']);
        $this->assertEquals(118, $reversal['fee']);
        $this->assertEquals(18, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();

        $this->assertEquals(0, $transaction['amount']);
        $this->assertEquals(-118, $transaction['fee']);
        $this->assertEquals(-18, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals($transaction['amount'] - $transaction['fee'], $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(-100, $feesBreakup[0]['amount']);
        $this->assertEquals(-18, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3470, $payment['amount_refunded']);
    }

    public function testOptimumRefundCreditReversal()
    {
        // Need multiple credit logs to completely test credit reversals flow
        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 3470
            ]);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 100
            ]);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 100
            ]);

        $this->fixtures->merchant->editRefundCredits('3670', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            if ($action === 'refund') {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(3670, $balance['refund_credits']);

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3470, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['debit']);

        $creditTxns = $this->getDbEntitiesInOrder(
            'credit_transaction', 'id', ['transaction_id' => $transaction['id']], 'desc');

        $creditsUsed = 0;

        foreach ($creditTxns as $creditTxn)
        {
            $creditsUsed += $creditTxn['credits_used'];
        }

        $this->assertEquals(3588, $creditsUsed);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals('refund', $reversal['entity_type']);
        $this->assertEquals($refund['id'], 'rfnd_' . $reversal['entity_id']);
        $this->assertNotNull($reversal['balance_id']);
        $this->assertEquals(0, $reversal['amount']);
        $this->assertEquals(118, $reversal['fee']);
        $this->assertEquals(18, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();

        $this->assertEquals(0, $transaction['amount']);
        $this->assertEquals(-118, $transaction['fee']);
        $this->assertEquals(-18, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(-118, $transaction['fee_credits']);
        $this->assertEquals($transaction['fee'], $transaction['fee_credits']);

        $fee = 118;

        foreach ($creditTxns as $creditTxn)
        {
            if ($fee === 0) break;

            $lastTxn = $this->getDbEntities('credit_transaction', ['credits_id' => $creditTxn['credits_id']])->last();

            $reverseAmount = min($fee, $creditTxn['credits_used']);

            $this->assertEquals($lastTxn['credits_used'], -1 * ($reverseAmount));

            $fee -= $reverseAmount;
        }

        $credits = $this->getDbEntities('credits');

        $creditsUsed = 0;

        foreach ($credits as $credit)
        {
            $creditsUsed += $credit['used'];
        }

        $this->assertEquals(3470, $creditsUsed);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals( 200, $balance['refund_credits']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3470, $payment['amount_refunded']);
    }

    public function testInstantRefundSuccessful()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            if ($action === 'refund') {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(100, $feesBreakup[0]['amount']);
        $this->assertEquals(18, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(118, $refund['fee']);
        $this->assertEquals(18, $refund['tax']);
    }

    public function createUpiPayment()
    {
        $this->gateway = Gateway::UPI_MINDGATE;

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);

        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertNotNull($upiEntity['npci_reference_id']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);
        $this->assertNotNull($payment['acquirer_data']['upi_transaction_id']);

        $this->assertEquals($payment['reference16'], $upiEntity['npci_reference_id']);
        $this->assertNotNull($upiEntity['gateway_payment_id']);
        $this->assertEquals($payment['reference1'],$upiEntity['gateway_payment_id']);
        $this->assertSame('00', $upiEntity['status_code']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    public function testInstantRefundsOnUpiSuccessful()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment($upiPayment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true, 'fta_data' => ['vpa' => ['address' => $paymentEntity->getVpa()]]]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(100, $feesBreakup[0]['amount']);
        $this->assertEquals(18, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals($refund['vpa_id'], $fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(118, $refund['fee']);
        $this->assertEquals(18, $refund['tax']);
    }

    public function testOptimumRefundFeeReversalOnUpi()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($upiPayment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true, 'fta_data' => ['vpa' => ['address' => $paymentEntity->getVpa()]]]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3470, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(100, $feesBreakup[0]['amount']);
        $this->assertEquals(18, $feesBreakup[1]['amount']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals('refund', $reversal['entity_type']);
        $this->assertEquals($refund['id'], 'rfnd_' . $reversal['entity_id']);
        $this->assertNotNull($reversal['balance_id']);
        $this->assertEquals(0, $reversal['amount']);
        $this->assertEquals(118, $reversal['fee']);
        $this->assertEquals(18, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();

        $this->assertEquals(0, $transaction['amount']);
        $this->assertEquals(-118, $transaction['fee']);
        $this->assertEquals(-18, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals($transaction['amount'] - $transaction['fee'], $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(-100, $feesBreakup[0]['amount']);
        $this->assertEquals(-18, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3470, $payment['amount_refunded']);

        $this->mockServerContentFunction(function (& $content, $action = null) use($refund)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $refundId = substr($refund['id'], 5);

                $content[4] = 'SUCCESS';

                $this->assertEquals($refundId . 1, $content[1]);
            }
        });

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('normal', $refund['speed_processed']);
        $this->assertEquals(0, $refund['fee']);
        $this->assertEquals(0, $refund['tax']);
    }
}
