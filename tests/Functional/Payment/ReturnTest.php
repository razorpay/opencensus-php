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
        $testData = array(
            'request' => [
                'content' => [
                    'callback_url' => $this->getLocalMerchantCallbackUrl(),
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

        $this->assertTrue($this->merchantCallbackFlow);
    }

    public function testReturnUrlWithout3dSecure()
    {
        $testData = array(
            'request' => [
                'content' => [
                    'card' => ['number' => '4111111111111111'],
                    'callback_url' => $this->getLocalMerchantCallbackUrl(),
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

        $this->assertTrue($this->merchantCallbackFlow);
    }

    public function testReturnUrlWithout3dSecureFailure()
    {
        $this->app['env'] = 'dev';
        $this->app['config']->set('app.debug', false);

        $testData = array(
            'request' => [
                'content' => [
                    'card' => ['number' => '411111111111111'],
                    'callback_url' => $this->getLocalMerchantCallbackUrl(),
                ],
            ],
            'response' => [
                'content' => [
                    'error[code]' => 'BAD_REQUEST_ERROR',
                    'error[description]' => 'The number is invalid.',
                    'error[field]' => 'number',
                ],
            ]
        );

        $this->replaceDefualtValues($testData['request']['content']);

        $content = $this->runRequestResponseFlow($testData);

        $this->assertTrue($this->merchantCallbackFlow);
    }

    public function testReturnUrlWith3dSecureFailure()
    {
        $this->markTestSkipped();

        $this->app['env'] = 'dev';
        $this->app['config']->set('app.debug', false);

        $testData = array(
            'request' => [
                'content' => [
                    'card' => ['number' => '411111111111111'],
                    'callback_url' => $this->getLocalMerchantCallbackUrl(),
                ],
            ],
            'response' => [
                'content' => [
                    'error[code]' => 'BAD_REQUEST_ERROR',
                    'error[description]' => 'The number is invalid.',
                    'error[field]' => 'number',
                ],
            ]
        );

        $this->replaceDefualtValues($testData['request']['content']);

        $content = $this->runRequestResponseFlow($testData);

        $this->assertTrue($this->merchantCallbackFlow);
    }
}
