<?php

namespace Functional\Gateway\PayLater;

use RZP\Tests\Functional\Helpers;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaylaterFlexmoneyGatewayTest extends TestCase
{
    use PaymentTrait;
    use Helpers\DbEntityFetchTrait;

    protected $provider = 'hdfc';

    protected $method = 'paylater';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/PaylaterFlexmoneyGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'paylater';

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');
    }

    public function testPaymentAndRefund()
    {
        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        // authorize
        $response = $this->doAuthPayment($payment);

        // assertions after payment status - authorized
        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentAuthorized');

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntityFlexmoney');

        $this->assertNotNull($response['razorpay_payment_id']);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntityFlexmoney');

        // capture
        $this->capturePayment($response['razorpay_payment_id'], $payment['amount']);

        // assertions after payment status - captured

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCaptureEntity');

        //refund
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['status']       = 'Success';
            }

            return $content;
        });

        $this->refundPayment($response['razorpay_payment_id'], $payment['amount']);

        // assertions after payment status - refunded

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertNotNull($cardlessEmiEntity['refund_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentRefundEntity');

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentEntityAfterRefund');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('1234567', $refund['acquirer_data']['arn']);
    }

    public function testPaymentVoidRefund()
    {
        $this->fixtures->merchant->addFeatures('void_refunds');

        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        // authorize
        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        //refund
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['status']       = 'Success';
            }

            return $content;
        });

        $this->refundPayment($response['razorpay_payment_id'], $payment['amount']);

        // assertions after payment status - refunded

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertNotNull($cardlessEmiEntity['refund_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentRefundEntity');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('1234567', $refund['acquirer_data']['arn']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testCapturePaymentFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                $content['error_code'] = 'CAPTURE_FAILED';
                $content['error_description'] = 'Payment capture failed';
                unset($content['entity']);
                unset($content['rzp_payment_id']);
                unset($content['provider_payment_id']);
                unset($content['status']);
                unset($content['currency']);
                unset($content['amount']);
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

                $this->doAuthPayment($payment);

                $payment = $this->getLastEntity('payment', true);

                $this->capturePayment($payment['public_id'], $payment['amount']);
            });
    }

    public function testPaymentVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['error_code'] = 'payment_verify_failed';
                $content['error_description'] = 'Payment verification failed';
                unset($content['status']);
            }
        });

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

                $authPayment = $this->doAuthPayment($payment);

                $this->verifyPayment($authPayment['razorpay_payment_id']);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 0);
    }
}
