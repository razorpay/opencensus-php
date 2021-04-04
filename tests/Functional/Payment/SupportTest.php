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

    protected function setUp(): void
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

        $this->replaceDefaultValues($testData['request']['content']);

        $payment = $this->runRequestResponseFlow($testData);

        // get its payment id
        $this->assertArrayHasKey('razorpay_payment_id', $payment);
        $id = $payment['razorpay_payment_id'];

        // get amount
        $amount = '50000';

        $this->capturePayment($id, $amount);

        $this->refundPayment($id);
    }
}
