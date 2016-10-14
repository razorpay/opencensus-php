<?php

namespace RZP\Tests\Functional\Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Tests\Functional\TestCase;

/**
 * Tests that support payments (capture/refund) are working fine.
 * creates a hold payment using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class SupportTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tests the support payments, calls capture & refund
     * @group testSupport
     * @group testCapture
     * @group testRefund
     */
    public function testSupport()
    {
        $this->ba->publicAuth();
        $testData = array(
            'request' => [
                'content' => [
                    'card' => [
                        'number' => '4012001038443335',
                    ],
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

        // get amount
        $amount = '50000';

        $this->capturePayment($id, $amount);

        $this->refundPayment($id);
    }

    public function testVerifyAllPayments()
    {
        $createdAt = time() - 60 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url' => '/payments/verify/all',
            'method' => 'post'
        );

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(
            [
                'filter'        => 'all',
                'verified'      => 1,
                'failed'        => 0,
                'authorized'    => 0,
                'timed out'     => 0,
                'error'         => 0,
                'authorizedTime'=> 0,
                'totalTime'     => '0 secs',
            ],
            $content);
    }
}
