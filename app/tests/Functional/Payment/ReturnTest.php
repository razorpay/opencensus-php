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

        $this->ba->publicAuth();
    }

    public function testReturnUrlWith3dSecure()
    {
        $callbackUrl = $this->getLocalMerchantCallbackUrl();

        $testData = array(
            'request' => [
                'content' => [
                    'callback_url' => $callbackUrl,
                    'card' => ['number' => '4012001037141112'],
                ],
            ],
            'response' => [
                'content' => [],
            ]
        );

        $this->replaceDefualtValues($testData['request']['content']);

        $payment = $this->runRequestResponseFlow($testData);

        // get its payment id
        $this->assertArrayHasKey('razorpay_payment_id', $payment);
        $id = $payment['razorpay_payment_id'];
    }

    public function testReturnUrlWithout3dSecure()
    {
        $callbackUrl = $this->getLocalMerchantCallbackUrl();

        $testData = array(
            'request' => [
                'content' => [
                    'card' => ['number' => '4111111111111111'],
                    'callback_url' => $callbackUrl,
                ],
            ],
            'response' => [
                'content' => [],
            ]
        );

        $this->replaceDefualtValues($testData['request']['content']);

        $payment = $this->runRequestResponseFlow($testData);

        // get its payment id
        $this->assertArrayHasKey('razorpay_payment_id', $payment);
        $id = $payment['razorpay_payment_id'];
    }
}
