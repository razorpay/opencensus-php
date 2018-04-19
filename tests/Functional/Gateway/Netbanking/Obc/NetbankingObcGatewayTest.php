<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Obc;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Obc;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingObcGatewayTest extends TestCase
{
    use PaymentTrait;

    private $payment;

    private $bank = IFSC::ORBC;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingObcGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->gateway = 'netbanking_obc';

        $this->fixtures->create('terminal:shared_netbanking_obc_terminal');
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

    public function testPaymentVerifyMistamtch()
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

    public function testPaymentAmountMismatch()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockAmountMismatch();

        $data = $this->testData['testPaymentAmountMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });
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

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Obc\ResponseFields::PAID] = Obc\Status::FAILED;
            });
    }

    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content['AMT'] = '300.00';
            }, $this->gateway);
    }
}
