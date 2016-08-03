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
    }

    public function testPayment()
    {
        $this->markTestSkippedForWercker();

        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);
    }
}
