<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;

class PaymentCreateTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecure()
    {
        $this->payment['card']['number'] = '555555555555558';

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecureError()
    {
        $this->payment['card']['number'] = '555555555555559';

        $this->changeEnvToNonTest();

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertEquals($content['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_VALIDATION_FAILURE');
    }

    public function testPaymentWithUnderscoreArray()
    {
        $this->payment['_'] = array('1' => 2, '3' => 4);

        $content = $this->doAuthPayment($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCallbackOnAuthorizedPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->doAuthPayment();
        $id = $payment['razorpay_payment_id'];
    }

    public function testAutoCaptureWithOrderPayment()
    {
        $order = $this->fixtures->create('order', ['payment_capture' => true]);
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $response = $this->doAuthPayment($payment);

        $actualSignature = $response['razorpay_signature'];

        unset($response['razorpay_signature']);

        ksort($response);
        $exceptedSignature = $this->getSignature($response, 'TheKeySecretForTests');

        $this->assertEquals($actualSignature, $exceptedSignature);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($order->getPublicId(), $payment['order_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(true, $order['authorized']);
    }

    public function testInternationalPayment()
    {
        $this->fixtures->merchant->enableInternational();
        $this->payment['card']['number'] = '4012010000000007';
        $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], true);
    }

    public function testIntlPaymentWhenNotAllowed()
    {
        $this->fixtures->merchant->disableInternational();
        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function ()
            {
                $this->payment['card']['number'] = '4012010000000007';
                $this->doAuthPayment($this->payment);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED');
    }

    public function testCreatePaymentInEs()
    {
        $mockEs = $this->mockEsClient();

        $testData = $this->testData[__FUNCTION__];

        $mockEs->shouldReceive('update')
               ->once()
               ->with(
                   Mockery::on(function ($data) use ($testData)
                   {
                       $this->assertArraySelectiveEquals($testData, json_decode(json_encode($data), true));
                       return true;
                   })
               );

        $this->doAuthPaymentViaCheckoutRoute($this->payment);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForSuccess()
    {
        $payment = $this->doAuthPayment();

        $callbackUrl = $this->recorder->callbackUrl;

        $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($payment, $content);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForError()
    {
        // This will have raised an insufficient balance error.
        $this->makeRequestAndCatchException(function()
        {
            $this->payment['card']['number'] = '5010101010101015';
            $content = $this->doAuthPayment($this->payment);
            $this->doAuthPayment();
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $callbackUrl = $this->recorder->callbackUrl;

            $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);
        });
    }

    protected function mockEsClient()
    {
        $clientBuilder = Mockery::mock('RZP\Services\EsClient')->makePartial();

        $this->app->instance('es', $clientBuilder);

        return $clientBuilder;
    }
}
