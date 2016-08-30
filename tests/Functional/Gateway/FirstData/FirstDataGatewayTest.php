<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class FirstDataGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/FirstDataGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'first_data';

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testPayment()
    {
        $this->markTestSkippedForWercker();

        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $this->capturePayment($authResponse['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);
    }
}
