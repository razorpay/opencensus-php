<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Corporation;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Gateway\Netbanking\Corporation;

class NetbankingCorporationGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingCorporationGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_corporation';

        $this->bank = 'CORP';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment
        );

        $this->assertArrayHasKey('bank_payment_id', $payment);
    }

    /**
     * Test a payment that was tampered with in the authorize step
     * This case should throw PaymentVerificationException during verify broken
     */
    public function testTamperedPayment()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedVerifyResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->doNetbankingCorporationAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doNetbankingCorporationAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testRefundExcelFile()
    {
        Mail::fake();

        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);
        // sd($refund);

        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForNb('CORP');

        $this->assertEquals($data['netbanking_corporation']['count'], 3);
        $this->assertTrue(file_exists($data['netbanking_corporation']['file']));

    }

    protected function doNetbankingCorporationAuthAndCapturePayment($order = [])
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'CORP';

        if (empty($order) === false)
        {
            $payment['order_id'] = $order['id'];
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [];
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[Corporation\ResponseFields::STATUS] = Corporation\ResponseCodeMap::RESULT_REJECTED;
            }
        });
    }
}
