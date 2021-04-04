<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Vijaya;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Vijaya;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class NetbankingVijayaGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $bank = null;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__.'/NetbankingVijayaGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_vijaya';

        $this->bank = 'VIJB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_vijaya_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testPaymentFailed()
    {
        $payment = $this->payment;

        $this->mockPaymentFailed();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $payment =  $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingPaymentFailed');
    }

    public function testVerifyCallbackFailure()
    {
        $this->mockVerifyFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->testPayment();
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    public function testAmountTampering()
    {
        $this->mockAmountMismatch();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->testPayment();
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(VerifyStatus::SUCCESS, $verify[ConstantsEntity::PAYMENT][Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    public function testPaymentVerifyMismatch()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockVerifyFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });
    }

    // Authorization fails, but verify shows success
    // Results in a payment verification error
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testPaymentFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    //TODO : find out if this will ever happen as verify should mostly be disabled
    public function testAuthorizeFailedPayment()
    {
        $this->testPaymentFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['reference1']);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }

    protected function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'authorize')
                {
                    $content[Vijaya\ResponseFields::STATUS] = 'N';

                    unset($content[Vijaya\ResponseFields::BANK_REFERENCE_NUMBER]);
                }
            });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content = '<HTML><head><title> Shopping Mall Message Page </title></head><body><LINK rel="stylesheet" href="" type="text/css"><BODY bgcolor="#ffffff" link="#ff3300" vlink="#bbbbbb"><table border=0 width=100% CELLPADDING="5" cellspacing=0><tr><td><H4>Payment Record Not Found Check the Parameters sent</H4>&nbsp;</TD></tr></table><center><!--p--><a href="">Return To Shopping Site<a/><!--/p--></center></body></HTML>';
                }
            });
    }

    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'authorize')
                {
                    $content['AMT'] = '300.00';
                }
            });
    }
}
