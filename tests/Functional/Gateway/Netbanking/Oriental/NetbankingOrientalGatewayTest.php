<?php

namespace RZP\Tests\Functional\Gateway\Oriental;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Oriental;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
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

        $this->gateway = Payment\Gateway::NETBANKING_ORIENTAL;

        $this->fixtures->create('terminal:shared_netbanking_oriental_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking);
    }

    public function testPaymentFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->mockPaymentFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingPaymentFailed');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(1, $verify[ConstantsEntity::PAYMENT][Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    public function testPaymentVerifyFailed()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->mockVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    private function mockVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[Oriental\ResponseFields::TXN_STATUS] = Oriental\Status::FAILED;
                }
            });
    }

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Oriental\ResponseFields::PAID] = Oriental\Status::FAILED;
            });
    }
}
