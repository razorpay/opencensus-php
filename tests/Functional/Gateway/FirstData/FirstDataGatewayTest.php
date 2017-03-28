<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\FirstData\Gateway;
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

        Gateway::setTestChance(4);
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
        $this->assertEquals('1FrstDtRcrTrml', $paymentEntity['terminal_id']);

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        // Switch to private auth for second recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2SRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];
        $this->capturePayment($paymentId, $payment['amount']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('2FrstDtRcrTrml', $paymentEntity['terminal_id']);

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $firstDataEntity = $this->getLastEntity('first_data', true);

        $this->assertEquals($paymentId, $firstDataEntity['payment_id']);
    }

    public function testPaymentAuthAndCapture()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->capturePayment($authResponse['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }

    public function testMaestroCard()
    {
        $payment = $this->payment;

        $payment['card']['number'] = '5081597022059105';

        $this->doAuthAndCapturePayment($payment);

        $paymentRes = $this->getLastPayment(true);

        $this->assertEquals($paymentRes['gateway'], 'first_data');
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
        $this->assertNotEquals($paymentRes['gateway'], 'first_data');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 1);
    }

    public function testPaymentRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'refunded');

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

        $this->assertEquals($payment['status'], 'captured');

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

        $this->assertEquals($payment['status'], 'refunded');

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals($gatewayPayment['action'], 'reverse');
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

        $this->assertEquals($gatewayPayment['approval_code'], "N:mocked failure approval code");
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

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
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
