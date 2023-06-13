<?php

namespace RZP\Tests\Functional\Refund;

use DB;
use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settlement\Channel;
use RZP\Services\Scrooge;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Repository;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Payment\RefundRrnUpdated;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Refund\Constants;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\RazorxTrait;

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
    use CustomBrandingTrait;
    use RazorxTrait;

    protected $payment = null;

    protected $sharedTerminal;

    protected function setUp(): void
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

    public function testRefundTerminalDeleted()
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

        $paymentEntity = $this->getDbEntityById('payment', $payment['id']);

        // Disable foreign key checks to allow testing buggy case
        DB::statement("SET foreign_key_checks = 0");

        $this->fixtures->edit(
            'payment',
            $paymentEntity['id'],
            ['terminal_id' => 'B2K2t8JD9z98vh']);

        // Enable foreign key checks
        DB::statement("SET foreign_key_checks = 1");

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertNull($refund['gateway_refunded']);
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

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

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
            'expiry_year'  => '35',
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
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $payment = $this->getLastEntity('payment');

        $refund = $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testSuccessfulVoidRefundForMYR()
    {
        $this->fixtures->create('terminal:shared_eghl_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
        ]);


        $this->gateway = 'eghl';

        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('void_refunds');

        $this->fixtures->merchant->edit('10000000000000',[
            'country_code' => 'MY'
        ]);
        $payment = $this->defaultAuthPaymentForMY([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
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
                'expiry_year'  => '35',
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

        $this->ba->privateAuth();

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

        // Now handling this before build refund.
        $this->expectException('Illuminate\Database\QueryException');

        $response =  $this->refund(
                    [
                        'payment_id' => $payment['id'],
                        'notes'      => ['a' => 'b'],
                        'amount'     => '1000',
                        'receipt'    => '2544325',
                    ]);
    }

    public function testRefundsWithDuplicateReceiptRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                   ->setConstructorArgs([$this->app])
                   ->setMethods(['getTreatment'])
                   ->getMock();

        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                if ($feature === 'duplicate_receipt_check')
                                  {
                                    return 'on';
                                  }
                                  return 'off';
                              }));

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'amount'     => '1000',
                'receipt'    => '2544325',
            ]);

        // Now handling this before build refund.
        $this->expectException('RZP\Exception\BadRequestException');

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

        $this->ba->privateAuth();

        $this->startTest($payment['id'], 30000);
    }

    public function testRefundOnRefundedPayment()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id']);

        $this->ba->privateAuth();

        $this->startTest($payment['id'], 100);
    }

    public function testRefundOnMissingCapturedPaymentTransaction()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $paymentTransaction = $this->getLastEntity('transaction', ['entity_id' => substr($payment['id'], 4)]);

        $this->fixtures->payment->edit($payment['id'], ['transaction_id' => null]);
        $this->fixtures->transaction->edit($paymentTransaction['id'], ['entity_id' => 'boohooboohooaa']);

        // Now handling this before build refund.
        $this->expectException('RZP\Exception\LogicException');

        $response =  $this->refund(
            [
                'payment_id' => $payment['id'],
            ]);
    }

    public function testRefundByMerchantOnAuthorizedPayment()
    {
        $payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->startTest($payment['id']);
    }

    public function testRefundByMYMerchantOnAuthorizedPayment()
    {
        $payment = $this->defaultAuthPaymentForMY();

        $this->ba->privateAuth();

        $this->startTest($payment['id']);
    }

    public function testRefundByMerchantForPosPayment()
    {
        $payment = $this->fixtures->create('payment:card_authorized', [PaymentEntity::RECEIVER_TYPE => 'pos']);

        $this->startTest($payment['public_id'], 1000000);
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

    public function testRefundWithAmountLessThanINR1()
    {
        $refund = $this->refund(
            [
                'payment_id' => $this->payment['public_id'],
                'amount'     => $this->payment['amount'] - 50,
            ]);

        $this->assertNotNull($refund['id']);

        $this->ba->privateAuth();

        $this->startTest($this->payment['public_id']); // attempt remaining amount less than INR 1
    }

    public function testRefundOfOldAuthorizedPayments()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $refundAt = Carbon::now()->subMinute(5)->getTimestamp();

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
                    [
                        'created_at' => $createdAt,
                        'refund_at'  => $refundAt,
                    ]);

        $payments = $this->fixtures->times(2)->create('payment:authorized');

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        if ($flag === true)
        {
            $this->assertPassportKeyExists('consumer.id'); // just check for presence of passport
        }

        $this->assertNull($payments[0]->getRefundAt());
        $this->assertNull($payments[1]->getRefundAt());
        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(2, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(2, $content['authorized']);
    }

    public function testRefundOfOldAuthorizedEmandatePayments()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(35)->timestamp;

        $refundAt = Carbon::now()->subDays(1)->getTimestamp();

        $oldPayment = $this->fixtures->create(
            'payment:authorized',
            [
                'method'     => 'emandate',
                'created_at' => $createdAt,
                'refund_at'  => $refundAt
            ]);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);
    }

    public function testRefundOfOldAuthorizedEmandatePaymentsFlow()
    {
        // Setup for emandate payment.
        $this->fixtures->create('terminal:shared_emandate_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray('HDFC');

        $payment['bank_account'] = [
            'account_number'    => '0123456789',
            'ifsc'              => 'HDFC0000186',
            'name'              => 'Test Account',
            'account_type'      => 'savings',
        ];

        $this->gateway = 'netbanking_hdfc';

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity('payment');

        $this->assertSame('authorized', $payment->getStatus());

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        // Now start refunding old authorized payments
        // Setting it to null, as it should not go through scrooge.
        $this->gateway = null;
        $data = $this->refundOldAuthorizedPayments($flag);

        // Should not refund this payments as delay set
        $this->assertSame(0, $data['authorized']);
        $this->assertSame(0, $data['refunded']);

        // Change now after 30 days to actually refund this payment
        $testTime = Carbon::now()->addDays(30);
        Carbon::setTestNow($testTime);

        $data = $this->refundOldAuthorizedPayments($flag);

        // Should refund this payments as already 30 days completed
        $this->assertSame(1, $data['authorized']);
        $this->assertSame(1, $data['refunded']);
    }

    public function testRefundOfOldAuthorizedPaymentsWithOffset()
    {
        $now = Carbon::now();

        $this->fixtures->times(2)->create('payment:authorized',
            [
                'refund_at' => $now->getTimestamp()
            ]);

        $this->fixtures->times(2)->create('payment:authorized',
            [
                'refund_at' => $now->subDays(20)->getTimestamp()
            ]);

        // -15 days offset
        $offsetInSeconds = 1296000;

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $response =  $this->refundOldAuthorizedPayments($flag, $offsetInSeconds);

        $this->assertArraySubset([
            'refunded' => 2
        ], $response);
    }

    public function testRefundOldAuthorizedEmandatePayments2()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $refundAt = Carbon::now()->addDays(2)->timestamp;

        $this->fixtures->create('payment:authorized',
            [
                'method'     => 'emandate',
                'created_at' => $createdAt,
                'refund_at'  => $refundAt,
            ]);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(0, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(0, $content['authorized']);
    }

    public function testRefundOfOldAuthorizedPaymentsContainingDisputed()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $refundAt = Carbon::now()->subDays(2)->timestamp;

        $this->fixtures->times(2)->create(
            'payment:authorized',
            [
                'created_at' => $createdAt,
                'refund_at'  => $refundAt,
            ]);

        $this->fixtures->create(
            'payment:authorized',
            [
                'created_at' => $createdAt,
                'disputed'   => 1,
                'refund_at'  => $refundAt,
            ]);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

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
        $this->ba->cronAuth();

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

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->runRequestResponseFlow($testData);

        // Since, refunds are no longer created in API DB,
        // need to update payments explicitly.
        if ($flag === true)
        {
            $this->repo = (new Repository);
            $payments = $this->repo->getAuthorizedPaymentsOfPaidOrderForRefund();

            foreach ($payments as $payment) {
                $this->updatePaymentStatus($payment->getId(), [], true);
            }
        }

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

    public function testRefundPaymentsWithRefundDelay()
    {
        // Change auto refund delay to 2 days
        $this->fixtures->merchant->editAutoRefundDelay('2 days');

        $earlyDate = Carbon::now()->subDays(2);

        $now = Carbon::now();

        $createdAt = Carbon::today(Timezone::IST)->subDays(2)->timestamp;

        Carbon::setTestNow($earlyDate);

        $response = $this->doAuthPayment();

        $payment = $this->getDbLastPayment();

        Carbon::setTestNow($now);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        $this->assertSame(1, $content['refunded']);
    }

    public function testRefundPaymentsWithRefundDelayAutoRefundsDisabled()
    {
        //
        // Totally 4 authorized payments to be auto refunded
        // 3 belong to disable_auto_refunds merchant
        // 1 belongs to normal merchant
        //

        // Change auto refund delay to 2 days
        $this->fixtures->merchant->editAutoRefundDelay('2 days');

        $this->fixtures->merchant->addFeatures(['disable_auto_refunds']);

        $createdAt = Carbon::today(Timezone::IST)->subDays(2)->timestamp;

        $payments = $this->fixtures->times(3)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;
        $this->fixtures->on('test')->create('balance', ['id' => '1MercShareTerm', 'balance' => '1000000', 'merchant_id' => '1MercShareTerm']);

        $payment = $this->fixtures->create(
            'payment:authorized',
            [
                'transaction_id' => null,
                'merchant_id'    => '1MercShareTerm',
                'created_at'     => $createdAt,
                'refund_at'      => Carbon::today(Timezone::IST)->timestamp,
            ]
        );

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp;

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            ['created_at' => $createdAt]);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);
    }

    public function testRefundCalledOnPurchaseWithoutCapture()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(6)->timestamp;

        $refundAt = Carbon::today(Timezone::IST)->timestamp;

        $payments = $this->fixtures->times(1)->create(
            'payment:purchased',
            [
                'created_at' => $createdAt,
                'refund_at'  => $refundAt,
            ]);

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

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);

        // COME BACK TO THIS LATER
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
        $refundAt = Carbon::today(Timezone::IST)->timestamp;

        $payment = $this->fixtures->create(
            'payment:captured',
            [
                'authorized_at' => $authorizedAt,
                'created_at'    => $createdAt,
                'refund_at'     => $refundAt,
            ]);

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

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $content = $this->refundOldAuthorizedPayments($flag);

        $this->assertArrayHasKey('refunded', $content);
        $this->assertEquals(1, $content['refunded']);
        $this->assertArrayHasKey('authorized', $content);
        $this->assertEquals(1, $content['authorized']);

        // COME BACK TO THIS LATER
        $hdfcRefundedEntity = $this->getLastEntity('hdfc', true);

        $this->assertEquals('refunded', $hdfcRefundedEntity['status']);

        if ($flag === false)
        {
            $refund = $this->getLastEntity('refund', true);
            $this->assertEquals(true, $refund['gateway_refunded']);
            $this->assertNotNull($refund['transaction_id']);
        }
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
        //
        // Refunds flow has changed.
        // Before removing skip test, make sure Razorx experiment is turned ON.

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
        // Refunds flow has changed.
        // Before removing skip test, make sure Razorx experiment is turned ON.

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

        $this->fixtures->refund->edit(
            $rfnd['id'],
            [
                'speed_requested'  => 'normal',
                'speed_decisioned' => 'normal',
                'speed_processed'  => 'normal',
                'status'           => 'processed'
            ]
        );

        $rfnd = $this->getDbEntityById('refund', $rfnd['id']);

        $actual = $rfnd->toArrayPublic();
        $actual['acquirer_data'] = $rfnd->getAcquirerData()->toArray();

        $refund = $this->getEntityById('refund', $rfnd['public_id']);
        $this->assertArraySelectiveEquals($actual, $refund);

        $refunds = $this->getEntities('refund');
        $rfnds = ['entity' => 'collection', 'count' => 1, 'items' => [$actual]];
        $this->assertArraySelectiveEquals($rfnds, $refunds);
    }

    public function testFetchRefundByIdWithExtraAttributesExposedWithManualRefund()
    {

        $org = $this->fixtures->create('org');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'show_refnd_lateauth_param',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'    => $org->getId(),
        ]);

        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $this->fixtures->refund->edit(
            $rfnd['id'],
            [
                'speed_requested'  => 'normal',
                'speed_decisioned' => 'normal',
                'speed_processed'  => 'normal',
                'status'           => 'processed',
                'is_scrooge'        => true
            ]
        );

        $scroogeMock = Mockery::mock('RZP\Services\Scrooge');

        $scroogeMock->shouldReceive('getRefund')->withAnyArgs()->andReturn([
            'body' => [
                'initiation_type' => ['Merchant Initiated'],
            ],
            'code' => 200
        ]);

        $this->app->instance('scrooge', $scroogeMock);

        $rfnd = $this->getDbEntityById('refund', $rfnd['id']);

        $actual = $rfnd->toArrayPublicWithExpand();

        $this->assertArrayHasKey('processed_at', $actual);
        $this->assertArrayHasKey('refund_type', $actual);
        $this->assertEquals('manual', $actual['refund_type']);

    }

    public function testFetchRefundByIdWithExtraAttributesExposedWithAutoRefund()
    {

        $org = $this->fixtures->create('org');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'show_refnd_lateauth_param',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'    => $org->getId(),
        ]);

        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $this->fixtures->refund->edit(
            $rfnd['id'],
            [
                'speed_requested'  => 'normal',
                'speed_decisioned' => 'normal',
                'speed_processed'  => 'normal',
                'status'           => 'processed',
                'is_scrooge'        => true
            ]
        );

        $scroogeMock = Mockery::mock('RZP\Services\Scrooge');

        $scroogeMock->shouldReceive('getRefund')->withAnyArgs()->andReturn([
            'body' => [
                'initiation_type' => ['Razorpay Initiated'],
            ],
            'code' => 200
        ]);

        $this->app->instance('scrooge', $scroogeMock);

        $rfnd = $this->getDbEntityById('refund', $rfnd['id']);

        $actual = $rfnd->toArrayPublicWithExpand();

        $this->assertArrayHasKey('processed_at', $actual);
        $this->assertArrayHasKey('refund_type', $actual);
        $this->assertEquals('auto', $actual['refund_type']);

    }

    public function testFetchRefundByIdWithoutExtraAttributesExposed()
    {
        $payment = $this->fixtures->create('payment:captured');
        $rfnd = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $this->fixtures->refund->edit(
            $rfnd['id'],
            [
                'speed_requested'  => 'normal',
                'speed_decisioned' => 'normal',
                'speed_processed'  => 'normal',
                'status'           => 'processed'
            ]
        );

        $rfnd = $this->getDbEntityById('refund', $rfnd['id']);

        $actual = $rfnd->toArrayPublicWithExpand();

        $this->assertArrayNotHasKey('processed_at', $actual);
        $this->assertArrayNotHasKey('refund_type', $actual);
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
                            'amount'      => $rfnd1->getAmount()
                        ],
                        true);

        $this->assertEquals(1, $refunds['count']);

        $this->assertEquals($rfnd1->getPublicId(), $refunds['items'][0]['id']);
    }

    public function testFetchRefundsProxyAuthExpanded()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);
        Carbon::setTestNow($now);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => Channel::ICICI]);
        $this->fixtures->merchant->activate();
        $payment1 = $this->fixtures->create('payment:captured', ['gateway' => 'cybersource']);
        $rfnd1 = $this->fixtures->create('refund:from_payment', ['payment' => $payment1]);

        $refund  = $this->getLastEntity('refund', true);
        $transaction = $this->getLastEntity('transaction',true);

        $this->fixtures->transaction->edit($transaction['id'],['settled_at' => 1404986751]);

        $this->ba->privateAuth();

        $setlResponse = $this->initiateSettlements(Channel::ICICI, NULL, true, ['10000000000000']);

        $this->ba->proxyAuth();

        $data = $this->testData[__FUNCTION__];
        $data['request']['url'] = '/refunds/' . $refund['id'] . '?expand[]=transaction.settlement';

        $response = $this->makeRequestAndGetContent($data['request']);

        $lastSettlement = $this->getLastEntity('settlement', true);

        $this->assertEquals($response['transaction']['type'],'refund');
        $this->assertEquals($lastSettlement['id'],$response['transaction']['settlement_id']);
        $this->assertEquals($response['transaction']['settlement_id'], $response['transaction']['settlement']['id']);
        $this->assertEquals($response['transaction']['settlement']['status'],'created');
    }

    protected function initiateSettlements($channel, $testTimeStamp = null, $useQueue = false, $merchantIds = [])
    {
        $content = ['all' => 1];

        if ($testTimeStamp !== null)
        {
            $content['testSettleTimeStamp'] = $testTimeStamp;
        }

        if ($useQueue === true)
        {
            $content['use_queue'] = '1';
        }

        if (empty($merchantIds) === false)
        {
            $content['merchant_ids'] = $merchantIds;

            $content['settled_at'] = 1534648600;

            $content['initiated_at'] = 1534658600;
        }

        $request = [
            'url' => '/settlements/initiate/'.$channel,
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
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

    public function testPaymentRefundCreateDataProxyAuth()
    {
        $payment = $this->fixtures->create('payment:captured');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'operations');

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment->getPublicId();

        $response = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

        $this->assertEquals(true, $response[Constants::INSTANT_REFUND_SUPPORT]);
        $this->assertEquals(true, $response[Constants::GATEWAY_REFUND_SUPPORT]);
        $this->assertEquals(false, $response[Constants::DIRECT_SETTLEMENT_REFUND]);

        $this->fixtures->terminal->edit($payment['terminal_id'], [
            'type' => [
                'direct_settlement_with_refund' => '1',
                'recurring_3ds' => '1'
            ],
        ]);

        $response = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

        $this->assertEquals(true, $response[Constants::INSTANT_REFUND_SUPPORT]);
        $this->assertEquals(true, $response[Constants::GATEWAY_REFUND_SUPPORT]);
        $this->assertEquals(true, $response[Constants::DIRECT_SETTLEMENT_REFUND]);
    }

    public function testPaymentRefundCreateDataProxyAuthOnDeletedTerminal()
    {
        $payment = $this->fixtures->create('payment:captured');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'operations');

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment->getPublicId();

        $response = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

        $this->assertEquals(true, $response[Constants::INSTANT_REFUND_SUPPORT]);
        $this->assertEquals(true, $response[Constants::GATEWAY_REFUND_SUPPORT]);
        $this->assertEquals(false, $response[Constants::DIRECT_SETTLEMENT_REFUND]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('terminals')->delete();

        $response = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

        $this->assertEquals(true, $response[Constants::INSTANT_REFUND_SUPPORT]);
        $this->assertEquals(true, $response[Constants::GATEWAY_REFUND_SUPPORT]);
        $this->assertEquals(false, $response[Constants::DIRECT_SETTLEMENT_REFUND]);
    }

    public function testCreateRefundProxyAuthInvalidRole()
    {
        $payment = $this->fixtures->create('payment:captured');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'finance');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest($payment->getPublicId(), $payment->getAmount());
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

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);
        $this->assertEquals('test', $bankAccount['beneficiary_name']);
        $this->assertEquals('refund', $bankAccount['type']);

        $rfnd = $this->getDbEntityById('refund', $refund['id']);

        $actual = $rfnd->toArrayPublic();
        $actual['acquirer_data'] = $rfnd->getAcquirerData()->toArray();
    }

    public function testPaymentOrderAccountDetailsAvailableNonTpvBankTransferRefundNetbanking()
    {
        list($payment, $order) = $this->paymentOrderAccountDetailsAvailable();

        $this->fixtures->merchant->addFeatures(['non_tpv_bt_refund']);

        $response = $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        // Atom has been on boarded to Scrooge,
        // Changing this since in scrooge flow it will remain in created until cron picks up FTA for processing
        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);
        $this->assertEquals('refund', $bankAccount['type']);

        $rfnd = $this->getDbEntityById('refund', $refund['id']);

        $actual = $rfnd->toArrayPublic();
        $actual['acquirer_data'] = $rfnd->getAcquirerData()->toArray();
    }

    public function testTpvPaymentRefundNetbankingForDirectSettlementRefund()
    {
        list($payment, $order) = $this->tpvPayment();

        $this->fixtures->merchant->addFeatures(['bank_transfer_refund']);

        $this->fixtures->terminal->edit($payment['terminal_id'], [
            'type' => [
                'direct_settlement_with_refund' => '1',
                'recurring_3ds' => '1'
            ],
        ]);

        $response = $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], null);
    }

    public function testPaymentOrderAccountDetailsAvailableNonTpvBankTransferRefundNetbankingForDirectSettlementRefund()
    {
        list($payment, $order) = $this->paymentOrderAccountDetailsAvailable();

        $this->fixtures->merchant->addFeatures(['non_tpv_bt_refund']);

        $this->fixtures->terminal->edit($payment['terminal_id'], [
            'type' => [
                'direct_settlement_with_refund' => '1',
                'recurring_3ds' => '1'
            ],
        ]);

        $response = $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], null);
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

        $this->assertEquals('Test Merchant Refund ' . $payment['id'], $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

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

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function tpvPayment(): array
    {
        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->gateway = 'atom';

        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order = $this->runRequestResponseFlow($data);

        $payment['order_id'] = $order['id'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if($action !== 'verify_refund') {
                $content['bank_txn'] = '99999999';
                $content['bank_name'] = 'SBIN';
            }
        });

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);

        return [$payment, $order];
    }

    public function paymentOrderAccountDetailsAvailable()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->gateway = 'atom';

        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $this->ba->privateAuth();

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

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

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

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

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

    // Direct settlement without refund - No balance
    public function testRefundSettledByWithZeroBalance()
    {
        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');

        $this->ba->privateAuth();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $this->fixtures->edit('balance', '10000000000000', ['balance' => '0']);

        $this->startTest($payment['razorpay_payment_id']);
    }

    // Direct settlement with refund - No  balance
    public function testDirectSettlementRefundSettledByWithZeroBalance()
    {
        $this->fixtures->create('terminal:direct_settlement_refund_hdfc_terminal');

        $this->ba->privateAuth();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $this->fixtures->edit('balance', '10000000000000', ['balance' => '0']);

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

    // Direct settlement with refund - No  balance - InstantRefund
    public function testDirectSettlementInstantRefundSettledByWithZeroBalance()
    {
        $this->fixtures->create('terminal:direct_settlement_refund_hdfc_terminal');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->ba->privateAuth();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $this->fixtures->edit('balance', '10000000000000', ['balance' => '0']);

        $this->startTest($payment['razorpay_payment_id']);
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

    public function testRefundRetryWithUnsignedId()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

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
            $this->capturePayment($payment['public_id'], $payment['amount']);
        });

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

        $this->fixtures->edit('refund', $refund['id'], ['status' => 'created']);

        $internalRefundId = $this->formatRefundId($refund['id']);

        $this->retryFailedRefund($internalRefundId, $refund['payment_id']);

        $refund = $this->getLastEntity('refund', 'true');

        $this->assertEquals($refund['status'], 'processed');
    }

    public function testRefundOnHdfcPaymentCaptureTimedOut()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

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

    public function testNonINRRefundReversal()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $input = [
            'amount'   => 5000,
            'currency' => 'USD'
        ];

        $payment = $this->defaultAuthPayment($input);

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

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_'.$reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['base_amount'], $reversal['amount']);
        $this->assertEquals(0, $reversal['fee']);
        $this->assertEquals(0, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();
        $this->assertEquals($refund['base_amount'], $transaction['amount']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals($refund['base_amount'], $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);
        $this->assertEmpty($feesBreakup);
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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $scroogeResponse = [
            'mode'                                 => 'IMPS',
            'gateway_refund_support'               => true,
            'instant_refund_support'               => true,
            'payment_age_limit_for_gateway_refund' => null
        ];

        $scroogeInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['bulkUpdateRefundStatus','fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('bulkUpdateRefundStatus')
                           ->will($this->returnCallback(
                               function (array $input, bool $throwExceptionOnFailure = false) use (&$scroogeInput)
                               {
                                   $scroogeInput = $input;

                                   return json_decode('{
                                                   "success_count": 1,
                                                   "failure_count": 1,
                                                   "errors": [{
                                                        "refund_id": "abc1234d",
                                                        "code": "INVALID_STATE",
                                                        "description": "State transition invalid"
                                                     }]
                                                   }', true);
                               }));

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment(
            $payment['id'],
            3470,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('failed', $fta['status']);

        // Asserting that Scrooge is being sent the fta bank_response_code during fta -> scrooge refund update call
        $this->assertEquals('ns:E403', $scroogeInput['refunds'][0]['bank_response_code']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

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

    public function testRefundCreditFeeReversal()
    {
        // Need multiple credit logs to completely test credit reversals flow
        $this->fixtures->create('credits',
            [
                'merchant_id' =>'10000000000000',
                'type'  => 'refund',
                'value' => 3470,
                'used'  => 0,
                'campaign' => 'random1234'
            ]);

        $this->fixtures->create('credits',
            [
                'merchant_id' =>'10000000000000',
                'type'  => 'refund',
                'value' => 100,
                'used'  => 0,
                'campaign' => 'random1234'
            ]);

        $this->fixtures->create('credits',
            [
                'merchant_id' =>'10000000000000',
                'type'  => 'refund',
                'value' => 100,
                'used'  => 0,
                'campaign' => 'random1234'
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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(3670, $balance['refund_credits']);

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment(
            $payment['id'],
            3470,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(708, $refund['fee']);
        $this->assertEquals(108, $refund['tax']);
    }

    public function testInstantRefundSuccessfulWithRazorx()
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

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'hdfc_refunds_0_loc_post_init_flow_ramp_up')
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        // Assert that refund db is not saved in the new FTA flow
        $refund = $this->getLastEntity('refund', true);
        $this->assertEmpty($refund['vpa_id']);

    }

    public function testInstantRefundSuccessfulonDC()
    {
        $payment = $this->defaultAuthPayment();

        $payment['card']['number'] = '6074667022059103';

        $this->fixtures->create('iin',
            [
                'iin'    => '607466',
                'issuer' => 'HDFC',
                'type'   => 'debit',
            ]);

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->card->edit(
            $payment['card_id'],
            [
                'iin'         => '607466',
                'type'        => 'debit',
                'vault_token' => 'XXXXXXXXXXX',
            ]
        );

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals('debit', $iin['type']);

        $this->assertEquals($iin['issuer'], 'HDFC');

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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'          => 'optimum',
                'is_fta'         => true,
                'mode_requested' => 'CT',
                'fta_data'       => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals($card['id'], $fta['card_id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('created', $fta['status']);
        $this->assertEquals(true, $fta['is_fts']);
        $this->assertEquals('m2p', $fta['channel']);
        $this->assertEquals('CT', $fta['mode']);
        $this->assertNotNull($fta['initiate_at']);

        //
        // Asserting that initiate_at is immediate and not a future date.
        // 10 second leeway - to help avoid drone failures during peak hours
        //
        $this->assertLessThanOrEqual(10, abs(Carbon::now()->getTimestamp()-$fta['initiate_at']));

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(708, $refund['fee']);
        $this->assertEquals(108, $refund['tax']);
    }

    public function testInstantRefundSuccessfulOnDirectRoute()
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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refund(
            [
                'payment_id' => $payment['id'],
                'notes'      => ['a' => 'b'],
                'receipt'    => '2544325',
                'speed'      => 'optimum',
                'amount'     => 3471
            ],
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(708, $refund['fee']);
        $this->assertEquals(108, $refund['tax']);
    }

    public function testInstantRefundSuccessfulWithDefaultPricing()
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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(943, $transaction['fee']);
        $this->assertEquals(144, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(799, $feesBreakup[0]['amount']);
        $this->assertEquals(144, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(943, $refund['fee']);
        $this->assertEquals(144, $refund['tax']);
    }

    public function testInstantRefundSuccessfulWithDefaultPricingUnconfiguredMode()
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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlanV2();

        $scroogeResponse = [
            'mode' => 'NEFT',
            'gateway_refund_support' => true,
            'instant_refund_support' => true,
            'payment_age_limit_for_gateway_refund' => null
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(943, $transaction['fee']);
        $this->assertEquals(144, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(799, $feesBreakup[0]['amount']);
        $this->assertEquals(144, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3471, $payment['amount_refunded']);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertNull($fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(943, $refund['fee']);
        $this->assertEquals(144, $refund['tax']);
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
        Mail::fake();

        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $upiPayment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'vpa' => [
                        'address' => $paymentEntity->getVpa()
                    ]
                ]
            ]
        );

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
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(118, $refund['fee']);
        $this->assertEquals(18, $refund['tax']);

        // Asserting refund arn update event mail notification
        Mail::assertQueued(RefundRrnUpdated::class);
    }

    public function testOptimumRefundFeeReversalOnUpi()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment(
            $upiPayment['id'],
            3470,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'vpa' => [
                        'address' => $paymentEntity->getVpa()
                    ]
                ]
            ]
        );

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

    public function testRefundProcessedToFileInitEvent()
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

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $refund['reference1'] = '1234567890';

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['processed_at']);
        $this->assertEquals('1234567890', $refund['reference1']);

        $this->scroogeUpdateRefundStatus($refund, 'processed_to_file_init_event', 'file_init');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);
        $this->assertNull($refund['processed_at']);
        $this->assertNull($refund['reference1']);
    }

    public function testInstantRefundSuccessfulPostpaidFeeModel()
    {
        $this->fixtures->merchant->setFeeModel(Merchant\FeeModel::POSTPAID);

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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'], $transaction['debit']);
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

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(118, $refund['fee']);
        $this->assertEquals(18, $refund['tax']);
    }

    public function testOptimumRefundFeeReversalPostpaidFeeModel()
    {
        $this->fixtures->merchant->setFeeModel(Merchant\FeeModel::POSTPAID);

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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Ensuring that balance check happens only on refund amount and not fee - in case of instant refunds
        // for postpaid fee model merchants
        $this->fixtures->balance->edit('10000000000000', ['balance' => 3470]);

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment(
            $payment['id'],
            3470,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('failed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3470, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals('refund', $reversal['entity_type']);
        $this->assertEquals($refund['id'], 'rfnd_' . $reversal['entity_id']);
        $this->assertNotNull($reversal['balance_id']);
        $this->assertEquals(0, $reversal['amount']);
        $this->assertEquals(708, $reversal['fee']);
        $this->assertEquals(108, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();

        $this->assertEquals(0, $transaction['amount']);
        $this->assertEquals(-708, $transaction['fee']);
        $this->assertEquals(-108, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(-600, $feesBreakup[0]['amount']);
        $this->assertEquals(-108, $feesBreakup[1]['amount']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals(3470, $payment['amount_refunded']);
    }

    public function testRefundCreditFeeReversalForPostpaidMerchant()
    {
        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 3670
            ]);
        $this->fixtures->merchant->editRefundCredits('3670', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->fixtures->merchant->setFeeModel(Merchant\FeeModel::POSTPAID);

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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(3670, $balance['refund_credits']);

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment(
            $payment['id'],
            3470,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3470, $transaction['amount']);
        $this->assertEquals(118, $transaction['fee']);
        $this->assertEquals(18, $transaction['tax']);
        $this->assertEquals($transaction['amount'], $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['debit']);

        $creditTxns = $this->getDbEntitiesInOrder(
            'credit_transaction', 'id', ['transaction_id' => $transaction['id']], 'desc');

        $creditsUsed = 0;

        foreach ($creditTxns as $creditTxn)
        {
            $creditsUsed += $creditTxn['credits_used'];
        }

        $this->assertEquals(3470, $creditsUsed);

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
        $this->assertEquals(0, $transaction['fee_credits']);

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

    public function testInstantRefundSuccessfulWithModeDecisioningOn()
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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

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

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(118, $refund['fee']);
        $this->assertEquals(18, $refund['tax']);
    }

    public function createNetbankingPayment($issuer = null)
    {
        $payment = $this->getDefaultNetbankingPaymentArray($issuer);

        $this->gateway = 'netbanking_hdfc';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        return $payment;
    }

    public function testNetbankingInstantRefundSuccessful()
    {
        $netbankingPayment = $this->createNetbankingPayment('HDFC');

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $netbankingPayment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'bank_account' => [
                        'account_number'   => '987654321234567',
                        'beneficiary_name' => 'Not Availabe',
                        'ifsc_code'        => 'RATN0000999',
                    ]
                ]
            ]
        );

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
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals('instant', $refund['speed_processed']);
        $this->assertEquals(118, $refund['fee']);
        $this->assertEquals(18, $refund['tax']);

        $rfnd = $this->getDbEntityById('refund', $refund['id']);

        $actual = $rfnd->toArrayPublic();
        $actual['acquirer_data'] = $rfnd->getAcquirerData()->toArray();
    }

    public function testRetryViaBankAccountOnFailedInstantRefunds()
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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $this->markProcessedInstantRefundFailed($refund, $fta);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('failed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::CREATED, $refund['status']);
        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_decisioned']);
        $this->assertEquals(0, $refund['fee']);
        $this->assertEquals(0, $refund['tax']);

        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals('refund', $reversal['entity_type']);
        $this->assertEquals($refund['id'], 'rfnd_' . $reversal['entity_id']);
        $this->assertEquals(0, $reversal['amount']);
        $this->assertEquals(118, $reversal['fee']);
        $this->assertEquals(18, $reversal['tax']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($reversal['id'], 6)])->last();
        $this->assertEquals(0, $transaction['amount']);
        $this->assertEquals(-118, $transaction['fee']);
        $this->assertEquals(-18, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);
        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals(-100, $feesBreakup[0]['amount']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(-18, $feesBreakup[1]['amount']);

        $bankAccountData =
            [
                'bank_account' => [
                    'ifsc_code'        => '12345678911',
                    'account_number'   => '123456789',
                    'beneficiary_name' => 'test'
                ]
            ];

        $refundData['amount'] = 3471;

        $this->retryFailedRefund($refund['id'], $refund['payment_id'], $bankAccountData, $refundData);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    public function testInstantRefundSuccessfulSetFtaInitiateAt()
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

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Sunday
        Carbon::setTestNow(Carbon::createFromTimestamp(1578810600));

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'          => 'optimum',
                'is_fta'         => true,
                'mode_requested' => 'NEFT',
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        // Monday 8:15 AM
        $this->assertEquals(1578883500, $fta['initiate_at']);
    }

    public function testRazorxRefundRampOnFts()
    {
        list($payment, $order) = $this->tpvPayment();

        $this->fixtures->merchant->addFeatures(['bank_transfer_refund']);

        $this->enableRazorXTreatmentForRazorXRefund();

        $response = $this->refundPayment(
            $payment['id'],
            $payment['amount'],
            [
                'is_fta' => true
            ]
        );

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        // Atom has been on boarded to Scrooge,
        // Changing this since in scrooge flow it will remain in created until cron picks up FTA for processing
        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fundTransferAttempt['narration']);

        $this->assertEquals('icici', $fundTransferAttempt['channel']);

        $this->assertEquals(1, $fundTransferAttempt['is_fts']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);
        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testInstantRefundsSupportedOnNonRZPOrg()
    {
        $dummyOrg = $this->fixtures->create('org', ['custom_code' => 'dummy']);
        $this->fixtures->create('org_hostname',[
            'org_id' => $dummyOrg->getId(),
            'hostname' => 'refund.razorpay.com',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $dummyOrg['id']]);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsPricingPlanOnOrg($dummyOrg['id']);

        $this->startTest($payment['id'], (string) $payment['amount']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(1031, $refund['fee']);
        $this->assertEquals(158, $refund['tax']);
        $this->assertEquals(873, $refund['fee']-$refund['tax']);
    }

    public function testInstantRefundsSupportedOnNonRZPOrgWithDefaultPricing()
    {
        $dummyOrg = $this->fixtures->create('org', ['custom_code' => 'dummy']);
        $this->fixtures->create('org_hostname',[
            'org_id' => $dummyOrg->getId(),
            'hostname' => 'refund.razorpay.com',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $dummyOrg['id']]);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        // No custom pricing defined
        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->startTest($payment['id'], (string) $payment['amount']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(943, $refund['fee']);
        $this->assertEquals(144, $refund['tax']);
        $this->assertEquals(799, $refund['fee']-$refund['tax']);
    }

    public function testInstantRefundsNotSupportedForFeatureNotEnabledMerchants()
    {
        $this->fixtures->merchant->addFeatures(['disable_instant_refunds']);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->startTest($payment['id'], (string) $payment['amount']);
    }

    public function testRefundOnCapturedDCCPaymentWithVoidRefund()
    {
        Mail::fake();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['dcc']);
        $this->fixtures->merchant->addFeatures('void_refunds');
       // $this->gateway = 'hitachi';
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->mockCardVault();

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012010000000007';
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment');

        $refund = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals($usdAmount, $refund['gateway_amount']);
        $this->assertEquals($cardCurrency, $refund['gateway_currency']);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testUpdateRefundAtForPaymentsWithNull()
    {
        $refundAt = Carbon::today(Timezone::IST)->subDays(6)->getTimestamp();

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            [
                'refund_at'  => $refundAt
            ]);

        $response = $this->updateRefundAtForPayments([
           [ 'id' => $payments[0]->getPublicId(), 'refund_at' => null ],
           [ 'id' => $payments[1]->getPublicId(), 'refund_at' => null ],
        ]);

        $this->assertSame(2, $response['success']);

        $payments[0]->refresh();
        $this->assertNull($payments[0]->getRefundAt());

        $payments[1]->refresh();
        $this->assertNull($payments[1]->getRefundAt());
    }

    public function testUpdateRefundAtForPaymentsWithTimestamp()
    {
        $refundAt = Carbon::today(Timezone::IST)->subDays(6)->getTimestamp();

        $newRefundAt = Carbon::today(Timezone::IST)->subDays(3)->getTimestamp();

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            [
                'refund_at'  => $refundAt
            ]);

        $response = $this->updateRefundAtForPayments([
            [ 'id' => $payments[0]->getPublicId(), 'refund_at' => $newRefundAt ],
            [ 'id' => $payments[1]->getPublicId(), 'refund_at' => null         ],
        ]);

        $this->assertSame(2, $response['success']);

        $payments[0]->refresh();
        $this->assertSame($newRefundAt, $payments[0]->getRefundAt());

        $payments[1]->refresh();
        $this->assertNull($payments[1]->getRefundAt());
    }

    public function testUpdateRefundAtForPaymentsValidationFail()
    {
        $refundAt = Carbon::today(Timezone::IST)->subDays(6)->getTimestamp();

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            [
                'refund_at'  => $refundAt
            ]);

        $this->makeRequestAndCatchException(
            function () use ($payments)
            {
                $this->updateRefundAtForPayments([
                    [ 'id' => $payments[0]->getPublicId(), 'refund_at' => null      ],
                    [ 'id' => $payments[1]->getPublicId(), 'refund_at' => 'invalid' ],
                ]);
            },
            BadRequestValidationFailureException::class
        );

        $payments[0]->refresh();
        $payments[1]->refresh();

        $this->assertSame($refundAt, $payments[0]->getRefundAt());
        $this->assertSame($refundAt, $payments[1]->getRefundAt());
    }

    public function testUpdateRefundAtForPaymentsWithNoRefundAt()
    {
        $refundAt = Carbon::today(Timezone::IST)->subDays(6)->getTimestamp();

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            [
                'refund_at'  => $refundAt
            ]);

        $this->makeRequestAndCatchException(
            function () use ($payments)
            {
                $this->updateRefundAtForPayments([
                    [ 'id' => $payments[0]->getPublicId()                      ],
                    [ 'id' => $payments[1]->getPublicId(), 'refund_at' => null ],
                ]);
            },
            BadRequestValidationFailureException::class
        );
    }

    public function testUpdateRefundAtForPaymentsWithWrongPaymentId()
    {
        $refundAt = Carbon::today(Timezone::IST)->subDays(6)->getTimestamp();

        $payments = $this->fixtures->times(2)->create(
            'payment:authorized',
            [
                'refund_at'  => $refundAt
            ]);

        $oldRefundAt1 = $payments[1]->getRefundAt();

        $response = $this->updateRefundAtForPayments([
            [ 'id' => $payments[0]->getPublicId(), 'refund_at' => null ],
            [ 'id' => 'pay_wrongPaymentId', 'refund_at' => null ],
        ]);

        $this->assertSame(1, $response['success']);
        $this->assertSame(1, $response['failure']);

        $payments[0]->refresh();
        $this->assertNull($payments[0]->getRefundAt());

        $payments[1]->refresh();
        // This will not change
        $this->assertSame($oldRefundAt1, $payments[1]->getRefundAt());
    }

    private function getDefaultPaymentFlowsRequestData($iin = null)
    {
        if ($iin === null)
        {
            $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'US', 'issuer' => 'UTIB', 'network' => 'Visa',
                'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);
        }

        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'iin' => $iin->getIin()],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }

    public function testInstantRefundDecisionedToNormalForDccPayment()
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
        $this->fixtures->merchant->addFeatures(['dcc']);
        $this->fixtures->merchant->addFeatures('void_refunds');
        $this->gateway = 'hitachi';
        $this->mockCardVault();

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = [
            'number'       => CardNumber::VALID_ENROLL_NUMBER,
            'expiry_month' => '02',
            'expiry_year'  => '35',
            'cvv'          => 123,
            'name'         => 'Test Card'
        ];
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    public function testPaymentInstantRefundFee()
    {
        //
        // Instant Refunds v2 pricing is now default - not behind a razorx anymore
        // Instant Refunds v1 Pricing is behind razorx for merchants in transition phase
        //

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refundFee = $this->paymentRefundFetchFee($payment['id'], 3471, 'IMPS');

        $this->assertEquals(943, $refundFee['fee']);
        $this->assertEquals(144, $refundFee['tax']);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === 'instant_refunds_default_pricing_v1')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refundFee = $this->paymentRefundFetchFee($payment['id'], 3471);

        $this->assertEquals(589, $refundFee['fee']);
        $this->assertEquals(90, $refundFee['tax']);
    }

    public function testScroogeFetchRefundFee()
    {

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refundFee = $this->paymentScroogeFetchRefundFee(substr($payment['id'],4), 3471, 'IMPS');

        $this->assertEquals(943, $refundFee['fee']);
        $this->assertEquals(144, $refundFee['tax']);
    }

    public function testInstantRefundFTAWithNullMerchantBillingLabel()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => null, 'name' => null]);

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Razorpay Refund ' . substr($payment['id'], 4), $fta['narration']);
    }

    public function testInstantRefundFTAWithLongMerchantBillingLabel()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'Test Razorpay Long#Merchant>Name?Characters']);

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        // Ensuring removal of special characters and Character limit of 24
        $this->assertEquals('Test Razorpay LongMercha Refund ' . substr($payment['id'], 4), $fta['narration']);
    }

    public function testInstantRefundSuccessfulWithImpsModeAndCardIssuerNull()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

//        $this->fixtures->card->edit($payment['card_id'], ['iin' => $iin['iin']]);
//
//        $this->fixtures->card->edit($payment['card_id'], ['issuer'  => null]);

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

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'          => 'optimum',
                'is_fta'         => true,
                'mode_requested' => 'IMPS',
                'fta_data'       => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

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
        $this->assertEquals(708, $refund['fee']);
        $this->assertEquals(108, $refund['tax']);
    }

    public function testPaymentInstantRefundFeeWithMethodIndependentPricingDefined()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->pricing->createInstantRefundsPricingPlanWithDefaultMethodNull();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refundFee = $this->paymentRefundFetchFee($payment['id'], 3471);

        $this->assertEquals(644, $refundFee['fee']);
        $this->assertEquals(98, $refundFee['tax']);
    }

    public function testPaymentInstantRefundFeeWithMethodIndependentAndMethodPricingDefined()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->pricing->createInstantRefundsPricingPlanWithDefaultMethodNull();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refundFee = $this->paymentRefundFetchFee($payment['id'], 3471);

        // Most relevant rule is being picked.
        $this->assertEquals(118, $refundFee['fee']);
        $this->assertEquals(18, $refundFee['tax']);
    }

    public function testMcSpecificNarrationForModeCT()
    {
        $payment = $this->defaultAuthPayment();

        $payment['card']['number'] = '6074667022059103';

        $this->fixtures->create('iin',
            [
                'iin'    => '607466',
                'issuer' => 'HDFC',
                'type'   => 'debit',
            ]);

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->card->edit(
            $payment['card_id'],
            [
                'network'     => 'MasterCard',
                'iin'         => '607466',
                'type'        => 'debit',
                'vault_token' => 'XXXXXXXXXXX',
            ]
        );

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals('debit', $iin['type']);

        $this->gateway = 'hdfc';

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'          => 'optimum',
                'is_fta'         => true,
                'mode_requested' => 'CT',
                'fta_data'       => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals($card['id'], $fta['card_id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('m2p', $fta['channel']);
        $this->assertEquals('CT', $fta['mode']);
        $this->assertNotNull($fta['initiate_at']);

        $this->assertEquals('TestMerc' . substr($payment['id'], 4), $fta['narration']);

        $this->fixtures->card->edit(
            $payment['card_id'],
            [
                'network' => 'Maestro',
            ]
        );

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'          => 'optimum',
                'is_fta'         => true,
                'mode_requested' => 'CT',
                'fta_data'       => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals($card['id'], $fta['card_id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('m2p', $fta['channel']);
        $this->assertEquals('CT', $fta['mode']);
        $this->assertNotNull($fta['initiate_at']);

        $this->assertEquals('TestMerc' . substr($payment['id'], 4), $fta['narration']);
    }

    public function testFtaOnNormalGatewayRefundWithRazorxActiveSuccessful()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $scroogeInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['initiateRefund', 'fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefund')
                           ->will($this->returnCallback(
                               function ($input, $throwExceptionOnFailure) use (&$scroogeInput)
                               {
                                   $scroogeInput = $input;
                               }));

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => 'IMPS',
                               'gateway_refund_support' => true,
                               'instant_refund_support' => true,
                               'payment_age_limit_for_gateway_refund' => null
                           ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === 'upi_mindgate_refund_route_via_fta')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));

        $vpaData = ['vpa' => ['address' => $paymentEntity->getVpa()]];

        // Adding specific amount to refund - this is meant to test successful refunds on scrooge
        $refund = $this->refundPayment(
            $upiPayment['id'],
            4000,
            [
                'is_fta'   => true,
                'fta_data' => $vpaData
            ]
        );

        $refundEntity = $this->getDbLastEntity('refund');

        $ftaEntity = $this->getDbLastEntity('fund_transfer_attempt');

        // Fta data not being passed to Scrooge
        $this->assertEquals($vpaData, $scroogeInput['fta_data']);

        $this->assertEquals('normal', $scroogeInput['speed_requested']);
        $this->assertEquals('normal', $scroogeInput['speed_processed']);

        $this->assertEquals('processed', $refundEntity['status']);
        $this->assertEquals('normal', $refundEntity['speed_requested']);
        $this->assertEquals('normal', $refundEntity['speed_decisioned']);
        $this->assertEquals('normal', $refundEntity['speed_processed']);

        $this->assertEquals($refundEntity['id'], $ftaEntity['source_id']);
    }

    public function testFtaOnNormalGatewayRefundWithRazorxInactive()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $scroogeInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['initiateRefund', 'fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefund')
                           ->will($this->returnCallback(
                               function ($input, $throwExceptionOnFailure) use (&$scroogeInput)
                               {
                                   $scroogeInput = $input;
                               }));

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => 'IMPS',
                               'gateway_refund_support' => true,
                               'instant_refund_support' => true,
                               'payment_age_limit_for_gateway_refund' => null
                           ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === 'upi_mindgate_refund_route_via_fta')
                                  {
                                      return 'off';
                                  }

                                  return 'off';
                              }));

        // Adding specific amount to refund - this is meant to test successful refunds on scrooge -
        $refund = $this->refundPayment($upiPayment['id'], 4000);

        $refundEntity = $this->getDbLastEntity('refund');

        $ftaEntity = $this->getDbLastEntity('fund_transfer_attempt');

        // Fta data not being passed
        $this->assertFalse(array_key_exists('fta_data', $scroogeInput));

        $this->assertEquals('normal', $scroogeInput['speed_requested']);
        $this->assertEquals('normal', $scroogeInput['speed_processed']);

        $this->assertEquals('processed', $refundEntity['status']);
        $this->assertEquals('normal', $refundEntity['speed_requested']);
        $this->assertEquals('normal', $refundEntity['speed_decisioned']);
        $this->assertEquals('normal', $refundEntity['speed_processed']);

       $this->assertNull($ftaEntity);
    }

    public function testFtaOnNormalGatewayRefundWithRazorxActiveRetryViaGateway()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $scroogeInput = [];

        $scroogeRetryInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['initiateRefund', 'initiateRefundRetry', 'fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefund')
                           ->will($this->returnCallback(
                               function ($input, $throwExceptionOnFailure) use (&$scroogeInput)
                               {
                                   $scroogeInput = $input;
                               }));

        $this->app->scrooge->method('initiateRefundRetry')
                           ->will($this->returnCallback(
                               function ($input, $throwExceptionOnFailure) use (&$scroogeRetryInput)
                               {
                                   $scroogeRetryInput = $input;
                               }));

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn([
                               'mode' => 'IMPS',
                               'gateway_refund_support' => true,
                               'instant_refund_support' => true,
                               'payment_age_limit_for_gateway_refund' => null
                           ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === 'upi_mindgate_refund_route_via_fta')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));

        $vpaData = ['vpa' => ['address' => $paymentEntity->getVpa()]];

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $upiPayment['id'],
            3948,
            [
                'is_fta'   => true,
                'fta_data' => $vpaData
            ]
        );

        $this->assertEquals($vpaData, $scroogeInput['fta_data']);

        $refundEntity = $this->getDbLastEntity('refund');

        $ftaEntity = $this->getDbLastEntity('fund_transfer_attempt');

        // Fta data not being passed to Scrooge
        $this->assertEquals($vpaData, $scroogeInput['fta_data']);

        $this->assertEquals('normal', $scroogeInput['speed_requested']);
        $this->assertEquals('normal', $scroogeInput['speed_processed']);

        // Not yet processed
        $this->assertEquals('created', $refundEntity['status']);
        $this->assertEquals('normal', $refundEntity['speed_requested']);
        $this->assertEquals('normal', $refundEntity['speed_decisioned']);
        $this->assertEquals('normal', $refundEntity['speed_processed']);

        $this->assertEquals($refundEntity['id'], $ftaEntity['source_id']);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        // Fta data not being passed in retry since fta has already been created once
        $this->assertFalse(array_key_exists('fta_data', $scroogeRetryInput));

        $this->assertEquals('normal', $scroogeRetryInput['speed_requested']);
        $this->assertEquals('normal', $scroogeRetryInput['speed_processed']);
    }

    public function testFtaOnNormalGatewayRefundWithTerminalVariantActiveSuccessful()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $scroogeInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['initiateRefund', 'fetchRefundCreateData'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefund')
            ->will($this->returnCallback(
                function ($input, $throwExceptionOnFailure) use (&$scroogeInput)
                {
                    $scroogeInput = $input;
                }));

        $this->app->scrooge->method('fetchRefundCreateData')
            ->willReturn([
                'mode' => 'IMPS',
                'gateway_refund_support' => true,
                'instant_refund_support' => true,
                'payment_age_limit_for_gateway_refund' => null
            ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === '100UPIMindgate_terminal_refunds_route_via_fta')
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $vpaData = ['vpa' => ['address' => $paymentEntity->getVpa()]];

        // Adding specific amount to refund - this is meant to test successful refunds on scrooge
        $refund = $this->refundPayment(
            $upiPayment['id'],
            4000,
            [
                'is_fta'   => true,
                'fta_data' => $vpaData
            ]
        );

        $refundEntity = $this->getDbLastEntity('refund');

        $ftaEntity = $this->getDbLastEntity('fund_transfer_attempt');

        // Fta data not being passed to Scrooge
        $this->assertEquals($vpaData, $scroogeInput['fta_data']);
        $this->assertEquals('normal', $scroogeInput['speed_requested']);
        $this->assertEquals('normal', $scroogeInput['speed_processed']);

        $this->assertEquals('processed', $refundEntity['status']);
        $this->assertEquals('normal', $refundEntity['speed_requested']);
        $this->assertEquals('normal', $refundEntity['speed_decisioned']);
        $this->assertEquals('normal', $refundEntity['speed_processed']);

        $this->assertEquals($refundEntity['id'], $ftaEntity['source_id']);
    }

    public function testFtaOnNormalGatewayRefundWithTerminalVariantInactive()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $scroogeInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['initiateRefund', 'fetchRefundCreateData'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefund')
            ->will($this->returnCallback(
                function ($input, $throwExceptionOnFailure) use (&$scroogeInput)
                {
                    $scroogeInput = $input;
                }));

        $this->app->scrooge->method('fetchRefundCreateData')
            ->willReturn([
                'mode' => 'IMPS',
                'gateway_refund_support' => true,
                'instant_refund_support' => true,
                'payment_age_limit_for_gateway_refund' => null
            ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === '100UPIMindgate_terminal_refunds_route_via_fta')
                    {
                        return 'off';
                    }

                    return 'off';
                }));

        // Adding specific amount to refund - this is meant to test successful refunds on scrooge -
        $refund = $this->refundPayment($upiPayment['id'], 4000);

        $refundEntity = $this->getDbLastEntity('refund');

        $ftaEntity = $this->getDbLastEntity('fund_transfer_attempt');

        // Fta data not being passed
        $this->assertFalse(array_key_exists('fta_data', $scroogeInput));

        $this->assertEquals('normal', $scroogeInput['speed_requested']);
        $this->assertEquals('normal', $scroogeInput['speed_processed']);

        $this->assertEquals('processed', $refundEntity['status']);
        $this->assertEquals('normal', $refundEntity['speed_requested']);
        $this->assertEquals('normal', $refundEntity['speed_decisioned']);
        $this->assertEquals('normal', $refundEntity['speed_processed']);

        $this->assertNull($ftaEntity);
    }

    public function testFtaOnNormalGatewayRefundWithTerminalVariantActiveRetryViaGateway()
    {
        $upiPayment = $this->createUpiPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $scroogeInput = [];

        $scroogeRetryInput = [];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['initiateRefund', 'initiateRefundRetry', 'fetchRefundCreateData'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefund')
            ->will($this->returnCallback(
                function ($input, $throwExceptionOnFailure) use (&$scroogeInput)
                {
                    $scroogeInput = $input;
                }));

        $this->app->scrooge->method('initiateRefundRetry')
            ->will($this->returnCallback(
                function ($input, $throwExceptionOnFailure) use (&$scroogeRetryInput)
                {
                    $scroogeRetryInput = $input;
                }));

        $this->app->scrooge->method('fetchRefundCreateData')
            ->willReturn([
                'mode' => 'IMPS',
                'gateway_refund_support' => true,
                'instant_refund_support' => true,
                'payment_age_limit_for_gateway_refund' => null
            ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === '100UPIMindgate_terminal_refunds_route_via_fta')
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $vpaData = ['vpa' => ['address' => $paymentEntity->getVpa()]];

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $upiPayment['id'],
            3948,
            [
                'is_fta'   => true,
                'fta_data' => $vpaData
            ]
        );

        $this->assertEquals($vpaData, $scroogeInput['fta_data']);

        $refundEntity = $this->getDbLastEntity('refund');

        $ftaEntity = $this->getDbLastEntity('fund_transfer_attempt');

        // Fta data not being passed to Scrooge
        $this->assertEquals($vpaData, $scroogeInput['fta_data']);

        $this->assertEquals('normal', $scroogeInput['speed_requested']);
        $this->assertEquals('normal', $scroogeInput['speed_processed']);

        // Not yet processed
        $this->assertEquals('created', $refundEntity['status']);
        $this->assertEquals('normal', $refundEntity['speed_requested']);
        $this->assertEquals('normal', $refundEntity['speed_decisioned']);
        $this->assertEquals('normal', $refundEntity['speed_processed']);

        $this->assertEquals($refundEntity['id'], $ftaEntity['source_id']);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        // Fta data not being passed in retry since fta has already been created once
        $this->assertFalse(array_key_exists('fta_data', $scroogeRetryInput));

        $this->assertEquals('normal', $scroogeRetryInput['speed_requested']);
        $this->assertEquals('normal', $scroogeRetryInput['speed_processed']);
    }

    public function testInstantDecisioningWhenGatewayRefundNotSupported()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $scroogeResponse = [
            'mode' => 'IMPS',
            'gateway_refund_support' => false,
            'instant_refund_support' => true,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['fetchRefundCreateData'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
            ->willReturn($scroogeResponse);

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals('instant', $refund['speed_decisioned']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);
    }

    public function testGatewayRefundNotSupportedWhenRequestedNormalRefund()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $scroogeResponse = [
            'mode' => 'IMPS',
            'gateway_refund_support' => false,
            'instant_refund_support' => true,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        $failed = false;

        try
        {
            $this->refundPayment(
                $payment['id'],
                3471,
                [
                    'speed'    => 'normal',
                    'is_fta'   => true,
                    'fta_data' => [
                        'card_transfer' => [
                            'card_id' => $payment['card_id']
                        ]
                    ]
                ]
            );
        }
        catch (BadRequestException $ex)
        {
            $failed = true;

            $this->assertEquals(ErrorCode::BAD_REQUEST_ONLY_INSTANT_REFUND_SUPPORTED, $ex->getCode());
            $this->assertEquals('Payment is more than 6 months old, only instant refund is supported', $ex->getMessage());
        }

        $this->assertTrue($failed);
    }

    public function testNoRefundModeSupportedOnRefund()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $scroogeResponse = [
            'mode' => null,
            'gateway_refund_support' => false,
            'instant_refund_support' => false,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        $failed = false;

        try
        {
            $this->refundPayment(
                $payment['id'],
                3471,
                [
                    'speed'    => 'normal',
                    'is_fta'   => true,
                    'fta_data' => [
                        'card_transfer' => [
                            'card_id' => $payment['card_id']
                        ]
                    ]
                ]
            );
        }
        catch (BadRequestException $ex)
        {
            $failed = true;

            $this->assertEquals(ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK, $ex->getCode());
            $this->assertEquals('Refund is not supported by the bank because the payment is more than 6 months old', $ex->getMessage());
        }

        $this->assertTrue($failed);

        $failed = false;

        try
        {
            $this->refundPayment(
                $payment['id'],
                3471,
                [
                    'speed'    => 'optimum',
                    'is_fta'   => true,
                    'fta_data' => [
                        'card_transfer' => [
                            'card_id' => $payment['card_id']
                        ]
                    ]
                ]
            );
        }
        catch (BadRequestException $ex)
        {
            $failed = true;

            $this->assertEquals(ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK, $ex->getCode());
            $this->assertEquals('Refund is not supported by the bank because the payment is more than 6 months old', $ex->getMessage());
        }

        $this->assertTrue($failed);
    }

    public function testErrorMessageOnRefundsBlockedPayment()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $scroogeResponse = [
            'mode' => null,
            'gateway_refund_support' => false,
            'instant_refund_support' => false,
            'payment_age_limit_for_gateway_refund' => 170
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        $failed = false;

        try
        {
            $this->refundPayment(
                $payment['id'],
                3471,
                [
                    'speed'    => 'normal',
                    'is_fta'   => true,
                    'fta_data' => [
                        'card_transfer' => [
                            'card_id' => $payment['card_id']
                        ]
                    ]
                ]
            );
        }
        catch (BadRequestException $ex)
        {
            $failed = true;

            $this->assertEquals(ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK, $ex->getCode());
            $this->assertEquals('Refund is not supported by the bank because the payment is more than 5 months old', $ex->getMessage());
        }

        $this->assertTrue($failed);
    }

    public function testFailVoidRefundOnReverseUnsupportedAcquirer()
    {
        $this->gateway = 'paylater';

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_epaylater_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');

        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->getDefaultPayLaterPaymentArray('epaylater');

        $this->setOtp('123456');

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->startTest($payment['id'], $payment['amount']);
    }

    public function testSuccessVoidRefundOnReverseSupportedAcquirer()
    {
        $this->gateway = 'paylater';

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');

        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->getDefaultPayLaterPaymentArray('hdfc');

        $this->setOtp('123456');

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->startTest($payment['id'], $payment['amount']);
    }

    public function testFailVoidRefundOnNullAcquirer()
    {
        $this->gateway = 'paylater';

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');

        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->getDefaultPayLaterPaymentArray('hdfc');

        $this->setOtp('123456');

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->fixtures->terminal->edit($this->sharedTerminal->getId(), [
            'gateway_acquirer' => null
        ]);

        $this->startTest($payment['id'], $payment['amount']);
    }

    public function testOptimumDecisioningForFeatureEnabledMerchantsWhenGatewayRefundNotSupported()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures('refund_aged_payments');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $scroogeResponse = [
            'mode' => 'IMPS',
            'gateway_refund_support' => false,
            'instant_refund_support' => true,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals('optimum', $refund['speed_decisioned']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);
    }

    public function testNormalDecisioningForFeatureEnabledMerchantsWhenRefundNotSupported()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures('refund_aged_payments');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $scroogeResponse = [
            'mode' => null,
            'gateway_refund_support' => false,
            'instant_refund_support' => false,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals('normal', $refund['speed_decisioned']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    public function testRefundCreationWhenRefundNotSupportedForFeatureEnabledMerchant()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->merchant->addFeatures('refund_aged_payments');

        $this->gateway = 'hdfc';

        $scroogeResponse = [
            'mode' => null,
            'gateway_refund_support' => false,
            'instant_refund_support' => false,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'normal',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('normal', $refund['speed_requested']);
        $this->assertEquals('normal', $refund['speed_decisioned']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    public function testScroogeRetryWithVerify()
    {
        $this->ba->adminAuth();

        $input = [
            'refund_ids' => [
                '12345678900987',
                '12345678900981',
            ],
        ];

        $expectedOutput = [
            '12345678900987' => [
                'error' => NULL
            ],
            '12345678900981' => [
                'error' => NULL
            ]
        ];

        $this->testData['scroogeRetryWithVerify']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['scroogeRetryWithVerify']);

        $this->assertEquals($expectedOutput, $response);
    }

    public function testScroogeRetryWithoutVerify()
    {
        $this->ba->adminAuth();

        $input = [
            'refund_ids' => [
                '12345678900987',
                '12345678900981',
            ],
        ];

        $expectedOutput = [
            '12345678900987' => [
                'error' => NULL
            ],
            '12345678900981' => [
                'error' => NULL
            ]
        ];

        $this->testData['scroogeRetryWithoutVerify']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['scroogeRetryWithoutVerify']);

        $this->assertEquals($expectedOutput, $response);
    }

    public function testScroogeRetryViaSourceFundTransfers()
    {
        $this->ba->adminAuth();

        $input = [
            'refund_ids' => [
                '12345678900987',
                '12345678900981',
            ],
        ];

        $expectedOutput = [
            '12345678900987' => [
                'error' => NULL
            ],
            '12345678900981' => [
                'error' => NULL
            ]
        ];

        $this->testData['scroogeRetryViaSourceFundTransfers']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['scroogeRetryViaSourceFundTransfers']);

        $this->assertEquals($expectedOutput, $response);
    }

    public function testScroogeRetryViaCustomFundTransfers()
    {
        $this->ba->adminAuth();

        $input = [
            'refunds' => [
                '12345678900987' => [
                    'fta_data' => [
                        'vpa' => [
                            'address' => 'blocked@test'
                        ]
                    ]
                ],
                '12345678900981' => [
                    'fta_data' => [
                        'vpa' => [
                            'address' => 'blocked@test'
                        ]
                    ]
                ]
            ]
        ];

        $expectedOutput = [
            '12345678900987' => [
                'error' => NULL
            ],
            '12345678900981' => [
                'error' => NULL
            ]
        ];

        $this->testData['scroogeRetryViaCustomFundTransfers']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['scroogeRetryViaCustomFundTransfers']);

        $this->assertEquals($expectedOutput, $response);
    }

    public function testScroogeRetryViaCustomFundTransfersBatch()
    {
        $this->ba->batchAppAuth();

        $input = [
            "type" => "retry_refunds_to_ba",
            "refunds" => [
                "12345678900987" => [
                    "fta_data" => [
                        "bank_account" => [
                            "beneficiary_name" => "Test",
                            "account_number" => "457823901212",
                            "ifsc" => "SBII0000012",
                            "transfer_mode" => "neft"
                        ]
                    ]
                ]
            ]
        ];

        $expectedOutput = [
            "type" => "retry_refunds_to_ba",
            "refunds" => [
                "12345678900987" => [
                    "fta_data" => [
                        "bank_account" => [
                            "beneficiary_name" => "Test",
                            "account_number" => "457823901212",
                            "ifsc" => "SBII0000012",
                            "transfer_mode" => "neft"
                        ]
                    ]
                ]
            ],
            "refund_id" => "12345678900987",
            "beneficiary_name" => "Test",
            "account_number" => "457823901212",
            "ifsc" => "SBII0000012",
            "transfer_mode" => "neft",
            "error_code" => null,
            "error_description" => null
        ];

        $this->testData['scroogeRetryViaCustomFundTransfersBatch']['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData['scroogeRetryViaCustomFundTransfersBatch']);
        $this->assertEquals($expectedOutput, $response);
    }

    public function testUpdateRefundNotes()
    {
        $this->ba->privateAuth();

        $this->testData['updateRefundNotes']['request']['url'] = '/refunds/rfnd_GfnS1Fj048VHo2';

        $response = $this->runRequestResponseFlow($this->testData['updateRefundNotes']);

        $this->assertEquals('rfnd_GfnS1Fj048VHo2', $response['id']);

        $this->testData['updateRefundNotes']['request']['url'] = '/refunds/rfnd_UpdateError001';

        $this->testData['updateRefundNotes']['response'] = [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ];

        $this->testData['updateRefundNotes']['exception'] = [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ];

        $this->runRequestResponseFlow($this->testData['updateRefundNotes']);
    }

    public function testUpdateRefundNotesInternal()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id'], $payment['amount']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEmpty($refund['notes']);

        $this->ba->scroogeAuth();

        $this->testData['updateRefundNotes']['request']['url'] = '/refunds/internal/' . substr($refund['id'], 5);

        $this->runRequestResponseFlow($this->testData['updateRefundNotes']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals( [
            'scrooge' => 'welcome'
        ], $refund['notes']);
    }

    public function testRearchPaymentRefund()
    {
        $this->enablePgRouterConfig();

        Mail::fake();

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' =>'GfnS1Fj048VHo2',
            'type' =>'payment',
            'merchant_id' =>'10000000000000',
            'amount' =>50000,
            'fee' =>1000,
            'mdr' =>1000,
            'tax' =>0,
            'pricing_rule_id' => NULL,
            'debit' =>0,
            'credit' =>49000,
            'currency' =>'INR',
            'balance' =>2025400,
            'gateway_amount' => NULL,
            'gateway_fee' =>0,
            'gateway_service_tax' =>0,
            'api_fee' =>0,
            'gratis' =>FALSE,
            'fee_credits' =>0,
            'escrow_balance' =>0,
            'channel' =>'axis',
            'fee_bearer' =>'platform',
            'fee_model' =>'prepaid',
            'credit_type' =>'default',
            'on_hold' =>FALSE,
            'settled' =>FALSE,
            'settled_at' =>1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' =>'10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' =>TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' =>1614262078,
            'updated_at' =>1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id' =>'10000000000000',
                'name' =>'Harshil',
                'expiry_month' =>12,
                'expiry_year' =>2024,
                'iin' =>'401200',
                'last4' =>'3335',
                'length' =>'16',
                'network' =>'Visa',
                'type' =>'credit',
                'sub_type' =>'consumer',
                'category' =>'STANDARD',
                'issuer' =>'HDFC',
                'international' =>FALSE,
                'emi' =>TRUE,
                'vault' =>'rzpvault',
                'vault_token' =>'NDAxMjAwMTAzODQ0MzMzNQ==',
                'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
                'trivia' => NULL,
                'country' =>'IN',
                'global_card_id' => NULL,
                'created_at' =>1614256967,
                'updated_at' =>1614256967,
        ]);

        // sd($card->getId());

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->makePartial();

        $this->app->instance('pg_router', $pgService);

        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [
                        'id' =>'GfnS1Fj048VHo2',
                        'merchant_id' =>'10000000000000',
                        'amount' =>50000,
                        'currency' =>'INR',
                        'base_amount' =>50000,
                        'method' =>'card',
                        'status' =>'captured',
                        'two_factor_auth' =>'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' =>FALSE,
                        'amount_authorized' =>50000,
                        'amount_refunded' =>0,
                        'base_amount_refunded' =>0,
                        'amount_transferred' =>0,
                        'amount_paidout' =>0,
                        'refund_status' => NULL,
                        'description' =>'description',
                        'card_id' =>$card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' =>FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' =>'a@b.com',
                        'contact' =>'+919918899029',
                        'notes' =>[
                            'merchant_order_id' =>'id',
                        ],
                        'transaction_id' => $transaction->getId(),
                        'authorized_at' =>1614253879,
                        'auto_captured' =>FALSE,
                        'captured_at' =>1614253880,
                        'gateway' =>'hdfc',
                        'terminal_id' =>'1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' =>0,
                        'signed' =>FALSE,
                        'verified' => NULL,
                        'gateway_captured' =>TRUE,
                        'verify_bucket' =>0,
                        'verify_at' =>1614253880,
                        'callback_url' => NULL,
                        'fee' =>1000,
                        'mdr' =>1000,
                        'tax' =>0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' =>FALSE,
                        'save' =>FALSE,
                        'late_authorized' =>FALSE,
                        'convert_currency' => NULL,
                        'disputed' =>FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' =>'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' =>1614253879,
                        'updated_at' =>1614253880,
                        'captured' =>TRUE,
                        'reference2' => '12343123',
                        'entity' =>'payment',
                        'fee_bearer' =>'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' =>FALSE,
                        'gateway_amount' =>50000,
                        'gateway_currency' =>'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' =>FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $transaction, $paymentData)
            {
                if ($method === 'GET')
                {
                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    return [];
                }

            });


        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $transaction, $paymentData)
            {
                if ($method === 'GET')
                {
                    return  $paymentData;

                }

                if ($method === 'POST')
                {
                    return [];
                }

            });

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

        $refund = $this->refundPayment('pay_GfnS1Fj048VHo2');

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));


        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testRefundWithApplicableDiscountCred()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:direct_cred_terminal');

        $this->fixtures->merchant->enableApp(Account::TEST_ACCOUNT, 'cred');

        $payment = $this->getDefaultCredPayment();

        $payment['amount'] = 200000;
        unset($payment['app_present']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer('cred')->getAsyncCallbackContentCred($payment);

        $this->makeS2SCallbackAndGetContent($content, 'cred');

        $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals(200000, $refund['amount']);

        $payment = $this->getLastPayment('payment', true);

        $this->assertEquals('full', $payment['refund_status']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(160000, $transaction['debit']);
    }

    public function testPartialRefundWithApplicableDiscountCred()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:direct_cred_terminal');

        $this->fixtures->merchant->enableApp(Account::TEST_ACCOUNT, 'cred');

        $payment = $this->getDefaultCredPayment();

        $payment['amount'] = 200000;
        unset($payment['app_present']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $refundAmount = 100000;

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer('cred')->getAsyncCallbackContentCred($payment);

        $this->makeS2SCallbackAndGetContent($content, 'cred');

        $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($payment['id'], $refundAmount);

        $this->assertEquals($refundAmount, $refund['amount']);

        $payment = $this->getLastPayment('payment', true);

        $this->assertEquals('partial', $payment['refund_status']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(80000, $transaction['debit']);
    }

    public function testAuthorizedPaymentRefundOnScrooge()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                   ->setConstructorArgs([$this->app])
                   ->setMethods(['getTreatment'])
                   ->getMock();

        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                if ($feature === RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1)
                                  {
                                    return 'on';
                                  }
                                  return 'off';
                              }));

        $payment = $this->defaultAuthPayment();
        $payment = $this->getDbEntityById('payment', $payment['id']);

        $input = ['amount' => $payment['amount']];

        $refund = $this->refundAuthorizedPayment($payment['id'], $input);
        $this->assertPassportKeyExists('consumer.id'); // just check for presence of passport

        // $payment = $this->getLastEntity('payment', true);
        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertSame($payment['id'], Payment::stripDefaultSign($refund['payment_id']));


        $this->assertSame('refunded', $payment['status']);
    }

    public function testInternalAuthorizedPaymentRefundOnScrooge()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                   ->setConstructorArgs([$this->app])
                   ->setMethods(['getTreatment'])
                   ->getMock();

        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                if ($feature === RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1)
                                  {
                                    return 'on';
                                  }
                                  return 'off';
                              }));

        $payment = $this->defaultAuthPayment();
        $payment = $this->getDbEntityById('payment', $payment['id']);

        $input = ['amount' => $payment['amount']];

        $refund = $this->refundAuthorizedPayment($payment['id'], $input, true);
        $this->assertPassportKeyExists('consumer.id'); // just check for presence of passport

        // $payment = $this->getLastEntity('payment', true);
        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertSame($payment['id'], Payment::stripDefaultSign($refund['payment_id']));


        $this->assertSame('refunded', $payment['status']);
    }

    public function testInternalAuthorizedPaymentRefundOnScroogeForCaptured()
    {
        $this->enableRazorXTreatmentForRefundV2();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);
        $payment = $this->getDbEntityById('payment', $payment['id']);

        $input = ['amount' => $payment['amount']];

        // Now handling this before the refund call.
        $this->expectException('RZP\Exception\InvalidArgumentException');

        $this->expectExceptionMessage('Can only refund authorized payments here but the status is captured');

        $this->refundAuthorizedPayment($payment['id'], $input, true);
    }

    public function testM2PTokenisationFlowForRZPTokens()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $this->fixtures->iin->edit($card['iin'], ['type' => 'debit', 'issuer' => 'SBIN']);

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'debit');

        $this->assertEquals($iin['issuer'], 'SBIN');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $tokenisedCard = $this->fixtures->create('card', [
            'merchant_id' =>'10000000000000',
            'name' =>'refunds',
            'expiry_month' =>12,
            'expiry_year' =>2030,
            'iin' =>'401200',
            'last4' =>'3335',
            'length' =>'16',
            'network' =>'Visa',
            'type' =>'debit',
            'sub_type' =>'consumer',
            'category' =>'STANDARD',
            'issuer' =>'SBIN',
            'international' =>FALSE,
            'emi' =>TRUE,
            'vault' =>'visa',
            'vault_token' =>'10000000000004',
            'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
            'trivia' => "1",
            'country' =>'IN',
            'global_card_id' => NULL,
            'created_at' =>1614256967,
            'updated_at' =>1614256967,
        ]);

        $token = $this->fixtures->create('token', ['method' => 'card', 'recurring' => false, 'card_id' => $tokenisedCard['id']]);

        $this->fixtures->payment->edit($payment['id'], ['token_id' => $token['id']]);

        $this->gateway = 'hdfc';

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $scroogeResponse = [
            'mode' => 'IMPS',
            'gateway_refund_support' => true,
            'instant_refund_support' => true,
            'payment_age_limit_for_gateway_refund' => null
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['fetchRefundCreateData'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
            ->willReturn($scroogeResponse);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'refunds_tokenisation_ir_ramp')
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'          => 'optimum',
                'is_fta'         => true,
                'mode_requested' => 'CT',
                'fta_data'       => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals('optimum', $refund['speed_decisioned']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('created', $fta['status']);
        $this->assertEquals($tokenisedCard['id'], $fta['card_id']);
    }

    public function testRefundOnDisabledMethod()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->payment->edit($payment['id'], ['method' => 'offline']);

        $this->ba->privateAuth();

        $this->startTest($payment['id'], (string) $payment['amount']);
    }

    //Refund Source balance, refund goes through Refund Credits
    public function testRefundSuccessFallbackEnoughRefundCredits()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockRazorxForFallback();

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'balance']);

        $this->fixtures->merchant->editRefundCredits('50099', '10000000000000');

        $this->fixtures->create('credits',
            [
                'merchant_id' =>'10000000000000',
                'type'  => 'refund',
                'value' => 50099,
                'used'  => 0,
                'campaign' => 'random1234'
            ]);

        $this->fixtures->merchant->editBalance('99999', '10000000000000');


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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals($refund['vpa_id'], $fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals( 50099 - $transaction['fee_credits'], $balance['refund_credits']);
        $this->assertEquals(99999,$balance['balance']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $payment = $this->getDbLastPayment();
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
        $this->assertEquals(708, $refund['fee']);
        $this->assertEquals(108, $refund['tax']);

    }

    //Refund Fallbacks to balance
    public function testRefundSuccessFallbackNotEnoughRefundCredits()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockRazorxForFallback();

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'balance']);

        $this->fixtures->merchant->editBalance('99999', '10000000000000');

        $this->fixtures->merchant->editRefundCredits('199', '10000000000000');


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

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals('optimum', $refund['speed_requested']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);
        $this->assertEquals($refund['vpa_id'], $fta['vpa_id']);
        $this->assertEquals('refund', $fta['purpose']);
        $this->assertEquals('processed', $fta['status']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $transaction = $this->getDbEntities('transaction', ['entity_id' => substr($refund['id'], 5)])->last();

        $this->assertEquals(3471, $transaction['amount']);
        $this->assertEquals(708, $transaction['fee']);
        $this->assertEquals(108, $transaction['tax']);
        $this->assertEquals($transaction['amount'] + $transaction['fee'], $transaction['debit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals( 199, $balance['refund_credits']);
        $this->assertEquals(99999-$transaction['debit'],$balance['balance']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(600, $feesBreakup[0]['amount']);
        $this->assertEquals(108, $feesBreakup[1]['amount']);

        $payment = $this->getDbLastPayment();
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
        $this->assertEquals(708, $refund['fee']);
        $this->assertEquals(108, $refund['tax']);

    }

    //Refund source is set to balance and the balance and refund credits are both zero
    public function testRefundFallbackWithZeroBalance()
    {
        $this->mockRazorxForFallback();

        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'balance']);

        $this->fixtures->merchant->editBalance('0', '10000000000000');

        $this->fixtures->merchant->editRefundCredits('0', '10000000000000');

        $this->startTest($payment['id'], (string) $payment['amount']);

    }

    //Refund source is set to balance and the balance is zero and refund credits are non zero, fallback to refund credits is disabled
    public function testRefundFallbackWithZeroBalanceAndNonZeroCredits()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'balance']);

        $this->fixtures->merchant->editBalance('0', '10000000000000');

        $this->fixtures->merchant->editRefundCredits('100000', '10000000000000');

        $this->fixtures->create('credits',
            [
                'merchant_id' =>'10000000000000',
                'type'  => 'refund',
                'value' => 100000,
                'used'  => 0,
                'campaign' => 'random1234'
            ]);


        $this->startTest($payment['id'], (string) $payment['amount']);

    }

    //Refund source is set to refundCredits and the refund credits are zero
    public function testRefundFallbackWithZeroRefundCredits()
    {
        $this->mockRazorxForFallback();
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->fixtures->merchant->editRefundCredits('0', '10000000000000');

        $this->startTest($payment['id'], (string) $payment['amount']);

    }

    public function testRefundCreateOnArchivedPayment()
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

        // Test archival case : remove payment data from test db (current) and add in live db.
        $paymentId = substr($payment['id'], 4);

        $paymentEntity = \DB::connection('test')->table('payments')->select(\DB::raw("*"))->where('id', '=', $paymentId)->get()->first();

        $paymentArray = (array)$paymentEntity;

        $originalPaymentCreatedAt = $paymentArray['created_at'];

        \DB::connection('test')->statement('SET FOREIGN_KEY_CHECKS=0');
        \DB::connection('live')->statement('SET FOREIGN_KEY_CHECKS=0');

        // insert card into live DB
        \DB::connection('live')->table('payments')->insert($paymentArray);

        // remove payment from test db
        \DB::connection('test')->table('payments')->where('id', '=', $paymentId)->limit(1)->update(['id' => 'KOOmLB0xqazzXp']);

        $paymentEntity = \DB::connection('test')->table('payments')->select(\DB::raw("*"))->where('id', '=', $paymentId)->get()->first();

        $this->assertNull($paymentEntity);

        // adding delay to assert created_at on payment reinsert
        sleep(1);

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);

        $paymentEntity = \DB::connection('test')->table('payments')->select(\DB::raw("*"))->where('id', '=', $paymentId)->get()->first();

        $paymentArray = (array)$paymentEntity;

        $this->assertEquals($originalPaymentCreatedAt, $paymentArray['created_at']);

        \DB::connection('test')->statement('SET FOREIGN_KEY_CHECKS=1');
        \DB::connection('live')->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testZeroDebitCreditRefund()
    {
        $this->mockRazorxForFallback();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');

        $this->gateway = 'ebs';

        $this->setMockGatewayTrue();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $input['force'] = '1';

        $this->refundAuthorizedPayment($payment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('ebs', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($refund['refund_id'],substr($txn['entity_id'],5));

        $this->assertEquals(0, $txn['credit']);

        $this->assertEquals(0, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
    }

}
