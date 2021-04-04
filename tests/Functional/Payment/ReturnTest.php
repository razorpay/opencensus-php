<?php

namespace RZP\Tests\Functional\Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Tests\Functional\TestCase;

class ReturnTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
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
                    'card' => ['number' => '4012001037167778'],
                ],
            ],
            'response' => [
                'content' => [],
            ]
        );

        $this->replaceDefaultValues($testData['request']['content']);

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

        $this->replaceDefaultValues($testData['request']['content']);

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
                    'error[code]'        => 'BAD_REQUEST_ERROR',
                    'error[description]' => 'The number is invalid.',
                    'error[field]'       => 'number',
                ],
                'status_code' => 200,
            ]
        );

        // Status will be 200 because it's a form post to the callback url and we get a response from that.
        // So we are checking the status code of the response from merchant callback url here.

        $this->replaceDefaultValues($testData['request']['content']);

        $content = $this->runRequestResponseFlow($testData);

        // Callback flow in this case will be true because we are returning
        // the result after first request only but with any redirection.
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

        $this->replaceDefaultValues($testData['request']['content']);

        $content = $this->runRequestResponseFlow($testData);

        $this->assertTrue($this->merchantCallbackFlow);
    }
}
