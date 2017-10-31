<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RateLimitingTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        // $this->markTestSkipped();

        parent::setUp();

        $this->ba->publicAuth();

        $this->app['config']->set('throttle.skip', false);
    }

    public function tearDown()
    {
        $this->app['config']->set('throttle.skip', true);

        parent::tearDown();
    }

    public function testThrottleAdmin()
    {
        $this->app['config']->set('throttle.limits.test.admin', 2);

        $response = $this->postDummyRequestAdminAuth();
        $this->assertArrayHasKey('message', $response);

        $response = $this->postDummyRequestAdminAuth();
        $this->assertArrayHasKey('message', $response);

        $response = $this->postDummyRequestAdminAuth();
        $this->assertArrayNotHasKey('message', $response);
        $this->assertEquals('Request failed. Please try after sometime.', $response['error']['description']);
    }

    protected function postDummyRequestAdminAuth()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/dummy/route',
            'content' => [
                'message' => 'hi',
            ],
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testThrottle()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5217294025032720';

        $this->app['config']->set('throttle.limits.test.public', 0);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayNotHasKey('razorpay_payment_id', $response);

        $this->assertEquals('Request failed. Please try after sometime.', $response['error']['description']);
    }

    public function testThrottleWithCallback()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4111111111111111';

        // For enrolled cards we need the limit to be more than double
        // of actual values needed for public auth
        $this->app['config']->set('throttle.limits.test.public', 1);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayNotHasKey('razorpay_payment_id', $response);

        $this->assertEquals('Request failed. Please try after sometime.', $response['error']['description']);
    }

    public function testThrottleWithMultipleLimits()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5217294025032720';

        $this->app['config']->set('throttle.limits.test.public', 10);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $paymentId = $response['razorpay_payment_id'];

        $this->app['config']->set('throttle.limits.test.private', 0);

        $response = $this->getEntityById('payment', $paymentId);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Request failed. Please try after sometime.', $response['error']['description']);
    }
}
