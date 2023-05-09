<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc;

use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Services\NbPlus;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class NetbankingHdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingHdfcGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_hdfc';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_hdfc_terminal');

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testPayment()
    {
        $this->fixtures->create('terminal:netbanking_hdfc_terminal');

        $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === NbPlus\Action::CALLBACK)
            {
                $content = [
                    NbPlus\Response::RESPONSE => null,
                    NbPlus\Response::ERROR    => [
                        NbPlus\Error::CODE    => 'RUNTIME',
                        NbPlus\Error::CAUSE   => [
                            NbPlus\Error::MOZART_ERROR_CODE => 'BAD_REQUEST_AMOUNT_MISMATCH'
                        ]
                    ],
                ];
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentOnDirectHdfcTerminal()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals($terminal['id'], $payment['terminal_id']);
    }

    public function testPaymentOnSharedTerminal()
    {
        $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);

        $verify =$this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

    }

    public function testVerifyOldPayment()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $FourtySixDaysAgoTimeStamp = Carbon::parse('46 days ago')->timestamp;

        $this->fixtures->edit('payment', $payment['id'], ['created_at' => $FourtySixDaysAgoTimeStamp ]);

        $verify = $this->verifyPayment($payment['id']);

        $this->assertTrue($verify['gateway']['gatewaySuccess']);
    }

    public function testRefundExcelFile()
    {
        Mail::fake();

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForNb('HDFC');

        $this->assertEquals(3, $data['netbanking_hdfc']['count']);
        $this->assertTrue(file_exists($data['netbanking_hdfc']['file']));

        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $testData = [
                'body' => 'Please forward the HDFC Netbanking refunds file to: Directpay.Refunds@hdfcbank.com',
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function doNetbankingHdfcAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

    protected function mockServerContentFunction($closure): void
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing($closure);
    }
}
