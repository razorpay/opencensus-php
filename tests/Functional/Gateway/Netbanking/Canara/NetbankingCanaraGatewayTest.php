<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Canara;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingCanaraGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingCanaraGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_canara';

        $this->bank = 'CNRB';

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_canara_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $paymententity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymententity);

        $netbankingentity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $netbankingentity);
    }

    public function doNetbankingCanaraAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

}