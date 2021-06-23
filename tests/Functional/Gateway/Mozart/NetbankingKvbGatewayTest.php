<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Mail;

use RZP\Models\Payment\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;

class NetbankingKvbGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingKvbGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->bank = 'KVBL';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_kvb_terminal');

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testPayment()
    {
        $this->doNetbankingKvbAuthAndCapturePayment();

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymentEntity);

        $netbankingEntity = $this->getDbLastEntityToArray('mozart', 'test');

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentMozartEntity'], $netbankingEntity);
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_kvb_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->fixtures->merchant->disableTPV();

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    public function testTamperedAmount()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['amount'] = '5';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testAuthFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthSuccessVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['success'] = false;
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        $this->assertEquals(1, $verify['payment']['verified']);

        $this->assertEquals('captured', $payment['status']);
    }

    protected function doNetbankingKvbAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->doAuthAndCapturePayment($payment);
    }
}
