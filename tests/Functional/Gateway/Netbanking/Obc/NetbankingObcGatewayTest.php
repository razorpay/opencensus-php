<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Obc;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Obc;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class NetbankingObcGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $bank = IFSC::ORBC;

    protected function setUp(): void
    {
        $this->markTestSkipped();

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

        $netbanking = $this->getDbLastEntityToArray(ConstantsEntity::NETBANKING);

        $this->assertTestResponse($netbanking);
    }

    public function testPaymentFailed()
    {
        $payment = $this->createPaymentFailed();

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        // The payment fails and an exception is thrown before acquirer data is updated
        $this->assertNull($payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $netbanking = $this->getDbLastEntityToArray(ConstantsEntity::NETBANKING);

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

        $payment = $this->getDbLastEntityToArray(ConstantsEntity::PAYMENT);

        // The payment status is updated to failed due to the verify callback error
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getDbLastEntityToArray(ConstantsEntity::NETBANKING);

        // The status doesn't get updated from Y to N
        $testData = $this->testData[__FUNCTION__ . 'Entity'];

        $this->assertArraySelectiveEquals($testData, $netbanking);
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

        $payment = $this->getDbLastEntityToArray(ConstantsEntity::PAYMENT);

        // The payment status is updated to failed due to the verify callback error
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(VerifyStatus::SUCCESS, $verify[ConstantsEntity::PAYMENT][Payment\Entity::VERIFIED]);

        $netbanking = $this->getDbLastEntityToArray(ConstantsEntity::NETBANKING);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    public function testPaymentVerifyMismatch()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockVerifyFailure();

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });
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

    public function testAuthorizeFailedPayment()
    {
        $this->createPaymentFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['reference1']);

        $this->mockVerifyResponse();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['reference1']);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testransactionNotFoundAtObc()
    {
        $payment = $this->createPaymentFailed();

        $this->mockTransactionNotFoundAtObc();

        $verify = $this->verifyPayment($payment['id']);

        $this->assertEquals(VerifyStatus::SUCCESS, $verify[ConstantsEntity::PAYMENT][Payment\Entity::VERIFIED]);
    }

    protected function createPaymentFailed()
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

    protected function mockVerifyResponse()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Obc\ResponseFields::BANK_PAYMENT_ID] = 9999999999;
            });
    }

    protected function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Obc\ResponseFields::PAID] = Obc\Status::FAILED;
            });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['TXN_STATUS'] = 'FAILURE';
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

    protected function mockTransactionNotFoundAtObc()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if($action === 'verify')
                {
                    $content = '103922 : Transaction details cannot be fetched/No Records Fetched';
                }
            }, $this->gateway);
    }
}
