<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCsbGatewayTest extends TestCase
{
    private $payment;

    private $sharedTerminal;

    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingCsbGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray(IFSC::CSBK);

        $this->gateway = Payment\Gateway::NETBANKING_CSB;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_csb_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertEquals(Payment\TwoFactorAuth::UNAVAILABLE, $payment[Payment\Entity::TWO_FACTOR_AUTH]);
        $this->assertEquals($netbanking[Netbanking::BANK_PAYMENT_ID], $payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $this->assertTestResponse($netbanking);
    }
}
