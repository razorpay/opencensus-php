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

        // For netbanking payments, acquirer data contains bank_transaction_id which is equal to reference1 attribute
        $this->assertEquals(9999999999, $payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking);
    }

    public function testPaymentFailed()
    {
        $payment = $this->createPaymentFailed();

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        // The payment fails and an exception is thrown before acquirer data is updated
        $this->assertNull($payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingPaymentFailed');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(VerifyStatus::SUCCESS, $verify[ConstantsEntity::PAYMENT][Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    public function testApiSuccessGatewayFailed()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData['testVerifyMismatch'];

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

    public function testApiFailedGatewaySuccess()
    {
        $payment = $this->createPaymentFailed();

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingPaymentFailedVerifySuccess');
    }

    private function createPaymentFailed()
    {
        $data = $this->testData['testPaymentFailed'];

        $payment = $this->payment;

        $this->mockPaymentFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        return $this->getLastEntity(ConstantsEntity::PAYMENT, true);
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
