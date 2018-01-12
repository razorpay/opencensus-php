<?php

namespace RZP\Tests\Functional\Gateway\Oriental;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingOrientalGatewayTest extends TestCase
{
    use PaymentTrait;

    private $payment;

    private $bank = IFSC::ORBC;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingOrientalGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->fixtures->create('terminal:shared_netbanking_oriental_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment['status']);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking);
    }
}
