<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici;

use Mail;
use Excel;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Options;

class NetbankingIciciGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_icici';

        $this->payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $this->setMockGatewayTrue();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayPayment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertEquals(9999999999, $gatewayPayment['bank_payment_id']);
    }

    /**
     * Backward compatibility test
     **/
    public function testRetailPaymentWithCorpTerminalPresent()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_icici_corp_terminal');

        $this->testPayment();
    }

    public function testPaymentCorporate()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_icici_corp_terminal');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $this->payment = $this->getDefaultNetbankingPaymentArray('ICIC_C');

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $content = $this->verifyPayment($payment['id']);

        $testData = $this->testData['testPayment'];
        $testData['bank'] = 'ICIC_C';
        $testData['terminal_id'] = '100NbIcicCrpTl';

        $this->assertArraySelectiveEquals($testData, $payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $testData = $this->testData['testPaymentNetbankingEntity'];
        $testData['bank'] = 'ICIC_C';

        $this->assertArraySelectiveEquals($testData, $gatewayPayment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertEquals(9999999999, $gatewayPayment['bank_payment_id']);

        assert($content['payment']['verified'] === 1);

        $this->fixtures->terminal->edit($this->sharedTerminal->getId(), ['corporate' => 0]);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['AMT'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefund()
    {
        $refund = $this->doAuthCaptureAndRefundPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 50000);
        $this->assertEquals($payment['amount'], $refund['amount']);
    }

    public function testPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund the payment above partially
        $refund = $this->refundPayment($payment['id'], 10000);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 10000);
        $this->assertEquals($refund['amount'], 10000);
    }

    public function testFailedRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                // Refund double the amount
                $refund = $this->refundPayment($payment['id'], 100000);
            });
    }

    public function testRefundExcelFile()
    {
        // Will remove test in separate pr
        $this->markTestSkipped();

        Mail::fake();

        // Generate 2 payments
        $this->createRefundsForExcel();

        $this->alterRefundsDateToYesterday();

        // Generating 3rd payment and leaving its created_at
        // date to now unlike first 2 payments
        $this->doAuthCaptureAndRefundPayment($this->payment);

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForNB('ICIC');

        $this->checkRefundFileData($data);

        $this->checkMailQueue();
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_icici_tpv_terminal');

        $this->ba->privateAuth();

        $data = $this->testData[__FUNCTION__]['request']['content'];

        $this->fixtures->merchant->enableTPV();

        $order = $this->startTest();
        $order = $this->getLastEntity('order');

        $this->payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // Asserting that TPV terminal of ICICI gets picked
        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($gatewayPayment['account_number']);
        $this->assertEquals($gatewayPayment['account_number'], $data['account_number']);

        $this->fixtures->merchant->disableTPV();
    }

    public function testFailedAuthPayment()
    {
        $this->mockPaymentFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });
    }

    public function testVerifyMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockVerifyFailure();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['razorpay_payment_id']);
            });
    }

    public function testEmptyVerifyResponse()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testFailedAuthPayment();

        $this->mockStringVerifyResponse();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertEquals(true, $netbanking['received']);
        $this->assertEquals('N', $netbanking['status']);
    }

    public function testStringIndexOutOfRangeVerifyResponse()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testFailedAuthPayment();

        $this->mockStringVerifyResponse('String index out of range: -17');

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertEquals(true, $netbanking['received']);
        $this->assertEquals('N', $netbanking['status']);
    }

    public function testAuthResponseDecryptionFailure()
    {
        $this->mockAuthDecryptionFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });
    }

    // Authorization fails, but verify shows success
    // Results in a payment verification error
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    protected function createRefundsForExcel()
    {
        // Refund the payment above in full
        $refund = $this->doAuthCaptureAndRefundPayment($this->payment);

        // Create a new payment #2
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Do a partial refund of 10000 of payment #2
        $this->refundPayment($payment['id'], 10000);
        // Refund the remaining amount of the 2nd payment
        $this->refundPayment($payment['id']);
    }

    protected function alterRefundsDateToYesterday()
    {
        // Get all pending refunds
        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkRefundFileData($data)
    {
        $filePath = $data['netbanking_icici']['file'];

        // Data shows 3 refunds - payment 1 = full, payment 2 = 100 and 400. Payment 3 doesn't show up
        $this->assertEquals($data['netbanking_icici']['count'], 3);
        $this->assertTrue(file_exists($filePath));

        $sheet = Excel::load($filePath)->all()->toArray();

        $this->assertEquals(count($sheet[0]), 10);

        $this->assertEquals($sheet[0]['refund_amount'], 500);
        $this->assertEquals($sheet[1]['refund_amount'], 100);
        $this->assertEquals($sheet[2]['refund_amount'], 400);

        unlink($filePath);
    }

    protected function checkMailQueue()
    {
        Mail::assertSent(RefundFileMail::class, function ($mail)
        {
            $body = 'Please forward the ICICI Netbanking refunds file to UBPS operations team';

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals('1000.00', $mail->viewData['amount']);

            $this->assertEquals('3', $mail->viewData['count']);

            $this->assertEquals('emails.admin.icici_refunds', $mail->view);

            return true;
        });
    }

    protected function mockPaymentFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['PAID'] = 'N';
        });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['STATUS'] = 'FAILED';
        });
    }

    protected function mockStringVerifyResponse(string $response = '')
    {
        $this->mockServerContentFunction(function(&$content, $action = null) use ($response)
        {
            $content = $response;
        });
    }

    protected function mockAuthDecryptionFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'hash')
            {
                $content['ES'] = 'This_is_a_random_string';
            }
        });
    }
}
