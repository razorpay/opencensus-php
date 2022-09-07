<?php

namespace RZP\Tests\Functional\Gateway\Cybersource;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use Illuminate\Support\Facades\Queue;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\CorePaymentServiceSync;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->gateway = 'cybersource';

        $this->mockCardVault();
    }

    public function testPayment()
    {
        $payment = $this->defaultAuthPayment();

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('1000CybrsTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // $this->assertNull($payment['verify_at']);
    }

    public function testMotoTransaction()
    {
        $motoTerminal = $this->fixtures->create('terminal:cybersource_axis_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $payment = $this->getDefaultPaymentArray();

        $payment['auth_type'] = 'skip';

        unset($payment['card']['cvv']);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($motoTerminal['id'], $payment['terminal_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('skip', $payment['auth_type']);
    }

    public function testPaymentEnrolledCard()
    {
        $enrolledCard = [
            'card' => [
                'number'    => '4000000000000002',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2024',
                'cvv'               => '566',
            ]
        ];

        $payment = $this->defaultAuthPayment($enrolledCard);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('1000CybrsTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        //    $this->assertNotNull($payment['reference2']);
        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // After capture verify_at is set to current_time()
        // $this->assertNull($payment['verify_at']);
    }

    public function testPaymentWithSavedCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['token'] = '1000gcardtoken';
        $payment['app_token'] = 'capp_1000000custapp';

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($response['razorpay_payment_id'], $payment['id']);
        $this->assertTestResponse($payment);
    }

    public function testNotEnrolledPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['two_factor_auth'],
            \RZP\Models\Payment\TwoFactorAuth::NOT_APPLICABLE);
    }

    public function testGatewayFullRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->assertRefundAmount($payment['amount']);

        $this->refundPayment($payment['id']);

        $cybersource = $this->getLastEntity('cybersource', true);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($payment['id']);

        $this->assertEquals($paymentId, $cybersource['payment_id']);

        $this->assertNotNull($cybersource['refund_id']);

        $this->assertTestResponse($cybersource);
    }

    public function testGatewayPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refundAmount = (int) ($payment['amount'] / 5);

        $this->assertRefundAmount($refundAmount);

        $this->refundPayment($payment['id'], $refundAmount);

        $cybersource = $this->getLastEntity('cybersource', true);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($payment['id']);

        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertNotNull($cybersource['refund_id']);
        $this->assertTestResponse($cybersource);
    }

    public function testAuthorizedPaymentRefund()
    {
        $this->fixtures->merchant->addFeatures('reverse');

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $input = ['amount' => $payment['amount']];

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'verify_reverse')
            {
                $content['data']['status'] = 'reversed';
            }
        });

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);

        $this->assertTestResponse($refund);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('reverse', $cybersource['action']);
    }

    public function testGatewayPaymentMatchVerify()
    {
        $payment = $this->doAuthPayment();

        $this->fixtures->base->editEntity(
            'payment', $payment['razorpay_payment_id'], ['authorized_at' => strtotime('-1 min')]);

        $response = $this->verifyPayment($payment['razorpay_payment_id']);

        $this->assertSame($response['payment']['verified'], 1);
        $this->assertSame($response['gateway']['status'], 'status_match');
        $this->assertSame($response['gateway']['gateway'], 'cybersource');
        $this->assertSame($response['gateway']['gatewayPayment']['status'], 'authorized');
    }

    public function testGatewayAuthorizedPaymentMatchVerify()
    {
        $payment = $this->doAuthPayment();

        $this->fixtures->base->editEntity(
            'payment', $payment['razorpay_payment_id'], ['authorized_at' => strtotime('-1 min')]);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'verify_content')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
            }
        });

        $data = $this->testData['testGatewayPaymentMismatchVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $response = $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    public function testGatewayFailedPaymentMismatchVerify()
    {
        $this->mockTimeout('processor');

        $this->makeRequestAndCatchException(function()
        {
            $this->mockServerContentFunction(function(&$content, $action = null)
            {
                if ($action === 'pay_init')
                {
                    $content['success'] = false;
                    $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                    $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                    $content['error']['gateway_error_description'] = 'Do not honour';
                }
            });

            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testGatewayPaymentMismatchVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testGatewayRefundVerify()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);

        Carbon::setTestNow($time);

        $this->resetMockServer();

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);
    }

    public function testGatewayVerifyRefundVerifyFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->scroogeRefund($refund);

        $this->assertEquals(false, $response['success']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $response['status_code']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('created', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(false, $actualRefund['gateway_refunded']);
    }

    public function testGatewayVerifyRefundFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('created', $actualRefund['status']);

        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(false, $actualRefund['gateway_refunded']);
    }

    public function testGatewayVerifyPaymentNotFound()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['status'] = 'authorize_failed';
            }
        });

        $this->makeRequestAndCatchException(function()
        {
            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === 'verify_content')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['status'] = 'authorize_failed';
            }
        });

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['status'], 'failed');
        $this->assertSame($response['payment']['verified'], 1);
        $this->assertSame($response['gateway']['status'], 'status_match');
        $this->assertSame($response['gateway']['gateway'], 'cybersource');
        $this->assertSame($response['gateway']['gatewayPayment']['status'], 'authorize_failed');
    }

    public function testVerifyDataUpdation()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['gateway_reference_id1']  = '5474993075916772203012';
            }
        });

        $this->makeRequestAndCatchException(function()
        {
            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);
        $cybsEntity = $this->getLastEntity('cybersource', true);

        $this->assertEquals('5474993075916772203012', $cybsEntity['ref']);

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === 'verify_content')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['status'] = 'authorize_failed';
                $content['data']['gateway_reference_id1']  = '5474993075916772203013';
            }
        });

        $response = $this->verifyPayment($payment['id']);

        $cybsEntity = $this->getLastEntity('cybersource', true);
        $this->assertEquals('5474993075916772203013', $cybsEntity['ref']);

        $this->assertSame($response['payment']['verified'], 1);
        $this->assertSame($response['gateway']['status'], 'status_match');
    }

    public function testVerifyMisMatch()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['gateway_reference_id1']  = '5474993075916772203012';
            }
        });

        $this->makeRequestAndCatchException(function()
        {
            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);
        $cybsEntity = $this->getLastEntity('cybersource', true);

        $this->assertEquals('5474993075916772203012', $cybsEntity['ref']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        },
        Exception\PaymentVerificationException::class);


        $payment = $this->getLastEntity('payment', true);
        $cybsEntity = $this->getLastEntity('cybersource', true);

        $this->assertEquals('5470653499446597903009', $cybsEntity['ref']);

        $this->assertSame($payment['verified'], 0);
    }

    public function testAuthorizeFailedPayment()
    {
        $enrolledCard = [
            'card' => [
                'number'    => '4000000000000002',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2024',
                'cvv'               => '566',
            ]
        ];

        $defaultPayment = $this->getDefaultPaymentArray();

        $payment = array_merge($defaultPayment, $enrolledCard);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'GATEWAY_ERROR_TIMED_OUT';
                $content['data']['xid'] = null;
            }
        });

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['gateway'], 'cybersource');
        $this->assertEquals($payment['internal_error_code'], 'GATEWAY_ERROR_TIMED_OUT');

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');
        $this->assertNull($payment['internal_error_code']);
        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertNotNull($cybersource['ref']);
        $this->assertTestResponse($cybersource);
    }

    public function testGatewayTimeOutAtCapture()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'capture')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'GATEWAY_ERROR_TIMED_OUT';
                $content['data'] = null;
            }
        });

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->doAuthAndCapturePayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('cybersource', $payment['gateway']);

        $this->assertEquals(3, count($this->getDbEntities('cybersource')));

        $cybs = $this->getLastEntity('cybersource', true);

        $this->assertEquals($payment['id'], 'pay_'.$cybs['payment_id']);
        $this->assertEquals('capture', $cybs['action']);
        $this->assertEquals('created', $cybs['status']);
    }

    public function testGatewayPaymentXidMisMatch()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000002';

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth_verify')
            {
                $content['data']['xid'] = 'random_xid';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentInvalidEci()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth_init')
            {
                $content['data']['eci'] = null;
            }

            if ($action === 'auth_verify')
            {
                $content['data']['eci'] = null;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentInternalServerError()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['success'] = false;
            $content['error']['description'] = 'Dummy Server Error';
            $content['error']['internal_error_code'] = 'SERVER_ERROR_RUNTIME_ERROR';
            $content['error']['gateway_error_code'] = '';
            $content['error']['gateway_error_desc'] = '';
            $content['error']['gateway_status_code'] = '0';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentValidationError()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['success'] = false;
            $content['error']['description'] = 'Dummy Validation Error';
            $content['error']['internal_error_code'] = 'BAD_REQUEST_VALIDATION_FAILURE';
            $content['error']['gateway_error_code'] = '';
            $content['error']['gateway_error_desc'] = '';
            $content['error']['gateway_status_code'] = '0';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentRouteNotFoundError()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['success'] = false;
            $content['error']['description'] = 'Dummy Route Not Found Error';
            $content['error']['internal_error_code'] = 'BAD_REQUEST_URL_NOT_FOUND';
            $content['error']['gateway_error_code'] = '';
            $content['error']['gateway_error_desc'] = '';
            $content['error']['gateway_status_code'] = '0';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentGatewayErrorRequestError()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['success'] = false;
            $content['error']['description'] = 'Dummy Gateway Error';
            $content['error']['internal_error_code'] = 'GATEWAY_ERROR_REQUEST_ERROR';
            $content['error']['gateway_error_code'] = '';
            $content['error']['gateway_error_desc'] = '';
            $content['error']['gateway_status_code'] = '0';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentCustomValidationError()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['success'] = false;
            $content['error']['description'] = 'Dummy Route Not Found Error';
            $content['error']['internal_error_code'] = 'SERVER_ERROR_LOGICAL_ERROR';
            $content['error']['gateway_error_code'] = '';
            $content['error']['gateway_error_desc'] = '';
            $content['error']['gateway_status_code'] = '0';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentGatewayErrorChecksumError()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['success'] = false;
            $content['error']['description'] = 'Dummy Route Not Found Error';
            $content['error']['internal_error_code'] = 'GATEWAY_ERROR_CHECKSUM_MATCH_FAILED';
            $content['error']['gateway_error_code'] = '';
            $content['error']['gateway_error_desc'] = '';
            $content['error']['gateway_status_code'] = '0';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayInvalidReasonCode()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            $content['success'] = false;
            $content['error']['internal_error_code'] = null;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCpsGatewayEntitySync()
    {
        $payment = $this->fixtures->create('payment:status_created');

        $gatewayData = [
            'mode'       => 'test',
            'timestamp'  => 294832,
            'payment_id' => $payment->getId(),
            'gateway'    => 'cybersource',
            'input'      => [
                'payment'       => [
                    'id'       => $payment->getId(),
                    'amount'   => 500000,
                    'currency' => 'INR',
                ],
                'terminal'      => [
                    'gateway_acquirer' => 'hdfc',
                ],
                'action'   => 'authorize',
            ],
            'gateway_transaction'       => [
                'payment_id'    => $payment->getId(),
                'acquirer'      => 'hdfc',
                'action'        => 'authorize',
                'received'      => false,
                'amount'        => 50000,
                'currency'      => 'INR',
                'status'        => 'created',
                'xid'           => 'aFM3NktkemM4OW1sSGNoOERXUzE=',
                'veresEnrolled' => 'Y',
                'ref'           => '466146845543214129700',
                'reason_code'   => 475,
            ],
        ];

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($cybersource['status'], 'created');

        $gatewayData['gateway_transaction']['status'] = 'authenticated';

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($cybersource['status'], 'authenticated');
    }

    public function testCpsGatewayEntitySyncReverseOrder()
    {
        $payment = $this->fixtures->create('payment:status_created');

        $gatewayData = [
            'mode'       => 'test',
            'timestamp'  => 1556616468,
            'payment_id' => $payment->getId(),
            'gateway'    => 'cybersource',
            'input'      => [
                'payment'       => [
                    'id'       => $payment->getId(),
                    'amount'   => 500000,
                    'currency' => 'INR',
                ],
                'terminal'      => [
                    'gateway_acquirer' => 'hdfc',
                ],
                'action'   => 'authorize',
            ],
            'gateway_transaction'       => [
                'payment_id'    => $payment->getId(),
                'acquirer'      => 'hdfc',
                'action'        => 'authorize',
                'received'      => false,
                'amount'        => 50000,
                'currency'      => 'INR',
                'status'        => 'authenticated',
                'xid'           => 'aFM3NktkemM4OW1sSGNoOERXUzE=',
                'veresEnrolled' => 'Y',
                'ref'           => '466146845543214129700',
                'reason_code'   => 475,
            ],
        ];

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($cybersource['status'], 'authenticated');

        // This message entity shouldn't get updated 'cause
        // timestamp is less than previous one
        $gatewayData['gateway_transaction']['status'] = 'created';
        $gatewayData['timestamp'] = 1556616460;

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($cybersource['status'], 'authenticated');
    }

    public function testGatewayVerifyAuthResponseFailure()
    {$this->markTestSkipped();
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            $content['reasonCode'] = 202;
            $content['decision'] = 'REJECT';
            $content['ccAuthReply'] = [
                'reasonCode' => 202
            ];
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testSoapFaultException()
    {$this->markTestSkipped();
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            throw new \SoapFault('HTTP', 'Random SoapFault Exception');
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentAuthenticateCard()
    {$this->markTestSkipped();
        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity['terminal_id']);

        $token = $paymentEntity['token_id'];

        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('2RecurringTerm', $paymentEntity['terminal_id']);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($paymentId);

        $cybersource = $this->getLastEntity('cybersource', true);

        $cybersourceData = $this->testData['cybersourceRecurringEntity'];

        $this->assertNotNull($cybersource['ref']);
        $this->assertNotNull($cybersource['authorizationCode']);
        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertArraySelectiveEquals($cybersourceData, $cybersource);
    }

    public function testManualGatewayCapture()
    {$this->markTestSkipped();
        $paymentData = $this->defaultAuthPayment();

        $payment = $this->fixtures->payment->edit($paymentData['id'], [
            'status' => 'captured',
            'captured_at' => time()
        ]);

        list($txn, $feeSplit) = $this->createTransactionForPaymentAuthorized($payment);
        $txn->saveOrFail();
        $payment->saveOrFail();

        $data = [
            'request' => [
                'url' => '/payments/'.$payment['public_id'] . '/gateway/capture',
                'method' => 'POST',
            ],

            'response' => [
                'content' => [
                    'payment_id' => $payment['id'],
                    'result'     => true
                ]
            ]
        ];

        $this->ba->adminAuth();

        $this->runRequestResponseFlow($data);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('capture', $cybersource['action']);
        $this->assertEquals('captured', $cybersource['status']);
    }

    public function testStatusAfterFailedAutoCapturePayment()
    {$this->markTestSkipped();
        $order = $this->fixtures->create('order:payment_capture_order');

        $this->mockServerContentFunction(function($input, $action)
        {
            if ($action === 'validate_capture')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid Capture');
            }
        });

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order['amount'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['captured_at']);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals(false, $payment['auto_captured']);
        $this->assertEquals('authorized', $payment['status']);

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(true, $order['authorized']);
    }

    // @todo: refactor
    protected function transaction(callable $callable)
    {
        $db = \DB::getFacadeRoot();

        return $db->transaction($callable);
    }

    // -------- helpers ----------

    protected function assertRefundAmount($expectedAmount)
    {
        $this->mockServerContentFunction(function($content, $action = null) use ($expectedAmount)
        {
            if ($action === 'validate_refund')
            {
                $actualRefundAmount = (int) ($content['purchaseTotals']['grandTotalAmount'] * 100);

                $assertion = ($actualRefundAmount === $expectedAmount);

                $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
            }
        });
    }

    protected function mockTimeout($type = 'gateway')
    {
        $this->mockServerContentFunction(function(&$content, $action = null) use ($type)
        {
            if ($action === 'enrollment')
            {
                if ($type === 'gateway')
                {
                    throw new \SoapFault('HTTP', 'Error Fetching http headers');
                }

                if ($type === 'processor')
                {
                    $content['decision'] = 'REJECT';
                    $content['reasonCode'] = 151;
                    $content['payerAuthEnrollReply'] = [
                        'reasonCode' => 151
                    ];

                    unset($content['purchaseTotals']);
                }
            }
        });
    }

    protected function mockRefundTimeout($type = 'gateway')
    {
        $this->mockServerContentFunction(function(&$content, $action = null) use ($type)
        {
            if ($action === 'refund')
            {
                if ($type === 'processor')
                {
                    $content['decision'] = 'ERROR';
                    $content['reasonCode'] = 150;
                    $content['ccCreditReply'] = [
                        'reasonCode' => 150
                    ];

                    unset($content['purchaseTotals']);
                }
            }

            if ($action === 'verify_refund_fail')
            {
                if ($type === 'processor')
                {
                    $content['success'] = false;
                    $content['data']['reason_code'] = 102;
                    $content['data']['r_flag'] = 'SNOTOK';
                    $content['data']['r_code'] = '0';

                    unset($content['purchaseTotals']);
                }
            }
        });
    }

    public function testGatewayAuthorizedPaymentVerifyFailure()
    { $this->markTestSkipped();
        $payment = $this->doAuthPayment();

        $this->fixtures->base->editEntity(
            'payment', $payment['razorpay_payment_id'], ['authorized_at' => strtotime('-1 min')]);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'verify_xml')
            {

            }
        });

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->verifyPayment($payment['razorpay_payment_id']);
            },
            \RZP\Exception\GatewayTimeoutException::class);
    }

    public function testAuthorizedPaymentRefundWithVerifyV2Disabled()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal_without_secret2');

        $this->fixtures->merchant->addFeatures('reverse');

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);

        $this->assertTestResponse($refund);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('reverse', $cybersource['action']);
    }

    public function testGatewayRefundVerifyWithV2Disabled()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal_without_secret2');

        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);

        $this->mockServerContentFunction(function(&$xml, $action) use ($refund)
        {
            if ($action === 'verify_xml')
            {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE Report SYSTEM "https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd">
<Report xmlns="https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd" Name="Transaction Detail" Version="1.1" MerchantID="razorpaycybs" ReportStartDate="2017-04-20 11:33:58.208+05:30" ReportEndDate="2017-04-20 11:33:58.208+05:30">
  <Requests>
    <Request MerchantReferenceNumber="'.$refund['id'].'" RequestDate="2017-04-04T00:01:12+05:30" RequestID="4912442722396160004013" SubscriptionID="" Source="SOAP Toolkit API">
      <BillTo>
        <FirstName />
        <LastName />
        <City />
        <Email />
        <Country />
        <Phone />
      </BillTo>
      <PaymentMethod>
        <Card>
          <AccountSuffix>8371</AccountSuffix>
          <ExpirationMonth>4</ExpirationMonth>
          <ExpirationYear>2018</ExpirationYear>
          <CardType>MasterCard</CardType>
        </Card>
      </PaymentMethod>
      <LineItems>
        <LineItem Number="0">
          <FulfillmentType />
          <Quantity>1</Quantity>
          <UnitPrice>2267.00</UnitPrice>
          <TaxAmount>0.00</TaxAmount>
          <ProductCode>default</ProductCode>
        </LineItem>
      </LineItems>
      <ApplicationReplies>
        <ApplicationReply Name="ics_credit">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>Request was processed successfully.</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <PaymentRequestID>4912442722396160004013</PaymentRequestID>
        <PaymentProcessor>vdcaxis</PaymentProcessor>
        <Amount>'. $refund['amount'] / 100 .'</Amount>
        <CurrencyCode>INR</CurrencyCode>
        <TotalTaxAmount>0.00</TotalTaxAmount>
        <AuthorizationCode>292540</AuthorizationCode>
      </PaymentData>
      <MerchantDefinedData>
        <field1>1</field1>
        <field2>'. $refund['payment_id'] .'</field2>
      </MerchantDefinedData>
    </Request>
  </Requests>
</Report>
';
            }
        });

        Carbon::setTestNow($time);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);
    }
}
