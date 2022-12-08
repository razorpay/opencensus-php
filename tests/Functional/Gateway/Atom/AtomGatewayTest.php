<?php

use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Atom\RefundResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
class AtomGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/AtomGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->gateway = 'atom';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');
    }

    public function testNetbankingPaymentAuthorize()
    {
        $this->ba->publicAuth();

        $content = $this->doAuthPayment($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $gatewayEntity = $this->getLastEntity('atom', true);

        $this->assertEquals(1, $gatewayEntity['received']);

        $payment = $this->getLastPayment(true);

        $this->assertTestResponse($payment);
    }

    public function testSignatureNotFound()
    {
        $this->mockSignatureNotFound();

        $this->ba->publicAuth();

        $data = $this->testData['testFailedPayment'];

        $payment = $this->payment;

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testAmountNotound()
    {
        $this->ba->publicAuth();

        $data = $this->testData['testInvalidResponse'];

        $payment = $this->payment;

        $this->mockInvalidCallback('amt');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testStatusFieldNotPresent()
    {
        $data = $this->testData['testInvalidResponse'];

        $payment = $this->payment;

        $this->mockInvalidCallback('f_code');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testTransactionIDNotPresent()
    {
        $data = $this->testData['testInvalidResponse'];

        $payment = $this->payment;

        $this->mockInvalidCallback('mer_txn');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testNetbankingPaymentCapture()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertTestResponse($payment);
    }

    public function testSbiAssociatedNetbankingPaymentCapture()
    {
        $this->payment = $this->getDefaultNetbankingPaymentArray('SBBJ');

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $gatewayPayment = $this->getLastEntity('atom', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayPayment['payment_id']);

        $this->assertEquals('atom', $payment['gateway']);
    }

    public function testAtomVerifyPayment()
    {
        $this->setMockGatewayTrue();

        $this->payment['amount'] = '50020';

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $id = $payment['id'];

        $this->mockSetVerifyTransactionId();

        $data = $this->verifyPayment($id);

        $this->assertEquals($data['payment']['verified'], 1);
    }

    public function testNBPaymentOnSharedTerminal()
    {
        $this->merchant = $this->fixtures->create('merchant:with_keys');

        $this->ba->setDefaultKey('rzp_test_AltTestAuthKey')->publicAuth();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $this->assertPaymentAfterAuthAndCapture($payment);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertTestResponse($txn);
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['bank'] = 'SBIN';
        $this->payment['order_id'] = $order['id'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['bank_txn'] = '99999999';
            $content['bank_name'] = 'SBIN';
        });

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('atom', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals('00004030403040304',
                            $gatewayEntity['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    public function testFailedPayment()
    {
        //While using mockServerContentFunction mode is not setting to test
        $this->app['rzp.mode'] = 'test';

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content['f_code'] = 'F';
            }
        });

        $payment = $this->payment;

        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testVerifyFailedPayment()
    {
        $payment = $this->doAuthPayment($this->payment);

        $gatewayPayment = $this->getLastEntity('atom', true);

        $this->fixtures->edit('atom', $gatewayPayment['id'], ['status' => 'F']);

        $this->mockSetVerifyTransactionId();

        $this->verifyPayment($payment['razorpay_payment_id']);

        $gatewayPayment = $this->getLastEntity('atom', true);

        $this->assertEquals('Ok', $gatewayPayment['status']);

        $this->assertEquals(true, $gatewayPayment['success']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->testFailedPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['reference1']);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['reference1']);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testFailedVerifyMismatch()
    {
        $this->testFailedPayment();

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testPaymentRefund()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'verify_refund')
            {
                $content = '<REFUNDSTATUS>
                <ERRORCODE>EE</ERRORCODE>
                <MESSAGE>Refund Not Found</MESSAGE>
                 </REFUNDSTATUS>';
            }
        });

        $this->refundPayment($payment['id']);

        $gatewayRefund = $this->getLastEntity('atom', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertTestResponse($gatewayRefund);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayRefund['payment_id']);

        $this->assertEquals('rfnd_' . $gatewayRefund['refund_id'], $refund['id']);
    }

    public function testPaymentPartialRefund()
    {
        $this->ba->publicAuth();

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $amount = (int) ($payment['amount'] / 3);

        $this->mockServerContentFunction(function(& $content, $action) use ($amount)
        {
            if ($action === 'refund')
            {
                $actualRefundAmount = (int) ($content['refundAmount'] * 100);

                $assertion = ($actualRefundAmount === $amount);

                $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
            }
        });

        $this->refundPayment($payment['id'], $amount);

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($refund['payment_id'], $payment['public_id']);

        $this->assertEquals($refund['amount'], $amount);
    }

    public function testRefundFailed()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = '<REFUNDSTATUS>
                <ERRORCODE>EE</ERRORCODE>
                <MESSAGE>Refund Not Found</MESSAGE>
                 </REFUNDSTATUS>';
            }

            if ($action === 'refund')
            {
                $content['STATUSCODE']    = 'M1';
                $content['STATUSMESSAGE'] = 'Refund is not allowed';
            }
        });

        $this->refundPayment($payment['id']);

        $gatwayRefund = $this->getLastEntity('atom',true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RZP\Models\Payment\Refund\Status::CREATED, $refund['status']);

        $this->assertEquals(false, $gatwayRefund['success']);

        $this->assertEquals('M1', $gatwayRefund['error_code']);

        $this->assertEquals('Refund is not allowed', $gatwayRefund['gateway_result_description']);
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['STATUSCODE']         = 'M1';
                $content['STATUSMESSAGE']      = 'Refund is not allowed';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    public function testVerifyRefundForMultipleRefunds()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['STATUSCODE']         = 'M1';
                $content['STATUSMESSAGE']      = 'Refund is not allowed';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = '<REFUNDSTATUS>
	                    <ERRORCODE>00</ERRORCODE>
	                    <MESSAGE>Refund Found</MESSAGE>
	                    <DETAILS>
                        <REFUND>
                            <TXNID>300044506069</TXNID>
                            <PRODUCT>RAZORPAY</PRODUCT>
                            <REFUNDAMOUNT>100.0000</REFUNDAMOUNT>
                            <REFUNDINITIATEDATE>2019-06-06</REFUNDINITIATEDATE>
                            <REFUNDPROCESSDATE></REFUNDPROCESSDATE>
                            <REMARKS></REMARKS>
                            <MEREFUNDREF>randomrfnd1</MEREFUNDREF>
                        </REFUND>
                        <REFUND>
                            <TXNID>300044506070</TXNID>
                            <PRODUCT>RAZORPAY</PRODUCT>
                            <REFUNDAMOUNT>100.0000</REFUNDAMOUNT>
                            <REFUNDINITIATEDATE>2019-06-06</REFUNDINITIATEDATE>
                            <REFUNDPROCESSDATE></REFUNDPROCESSDATE>
                            <REMARKS></REMARKS>
                            <MEREFUNDREF>randomrfnd2</MEREFUNDREF>
                        </REFUND>
                    </DETAILS>
                </REFUNDSTATUS> ';
            }
        });

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = '<REFUNDSTATUS>
                <ERRORCODE>EE</ERRORCODE>
                <MESSAGE>Refund Not Found</MESSAGE>
                </REFUNDSTATUS>';
            }

            if ($action === 'refund')
            {
                $content['STATUSCODE']         = 'M1';
                $content['STATUSMESSAGE']      = 'Refund is not allowed';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = '<REFUNDSTATUS>
                <ERRORCODE>EE</ERRORCODE>
                <MESSAGE>Refund Not Found</MESSAGE>
                </REFUNDSTATUS>';
            }
        });

        $response = $this->retryFailedRefund($refund['id'], $payment['id']);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    public function testRefundDateTimeIssueAfterMidNight()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $attributes = [
            'created_at' => '1541269830',
        ];

        $this->fixtures->edit('payment', $payment['id'], $attributes);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = '<REFUNDSTATUS>
                <ERRORCODE>EE</ERRORCODE>
                <MESSAGE>Refund Not Found</MESSAGE>
                 </REFUNDSTATUS>';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);

        $gatewayEntity = $this->getLastEntity('atom', true);

        $this->assertEquals('Full Refund initiated successfully',
            $gatewayEntity['gateway_result_description']);
    }

    public function testVerifyRefundNotSupported()
    {
        $this->ba->publicAuth();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = 'status-code = 421';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    protected function assertPaymentAfterAuthAndCapture($paymentInput = null)
    {
        $this->doAuthAndCapturePayment($paymentInput);

        $payment = $this->getLastEntity('payment', true);

        if (isset($paymentInput['method']) === false)
        {
            $method = 'card';
        }
        else
        {
            $method = $paymentInput['method'];
        }

        $this->assertEquals('atom', $payment['gateway']);
        $this->assertEquals($method, $payment['method']);
        $this->assertEquals('1000AtomShared', $payment['terminal_id']);
    }

    protected function mockSetVerifyTransactionId()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $gatewayPayment = $this->getLastEntity('atom', true);

            $content['atomtxnId'] = $gatewayPayment['gateway_payment_id'];

            $content['BID']       = $gatewayPayment['bank_payment_id'];
        });
    }

    protected function mockSignatureNotFound()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'hash')
            {
                unset($content['signature']);
            }
            if ($action === 'callback')
            {
                $content['f_code'] = 'F';
            }
        });
    }

    protected function mockInvalidCallback($field)
    {
        $this->mockServerContentFunction(function(&$content, $action = null) use ($field)
        {
            if ($action === 'callback')
            {
                unset($content[$field]);
            }
        });
    }

    public function testAllahabadTpvMigrationPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['bank'] = 'ALLA';
        $this->payment['order_id'] = $order['id'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['bank_txn'] = '99999999';
            $content['bank_name'] = 'ALLA';
        });

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('atom', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntityForAllahabad'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
            $data['request']['content']['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }
}
