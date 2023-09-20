<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Mockery;
use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingJsbGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingJsbGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->bank = 'JSFB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_jsb_terminal');

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymentEntity);

    }

    public function testTamperedAmount()
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing(function(& $content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'RUNTIME',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'SERVER_ERROR_RUNTIME_ERROR',
                        'gateway_error_code'                            =>  '',
                        'gateway_error_description'                     =>  '',
                    ]
                ],
            ];
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testAuthFailed()
    {
        $this->markTestSkipped();

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
            $this->doAuthAndCapturePayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testAuthFailedVerifySuccess()
    {
        $this->markTestSkipped();

        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_jsb_tpv_terminal');

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


    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        assert($verify['payment']['status'] === 'captured');
    }
}
