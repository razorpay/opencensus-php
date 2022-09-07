<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use \RZP\Error\ErrorCode;
use RZP\Gateway\FirstData\SoapWrapper;

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

    /**
     * This file covers testing on the old flow that is supported by firstdata.
     * Currently rupay cards will go through old flow.
     */
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/FirstDataGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'first_data';

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '6522622211727786';

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
    }

    public function testRecurringPayment()
    {
        list($terminal1, $terminal2) = $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');
        $this->mockCardVault();

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

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        // Switch to private auth for second recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($response['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

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
        $this->assertNotNull($firstDataEntity['approval_code']);
        $this->assertNotNull($firstDataEntity['status']);

        // Another payment to test auto-refund
        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($response['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

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

    public function testFailedSecondRecurringPayment()
    {
        list($terminal1, $terminal2) = $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');
        $this->mockCardVault();

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

        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT);
        });

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        // Switch to private auth for second recurring payment

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $content = $this->doS2sRecurringPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($content['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $lastPayment = $this->getLastEntity('Payment', true);
        $this->assertEquals($lastPayment['status'], 'failed');
        $this->assertNotNull($lastPayment['amount']);

        $gatewayEntity = $this->getLastEntity('first_data', true);
        $this->assertNull($gatewayEntity['transaction_result']);
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

        return $payment;
    }

    public function testVerifyRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->getErrorInReturn();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $statusToBeAsserted = ($refund['is_scrooge'] === true) ? 'created' : 'failed';

        $this->assertEquals($statusToBeAsserted, $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $refundId = explode('_', $refund['id'], 2)[1];

        $this->clearMockFunction();

        $this->getFailureInVerifyRefund($refundId);

        //TODO: Check for retry flow
        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $actualRefund = $this->getEntityById('refund', $refundId, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('processed', $actualRefund['status']);

        $this->assertEquals(1, $actualRefund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_'.$firstData['refund_id']);

        if ($refund['is_scrooge'] === false)
        {
            $this->assertEquals('CAPTURED', $firstData['status']);
        }
    }

    public function testVerifyRefundFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->getErrorInReturn();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $statusToBeAsserted = ($refund['is_scrooge'] === true) ? 'created' : 'failed';

        $this->assertEquals($statusToBeAsserted, $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $refundId = explode('_', $refund['id'], 2)[1];

        $this->getFailureInVerifyRefund($refund['id']);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $actualRefund = $this->getEntityById('refund', $refundId, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_' . $firstData['refund_id']);
        $this->assertEquals('CAPTURED', $firstData['status']);
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->getErrorInReturn();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $statusToBeAsserted = ($refund['is_scrooge'] === true) ? 'created' : 'failed';

        $this->assertEquals($statusToBeAsserted, $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$firstData['refund_id']);
        $this->assertEquals('FAILED', $firstData['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $refundId = explode('_', $refund['id'], 2)[1];

        $this->getSuccessInVerifyRefund();

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $actualRefund = $this->getEntityById('refund', $refundId, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('processed', $actualRefund['status']);

        $this->assertEquals(true, $actualRefund['gateway_refunded']);

        $firstData = $this->getLastEntity('first_data', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_' . $firstData['refund_id']);
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
            $this->doAuthPayment($this->payment);
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

        $this->runRequestResponseFlow($data,
            function() use ($payment) {
                $this->refundPayment($payment['id']);
            });
    }

    public function testPaymentReverse()
    {
        $this->markTestSkipped('reverse has been disabled due to issue on first data');

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

        $this->assertEquals('N:mocked failure approval code', $gatewayPayment['approval_code']);
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
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4160210902353047';

        $this->getErrorInCapture();
        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];


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
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4160210902353047';

        $this->doAuthPayment($payment);

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

    public function testCaptureGatewayRequestExceptionRetry()
    {
        $this->markTestSkipped('retry handler moved to upi_sbi');

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->getGatewayRequestException();

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $this->i);

        $this->assertEquals('captured', $payment['status']);
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

    public function testTransactionTimedOut()
    {
        $time_out_error_codes = ['N:-30052', 'N:-30053', 'N:-7778'];

        foreach ($time_out_error_codes as $error_code)
        {
            $this->getErrorTransactionTimedout($error_code);

            $data = $this->testData[__FUNCTION__];

            $this->runRequestResponseFlow($data, function() {
                $this->doAuthPayment($this->payment);
            });

        }
    }

    public function testMinimumCardNameLimit()
    {
        $this->payment['card']['name'] = 'A ';

        $this->mockServerContentFunction(
            function($content)
            {
                if (is_array($content) === true)
                {
                    $this->assertSame('AXX', $content['bname']);
                }
            });

        $this->doAuthPayment($this->payment);
    }

    public function testLongApprovalCode()
    {
        $longApprovalCodeArray = [
            'N',
            '03',
            'Timeout expired. The timeout period elapsed prior to obtaining a connection from the pool.'.
                'This may have occurred because all pooled connections were in use and max pool size was reached.'
        ];

        $this->getOveriddenApprovalCode(implode(':', $longApprovalCodeArray));

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            Exception\GatewayErrorException::class,
            // Error code for N:03 is mapped to Invalid Merchant
            'The payment has been rejected by the gateway.' .
                "\nGateway Error Code: N:03\nGateway Error Desc: Invalid merchant");
    }

    public function testInvalidApprovalCode()
    {
        $invalidApprovalCode = 'Invalid code';

        $this->getOveriddenApprovalCode($invalidApprovalCode);

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            Exception\GatewayErrorException::class,
            // Any invalid code is mapped to General Error
            'Payment processing failed due to error at bank or wallet gateway' .
            "\nGateway Error Code: Invalid code\nGateway Error Desc: General Error");
    }

    public function testWaitingRupayCode()
    {
        $ApprovalCode = '?:waiting RUPAY';

        $this->getOveriddenApprovalCode($ApprovalCode);

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            Exception\GatewayErrorException::class,
            // Any invalid code is mapped to General Error
            'Payment was not completed on time.' .
            "\nGateway Error Code: ?:waiting RUPAY\nGateway Error Desc: Waiting for Rupay");
    }

    public function testBadRequestPaymentTimedOut()
    {
        $payment = $this->testPaymentAuthAndCapture();

        $payment = $this->fixtures->edit('payment', $payment['id'],
                                                ['internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
                                                 'status' => 'created']);

        $this->mockServerContentFunction(function(& $content, $action) use ($payment)
        {
            $content = SoapWrapper::verifyResponseWrapper($payment['id'], 'N:-30051', 'WAITING');
        });

        $this->verifyPayment('pay_' . $payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('GATEWAY_ERROR_COMMUNICATION_ERROR', $payment['internal_error_code']);

        $this->assertEquals('Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.', $payment['error_description']);
    }
}
