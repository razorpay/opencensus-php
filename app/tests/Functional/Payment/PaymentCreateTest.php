<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentCreateTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
//        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecure()
    {
        $this->payment['card']['number'] = '555555555555558';

        $request = array(
            'content' => $this->payment,
            'url' => '/payments/create/checkout',
            'method' => 'post');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecureError()
    {
        $this->payment['card']['number'] = '555555555555559';

        $request = array(
            'content' => $this->payment,
            'url' => '/payments/create/checkout',
            'method' => 'post');

        $this->changeEnvToNonTest();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_VALIDATION_FAILURE');
    }

    public function testCallbackOnAuthorizedPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->doAuthPayment();
        $id = $payment['razorpay_payment_id'];

    }

}