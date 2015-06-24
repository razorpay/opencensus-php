<?php

namespace Tests\Functional\Payment;
use Tests\Functional\Helpers\Payment\PaymentTrait;

use Tests\Functional\TestCase;

class ReturnTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();
    }

    public function testReturnUrl()
    {
$this->markTestSkipped();
        $this->ba->publicAuth();

        $callbackUrl = $this->getLocalMerchantCallbackUrl();

        $testData = array(
            'request' => [
                'content' => [
                    'callback_url' => $callbackUrl,
                ],
            ],
            'response' => [
                'content' => [],
            ]
        );

        $this->replaceDefualtValues($testData['request']['content']);

        $payment = $this->runRequestResponseFlow($testData);
sd($payment);
        // get its payment id
        $this->assertArrayHasKey('razorpay_payment_id', $payment);
        $id = $payment['razorpay_payment_id'];

        // get amount
        $amount = '50000';

        $this->capturePayment($id, $amount);

        $this->refundPayment($id);

    }
}
