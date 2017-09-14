<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Bob;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingBobGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingBobGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_bob';

        $this->bank = 'BARB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_bob_terminal');
    }
}
