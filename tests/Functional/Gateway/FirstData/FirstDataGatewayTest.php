<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FirstDataGatewayTest extends TestCase
{
    use PaymentTrait;

    /**
     * Instance of a terminal from the fixtures
     * @var Terminal
     */
    protected $sharedTerminal;

    /**
     * The payment array
     * @var array
     */
    protected $payment;

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
        list($terminal1, $terminal2) = $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');
        $this->mockTokenex();

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];
        $this->capturePayment($paymentId, $payment['amount']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $paymentEntity['terminal_id']);

        $token = $this->getLastEntity('token', true);
        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $token['terminal_id']);

        $this->mockServerRequestFunction(function ($body) use ($terminal1)
        {
            $hostedDataStoreId = $body['Transaction']['Payment']['HostedDataStoreID'];

            $this->assertEquals(
                $terminal1->getGatewayMerchantId(),
                $hostedDataStoreId,
                'wrong MID sent for recurring payment request');
        });

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

        $token = $this->getLastEntity('token', true);
        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $token['terminal_id']);

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

        $token = $this->getLastEntity('token', true);
        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $token['terminal_id']);

        $gatewayToken = $this->getLastEntity('gateway_token', true);
        $this->assertEquals($token['id'], 'token_'.$gatewayToken['token_id']);
        $this->assertEquals('FDRcrgTrmlN3DS', $gatewayToken['terminal_id']);

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

    public function testVerifyRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->getErrorInReturn();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $refundId = explode('_', $refund['id'], 2)[1];

        $this->clearMockFunction();

        $response = $this->retryFailedRefunds();

        $actualRefund = $this->getEntityById('refund', $refundId, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(2, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('CAPTURED', $firstData['status']);
    }

    public function testVerifyRefundFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->getErrorInReturn();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $refundId = explode('_', $refund['id'], 2)[1];

        $this->getErrorInVerifyRefund();

        $response = $this->retryFailedRefunds();

        $actualRefund = $this->getEntityById('refund', $refundId, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('failed', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(false, $actualRefund['gateway_refunded']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);
    }

    public function testVerifyReverse()
    {
        $payment = $this->doAuthPayment();

        $this->getErrorInReturn();

        $this->refundAuthorizedPayment($payment['razorpay_payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $refundId = explode('_', $refund['id'], 2)[1];

        $this->clearMockFunction();

        $response = $this->retryFailedRefunds();

        $actualRefund = $this->getEntityById('refund', $refundId, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(2, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('CAPTURED', $firstData['status']);
    }

    public function testMaestroCard()
    {
        $payment = $this->payment;

        $payment['card']['number'] = '5081597022059105';

        $this->doAuthAndCapturePayment($payment);

        $paymentRes = $this->getLastPayment(true);

        $this->assertEquals('first_data', $paymentRes['gateway']);
    }

    public function testIciciDebitCard()
    {
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $payment = $this->payment;

        $payment['card']['number'] = '6074667022059103';

        $this->fixtures->create('iin',
            [
                'iin'    => '607466',
                'issuer' => 'ICIC',
                'type'   => 'debit',
            ]);

        $this->doAuthPayment($payment);

        $paymentRes = $this->getLastPayment(true);
        
        $transRes = $this->getLastTransaction(true);

        // FirstData now should get selected
        $this->assertEquals('first_data', $paymentRes['gateway']);
        $this->assertEquals($transRes['entity_id'], $paymentRes['id']);


        $payment['card']['number'] = '5109591717594888';

        $this->fixtures->create('iin',
            [
                'iin'    => '510959',
                'issuer' => 'ICIC',
                'type'   => 'credit',
            ]);

        $this->doAuthPayment($payment);

        $paymentRes = $this->getLastPayment(true);

        // FirstData gets selected now as credit cards are not filtered
        $this->assertEquals('first_data', $paymentRes['gateway']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['chargetotal'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment();
        });
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

    public function testSetCapsPaymentId()
    {
        $payment = $this->payment;

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $firstData = $this->getLastEntity('first_data', true);

        $paymentId = explode('_', $payment['id'])[1];

        $this->assertEquals(strtoupper($paymentId), $firstData['caps_payment_id']);
    }

    public function testAuthCodeMappingFromApprovalCode()
    {
        $sampleAuthCode = random_integer(6);

        $this->getOveriddenApprovalCode("Y:$sampleAuthCode:PPX: 233123");

        $this->doAuthPayment($this->payment);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($sampleAuthCode, $gatewayPayment['auth_code']);

        $this->assertEquals($sampleAuthCode, $payment['reference2']);
    }

    public function testAuthCodeMapForVerifyPayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        // The value is hardcoded in SoapWrapper,
        // it also makes sure that the first TransactionValues is picked if there are many
        $this->assertEquals('543210', $gatewayPayment['auth_code']);
    }

    public function testPaymentForMissingIin()
    {
        $iinCode = '466522';

        $iin = $this->getEntityById('iin', $iinCode);
        $this->assertArrayHasKey('error', $iin);

        $this->payment['card']['number'] = $iinCode . '00000000000';

        $this->doAuthPayment($this->payment);
    }
}
