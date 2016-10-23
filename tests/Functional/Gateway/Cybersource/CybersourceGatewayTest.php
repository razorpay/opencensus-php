<?php

namespace RZP\Tests\Functional\Gateway\Cybersource;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayTestData.php';

        parent::setUp();

        $this->sharedHdfcTerminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function testPayment()
    {
        $payment = $this->defaultAuthPayment();

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);
    }

    public function testGatewayTimeoutError()
    {
        $payment = $this->getDefaultPaymentArray();

        $data = $this->testData[__FUNCTION__];

        $this->mockTimeout();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayProcessorTimeout()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            $content['decision'] = 'REJECT';
            $content['reasonCode'] = 151;
            $content['payerAuthEnrollReply'] = [
                'reasonCode' => 151
            ];

            unset($content['purchaseTotals']);
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
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
        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment();

        $paymentId = $response['razorpay_payment_id'];
        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);
        $this->assertTestResponse($refund);
    }

    public function testGatewayPaymentMatchVerify()
    {
        $payment = $this->doAuthPayment();

        $response = $this->verifyPayment($payment['razorpay_payment_id']);

        $this->assertSame($response['payment']['verified'], 1);
        $this->assertSame($response['gateway']['status'], 'status_match');
        $this->assertSame($response['gateway']['gateway'], 'cybersource');
        $this->assertSame($response['gateway']['gatewayPayment']['status'], 'authorized');
    }

    public function testGatewayPaymentMismatchVerify()
    {
        $this->mockTimeout('processor');

        $this->makeRequestAndCatchException(function()
        {
            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthorizeFailedPayment()
    {
        $this->mockTimeout('processor');

        $this->makeRequestAndCatchException(function()
        {
            $this->doAuthPayment();
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
        $this->mockServerContentFunction(function(&$content) use ($type)
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
        });
    }
}
