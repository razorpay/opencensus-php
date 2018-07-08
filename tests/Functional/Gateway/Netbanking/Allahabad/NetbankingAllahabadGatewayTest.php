<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Allahabad;

use Carbon\Carbon;
use RZP\Models\Terminal\Options;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingAllahabadGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingAllahabadGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_allahabad';

        $this->payment = $this->getDefaultNetbankingPaymentArray('ALB');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        // Assert that bank payment id exists and is an integer
        $this->assertArrayHasKey('bank_payment_id', $gatewayPayment);

        $this->assertTrue(filter_var($gatewayPayment['bank_payment_id'],
                FILTER_VALIDATE_INT) !== false);
    }
}