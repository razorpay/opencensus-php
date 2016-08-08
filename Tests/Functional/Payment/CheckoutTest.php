<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CheckoutTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testCheckoutId()
    {
        $payment = $this->getDefaultPaymentArray();

        $checkoutId = \RZP\Models\Base\UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity('payment_analytics', true);

        $this->assertEquals($checkoutId, $paymentAnalytic['checkout_id']);
        $this->assertEquals(1, $payment['attempt']);

        // $payment = $this->getDefaultPaymentArray();
        // $payment['_']['checkout_id'] = $checkoutId;

        // $payment = $this->doAuthPayment($payment);
        // $payment = $this->getLastEntity('payment', true);

        // $this->assertEquals(checkout_id, $payment['checkout_id']);
        // $this->assertEquals(1, $payment['attempt']);
    }
}
