<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

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
}