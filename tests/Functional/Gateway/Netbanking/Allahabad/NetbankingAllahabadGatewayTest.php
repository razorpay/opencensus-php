<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Allahabad;

use Mail;
use Excel;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Allahabad\Status;
use RZP\Gateway\Netbanking\Allahabad\ResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class NetbankingAllahabadGatewayTest extends TestCase
{
    protected $payment;

    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped("Bank merged with Indian bank");

        $this->testDataFilePath = __DIR__.'/NetbankingAllahabadGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_allahabad';

        $this->bank = 'ALLA';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAmountTampering()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockAmountTampering();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testTamperedPaymentNetbankingEntity');
    }

    public function testFailedChecksum()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedChecksum();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testTamperedPaymentNetbankingEntity');
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    public function testPaymentFailedVerifyFailed()
    {
        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockPaymentVerifyFailed();

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifyFailedEntity');
    }

    public function testUserCancelledPayments()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockCancelledPaymentResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testUserCancelledNetbankingEntity');
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:allahabad_tpv_terminal');

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

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['PAID'] = "N";
            }
        });
    }

    protected function mockAmountTampering()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['AMT'] = 100;
            }
        });
    }

    protected function mockFailedChecksum()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['bank_signature'] = 10000000000000000000000;
            }
        });
    }

    protected function mockCancelledPaymentResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['PAID'] = 'C';
                $content['CRN'] = 'INR';
                unset($content['BID']);
                $content['PID'] = 'Razor';
            }
        });
    }

    protected function mockPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::PAID] = Status::NO;
                }
            });
    }
}
