<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Idfc;

use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Idfc\Fields;
use RZP\Gateway\Netbanking\Idfc\StatusCode;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Idfc\TransactionDetails;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIdfcGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingIdfcGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_idfc';

        $this->bank = 'IDFB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_idfc_terminal');

        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertTestResponse($payment);

        $netbankingEntity = $this->getDbLastEntityToArray('netbanking');

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $netbankingEntity
        );
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:idfc_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTpvPaymentEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
            $data['request']['content']['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    public function testTamperedPayment()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[Fields::AMOUNT] = '100.00';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals($payment['status'], 'failed');
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

        $payment = $this->getDbLastEntityToArray(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getDbLastEntityToArray(ConstantsEntity::NETBANKING);

        $this->assertTestResponse($netbanking, __FUNCTION__ . 'NetbankingEntity');

        return $payment;
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getDbLastEntityToArray('netbanking');

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testPaymentCancelledByUser()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if([$action === 'authorize'])
            {
                $content[Fields::PAYMENT_STATUS]    = 'N';
                $content[Fields::RESPONSE_CODE]     = 'CAN018';
                $content[Fields::RESPONSE_MESSAGE]  = 'Transaction canceled by customer';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testPaymentCancelledByUserForResponse()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if([$action === 'authorize'])
            {
                $content[Fields::PAYMENT_STATUS]    = 'N';
                $content[Fields::RESPONSE_CODE]     = 'CAN018';
                $content[Fields::RESPONSE_MESSAGE]  = 'Transaction canceled by customer';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testPaymentFailedVerifyFailed()
    {
        $data = $this->testData['testPaymentFailed'];

        $this->mockPaymentVerifyFailed();

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $this->payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $verify = $this->verifyPayment($this->payment[Payment\Entity::ID]);

        $this->assertEquals(false, $verify['gateway']['apiSuccess']);
        $this->assertEquals(false, $verify['gateway']['gatewaySuccess']);

        $payment    = $this->getDbLastEntityToArray(ConstantsEntity::PAYMENT);
        $netbanking = $this->getDbLastEntityToArray(ConstantsEntity::NETBANKING);

        $this->assertEquals(StatusCode::ACCOUNT_OR_TRANSACTION_FAILURE, $netbanking[Netbanking::STATUS]);

        $this->assertEquals($verify[ConstantsEntity::PAYMENT][Payment\Entity::ID], 'pay_'.$payment[Payment\Entity::ID]);
        $this->assertEquals(VerifyStatus::SUCCESS, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    protected function mockPaymentFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if($action === 'authorize')
            {
                $content[Fields::PAYMENT_STATUS]    = 'N';
                $content[Fields::RESPONSE_CODE]     = StatusCode::ACCOUNT_OR_TRANSACTION_FAILURE;
                $content[Fields::RESPONSE_MESSAGE]  = 'Account/Transaction Failure';
            }
        });
    }

    protected function mockPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if($action === 'authorize')
                {
                    $content[Fields::PAYMENT_STATUS]    = 'N';
                    $content[Fields::RESPONSE_CODE]     = StatusCode::ACCOUNT_OR_TRANSACTION_FAILURE;
                    $content[Fields::RESPONSE_MESSAGE]  = 'Account/Transaction Failure';
                }
                else if ($action === 'verify')
                {
                    $content[Fields::STATUS_RESULT]     = TransactionDetails::STATUS_FAILURE;
                    $content[Fields::RESPONSE_CODE]     = StatusCode::ACCOUNT_OR_TRANSACTION_FAILURE;
                    $content[Fields::RESPONSE_MESSAGE]  = 'Account/Transaction Failure';
                }
            });
    }

    public function testPaymentCancelledByUserForResponseInHindi()
    {
        $config = $this->fixtures->create('config', ['type' => 'locale', 'is_default' => '1', 'config' => '{"language_code" : "hi"}']);

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if([$action === 'authorize'])
            {
                $content[Fields::PAYMENT_STATUS]    = 'N';
                $content[Fields::RESPONSE_CODE]     = 'CAN018';
                $content[Fields::RESPONSE_MESSAGE]  = 'Transaction canceled by customer';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals($payment['status'], 'failed');
    }
}
