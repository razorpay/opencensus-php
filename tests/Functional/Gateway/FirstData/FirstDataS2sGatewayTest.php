<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use RZP\Gateway\FirstData\Mock;
use RZP\Gateway\FirstData\Action;
use RZP\Gateway\FirstData\Status;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\FirstData\SoapWrapper;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FirstDataS2sGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/FirstDataS2sGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(Constants::FIRST_DATA_S2S_FLOW);

        $this->gateway = 'first_data';

        $this->payment = $this->getDefaultPaymentArray();

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
    }

    public function testPaymentAuthAndCaptureForPurchaseModeTerminal()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal', [
            'id' => '1000FrstDataTk',
            'mode' => '2'
        ]);

        $this->mockServerRequestFunction(
            function(& $request)
            {
                $requestArray = $this->parseXmlAndReturnArray($request);

                $txnType =  $requestArray['SOAP-ENVBody']['ipgapiIPGApiOrderRequest']
                            ['v1Transaction']['v1CreditCardTxType']['v1Type'];

                $this->assertEquals('sale', $txnType);
            });

        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue( $payment['gateway_captured']);

        $this->assertNotNull($payment['transaction_id']);
    }

    protected function runPaymentCallbackFlowFirstData($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

            return $this->submitPaymentCallbackRequest($request);
        }
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

    public function testS2sCallbackSwitch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === Action::CALLBACK)
            {
                $content['MD']      = 'merchant_identifier';
                $content['PaRes']   = 'random_string';
            }
        });

        $this->doAuthPayment();

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('50000', $payment['amount']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayPayment['payment_id']);

        $this->assertEquals('APPROVED', $gatewayPayment['transaction_result']);

        $this->assertNotNull($gatewayPayment['approval_code']);
    }

    public function testNotEnrolledFailed()
    {
        $this->payment['card']['number'] = Mock\Constants::DOMESTIC_NOT_ENROLLED_CARD;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testNotEnrolledSuccess()
    {
        $this->payment['card']['number'] = Mock\Constants::INTERNATIONAL_CARD;

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('50000', $payment['amount']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayPayment['gateway_payment_id']);

        $this->assertEquals('APPROVED', $gatewayPayment['transaction_result']);

        $this->assertNotNull($gatewayPayment['approval_code']);
    }

    public function testAuthorizationFailed()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === Action::CALLBACK)
            {
                $content['status'] = 'DECLINED';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('50000', $payment['amount']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayPayment['payment_id']);

        $this->assertEquals('FAILED', $gatewayPayment['transaction_result']);

        $this->assertNotNull($gatewayPayment['approval_code']);
    }

    public function testEmptyApprovalCode()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === Action::CALLBACK)
            {
                unset($content['ipgapi:ApprovalCode']);
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testVerifyPayment()
    {
        $payment = $this->doAuthPayment($this->payment);

        $gatewayEntity = $this->getDbEntity('first_data', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertEquals('AUTHORIZED', $gatewayEntity['status']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $gatewayEntity = $this->getLastEntity('first_data', true);

        $this->assertEquals('CAPTURED', $gatewayEntity['status']);

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'verify_action')
            {
                $content = true;
            }
        });

        $this->verifyPayment($payment['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testPaymentCancelledOnAcsPage()
    {
        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === Action::CALLBACK)
            {
                $content['status'] = 'DECLINED';
            }
        });

        $data = $this->testData['testAuthorizationFailed'];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals($gatewayPayment['transaction_result'], 'FAILED');
    }

    public function testRefundOnPayment()
    {
        $payment = $this->testPaymentAuthAndCapture();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('refunded', $payment['status']);

        $this->assertEquals($gatewayPayment['status'], 'CAPTURED');

        $this->assertEquals($refund['id'],'rfnd_'. $gatewayPayment['refund_id']);
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

    public function testEnrollFailedWithDifferentXml()
    {
        $this->payment['card']['number'] = Mock\Constants::DOMESTIC_CARD_INSUFFICIENT_BALCANCE;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testFirstAndSecondRecurringPayment()
    {
        list($terminal1, $terminal2) = $this->fixtures->
                                            create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->mockCardVault();

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $gatewayEntity = $this->getDbEntity('first_data', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertEquals(Status::AUTHORIZED, $gatewayEntity['status']);

        $this->capturePayment($paymentId, $payment['amount']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $gatewayEntity = $this->getLastEntity('first_data', true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $paymentEntity['terminal_id']);

        $token = $this->getLastEntity('token', true);
        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $token['terminal_id']);

        $this->assertEquals(Status::CAPTURED, $gatewayEntity['status']);
        $this->assertEquals($paymentEntity['amount'], $gatewayEntity['amount']);

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

        $gatewayEntity = $this->getLastEntity('first_data', true);

        $token = $this->getLastEntity('token', true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $paymentEntity['terminal_id']);
        $this->assertNotNull($paymentEntity['transaction_id']);

        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $token['terminal_id']);

        $this->assertEquals('APPROVED', $gatewayEntity['transaction_result']);
        $this->assertEquals('CAPTURED', $gatewayEntity['status']);

        $this->mockServerContentFunction(function(& $content, $action) use ($payment)
        {
            $content = SoapWrapper::s2sSecondRecurringVerifyResponseWrapper();
        });

        $this->verifyPayment($paymentId);

        $this->capturePayment($paymentId, $payment['amount']);
    }

    public function testFirstAndSecondRecurringPaymentWithSingleDigitMonth()
    {
        list($terminal1, $terminal2) = $this->fixtures->
        create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->mockCardVault();

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['expiry_month'] = '4';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $gatewayEntity = $this->getDbEntity('first_data', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertEquals(Status::AUTHORIZED, $gatewayEntity['status']);

        $this->capturePayment($paymentId, $payment['amount']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $gatewayEntity = $this->getLastEntity('first_data', true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $paymentEntity['terminal_id']);

        $token = $this->getLastEntity('token', true);
        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmnl3DS', $token['terminal_id']);

        $this->assertEquals(Status::CAPTURED, $gatewayEntity['status']);
        $this->assertEquals($paymentEntity['amount'], $gatewayEntity['amount']);

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

        $gatewayEntity = $this->getLastEntity('first_data', true);

        $token = $this->getLastEntity('token', true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $paymentEntity['terminal_id']);
        $this->assertNotNull($paymentEntity['transaction_id']);

        $this->assertEquals($paymentEntity['token_id'], $token['id']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('FDRcrgTrmlN3DS', $token['terminal_id']);

        $this->assertEquals('APPROVED', $gatewayEntity['transaction_result']);
        $this->assertEquals('CAPTURED', $gatewayEntity['status']);

        $this->mockServerContentFunction(function(& $content, $action) use ($payment)
        {
            $content = SoapWrapper::s2sSecondRecurringVerifyResponseWrapper();
        });

        $this->verifyPayment($paymentId);

        $this->capturePayment($paymentId, $payment['amount']);
    }

    public function testRecurringPaymentFailed()
    {
        list($terminal1, $terminal2) = $this->fixtures->
                                            create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->mockCardVault();

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = Mock\Constants::DOMESTIC_NOT_ENROLLED_CARD;

        $data = $this->testData['testNotEnrolledFailed'];

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    protected function getErrorInReturn()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['ApprovalCode']      = 'N:-5008:Order does not exist.';
            $content['TransactionResult'] = 'FAILED';
        });
    }

    protected function parseXmlAndReturnArray($xml)
    {
        $xml = preg_replace('/(<\/?)(\w+-*\w+):([^>]*>)/', '$1$2$3', $xml);

        $formattedXml = simplexml_load_string($xml);

        $responseArray = json_decode(json_encode($formattedXml), true);

        return $responseArray;
    }
}
