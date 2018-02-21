<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Csb\Mode;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Csb\ResponseFields;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
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

        return $payment;
    }

    public function testPaymentFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockPaymentFailed();

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, __FUNCTION__ . 'NetbankingEntity');

        return $payment;
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        // Since BID is not null, we send V as the Mode for verify
        $this->assertEquals(Mode::VERIFY, $verify['gateway']['verifyRequest'][7]);

        $this->assertEquals(true, $verify['gateway']['apiSuccess']);
        $this->assertEquals(true, $verify['gateway']['gatewaySuccess']);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);
        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // Status remains in Success after verify
        $this->assertEquals(Status::SUCCESS, $netbanking[Netbanking::STATUS]);

        $this->assertEquals($verify[ConstantsEntity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentFailedVerify()
    {
        $payment = $this->testPaymentFailed();

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $testData = $this->testData['testPaymentFailedNetbankingEntity'];

        // The status changes from 'N' to 'Y' after verification
        $testData['status'] = Status::SUCCESS;

        $this->assertArraySelectiveEquals($testData, $netbanking);
    }

    public function testPaymentFailedVerifyFailed()
    {
        $payment = $this->testPaymentFailed();

        $this->mockPaymentVerifyFailed();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(false, $verify['gateway']['apiSuccess']);
        $this->assertEquals(false, $verify['gateway']['gatewaySuccess']);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);
        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // Status remains in failed after verify
        $this->assertEquals(Status::FAILURE, $netbanking[Netbanking::STATUS]);

        $this->assertEquals($verify[ConstantsEntity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(VerifyStatus::SUCCESS, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentSuccessVerifyFailed()
    {
        $payment = $this->testPayment();

        $data = $this->testData['testVerifyMismatch'];

        $this->mockPaymentVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // The status doesn't get updated from Y to N
        $testData = $this->testData['testPayment'];

        $this->assertArraySelectiveEquals($testData, $netbanking);
    }

    public function testPaymentVerifyHtmlResponse()
    {
        $payment = $this->testPaymentFailed();

        $data = $this->testData['testVerifyMismatch'];

        $this->mockPaymentVerifyHtmlPage();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(VerifyStatus::UNKNOWN, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'testPaymentFailedNetbankingEntity');
    }

    public function testVerifyCallbackFailure()
    {
        $this->mockPaymentVerifyFailed();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->testPayment();
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        // The payment status is updated to failed due to the verify callback error
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // The status doesn't get updated from Y to N
        $testData = $this->testData[__FUNCTION__ . 'Entity'];

        $this->assertArraySelectiveEquals($testData, $netbanking);
    }

    public function testPaymentEmptyStringVerifyResponse()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockPaymentVerifyEmptyStringResponse();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'testPayment');
    }

    public function testPaymentRandomStringVerifyResponse()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockPaymentVerifyRandomStringResponse();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'testPayment');
    }

    public function testPaymentFailedVerifyCallbackSuccess()
    {
        $this->mockPaymentFailed();

        $data = $this->testData['testPaymentFailed'];

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                // Payment is a failure, but verify callback is a success
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'testPaymentFailedNetbankingEntity');
    }

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[ResponseFields::STATUS] = Status::FAILURE;
                $content[ResponseFields::NARRATION] = 'Payment failed';
            });
    }

    private function mockPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::VERIFICATION] = Status::FAILURE;
                }
            });
    }

    private function mockPaymentVerifyHtmlPage()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content = file_get_contents(__DIR__ . '/csbk.html');
                }
            });
    }

    private function mockPaymentVerifyEmptyStringResponse()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content = '';
                }
            });
    }

    private function mockPaymentVerifyRandomStringResponse()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content = 'Random string';
                }
            });
    }
}
