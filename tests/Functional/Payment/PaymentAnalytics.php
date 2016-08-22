<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Constants\Table;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentAnalyticsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testAttempts()
    {
        $payment = $this->getDefaultPaymentArray();

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity(Table::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic['checkout_id']);
        $this->assertEquals(1, $paymentAnalytic['attempts']);

        // ------------------------------------------------------------------ //

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity(Table::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic['checkout_id']);
        $this->assertEquals(2, $paymentAnalytic['attempts']);
    }
}
