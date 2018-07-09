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

        $this->bank = 'canara';     // verify

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_canara_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $paymententity = $this->getLastEntity('payment', true);

        s($paymententity);

        //$this->assertTestResponse($paymententity);

        $netbankingentity = $this->getLastEntity('netbanking', true);

        s($netbankingentity);
    }

    public function doNetbankingCanaraAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('canara');

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

}