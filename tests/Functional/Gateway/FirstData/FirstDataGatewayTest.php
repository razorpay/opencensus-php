<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FirstDataGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/FirstDataGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'first_data';

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testRecurringPayment()
    {
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('recurring');
        $this->mockTokenex();

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];
        $this->capturePayment($paymentId, $payment['amount']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $paymentEntity['terminal_id']);

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        // Switch to private auth for second recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $paymentEntity['terminal_id']);
        $this->assertNotNull($paymentEntity['transaction_id']);

        // Transaction created at auth step itself, as recurring payment is a purchase request
        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($paymentEntity['id'], $transaction['entity_id']);

        $this->capturePayment($paymentId, $payment['amount']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('captured', $paymentEntity['status']);

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $firstDataEntity = $this->getLastEntity('first_data', true);
        $this->assertEquals($paymentId, $firstDataEntity['payment_id']);

        // Another payment to test auto-refund
        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];
        $this->refundAuthorizedPayment($paymentId);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('first_data', true);
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
    }

    public function testPaymentAuthAndCapture()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($authResponse['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('passed', $payment['two_factor_auth']);
    }

    public function testMaestroCard()
    {
        $payment = $this->payment;

        $payment['card']['number'] = '5081597022059105';

        $this->doAuthAndCapturePayment($payment);

        $paymentRes = $this->getLastPayment(true);

        $this->assertEquals('first_data', $paymentRes['gateway']);
    }

    public function testIciciCardIsFiltered()
    {
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $payment = $this->payment;

        $payment['card']['number'] = '6074667022059103';

        $this->fixtures->create('iin',
            [
                'iin'    => '607466',
                'issuer' => 'ICIC',
            ]);

        $this->doAuthPayment($payment);

        $paymentRes = $this->getLastPayment(true);

        // FirstData is preferred over Sharp, but does not get selected
        // as ICICI cards are disabled on FirstData
        $this->assertNotEquals('first_data', $paymentRes['gateway']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testPaymentRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
    }

    public function testPaymentPartialRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($refund['payment_id'], $payment['public_id']);

        $this->assertEquals($refund['amount'], $amount);
    }

    public function testPaymentRefundWithoutCapture()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
    }

    public function testPaymentReverse()
    {
        $features = $this->fixtures->merchant->addFeatures(['reverse']);

        $payment = $this->doAuthPayment($this->payment);

        $this->refundAuthorizedPayment($payment['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals('reverse', $gatewayPayment['action']);
    }

    public function testPaymentAuthAndAlreadyCaptured()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->fixtures->create(
            'first_data',
            [
                'payment_id' => $gatewayPayment['payment_id'],
                'action'     => 'capture',
                'received'   => true,
                'amount'     => $gatewayPayment['amount'],
            ]
        );

        // Capture entity already exists. This is unexpected,
        // but capture should quietly succeed anyway.
        $this->capturePayment($authResponse['razorpay_payment_id'], $gatewayPayment['amount']);
    }

    public function testPaymentDoubleCapture()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->capturePayment($payment['id'], $payment['amount']);
        });
    }

    public function testFailedAuthPayment()
    {
        $this->getErrorInAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testNoApprovalCodeInAuthResponse()
    {
        $this->removeApprovalCodeInAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testNoApprovalCodeOrFailReason()
    {
        $this->removeApprovalCodeFailRc();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->doAuthPayment($this->payment);
        });

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals("N:mocked failure approval code", $gatewayPayment['approval_code']);
    }

    public function testFailedAuthUnknownError()
    {
        $this->getUnknownErrorInAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testFailedRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getErrorInReturn();

        $this->refundpayment($payment['id']);
    }

    public function testFailedCapture()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getErrorInCapture();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->capturePayment($payment['id'], $payment['amount']);
        });
    }

    public function testFailedVerifyMismatch()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getErrorInInquiry();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testMismatchVerify()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $this->fixtures->edit('payment', $authResponse['razorpay_payment_id'], ['status' => 'failed']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($authResponse) {
            $this->verifyPayment($authResponse['razorpay_payment_id']);
        });
    }

    public function testVerifyGatewayPaymentFailed()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->fixtures->edit('first_data', $gatewayPayment['id'], ['status' => 'failed']);

        $this->verifyPayment($authResponse['razorpay_payment_id']);
    }

    public function testCaptureTimeout()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getTimeoutInCapture();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->capturePayment($payment['id'], $payment['amount']);
        });

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals('authorize', $gatewayPayment['action']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testInvalidAuthFields()
    {
        $validatedFields = [
            'mode',
            'paymentMethod',
            'language',
            'currency',
            'hash_algorithm'
        ];

        $data = $this->testData[__FUNCTION__];

        foreach ($validatedFields as $field)
        {
            $this->setInvalidAuthField($field);

            $this->runRequestResponseFlow($data, function() {
                $this->doAuthPayment($this->payment);
            });
        }
    }
}
