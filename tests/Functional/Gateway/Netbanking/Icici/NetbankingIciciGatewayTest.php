<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici;

use Mail;
use Excel;
use Mockery;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;

use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Exception\GatewayErrorException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Gateway\Netbanking\Icici\ResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIciciGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_icici';

        $this->payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $this->setMockGatewayTrue();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testPayment()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayPayment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertEquals(9999999999, $gatewayPayment['bank_payment_id']);
    }

    public function testEmiPayment()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $this->payment['amount'] = '60000';

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testPayment'];

        $data['amount'] = 60000;

        $this->assertEquals('captured', $payment['status']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $gatewayData = $this->testData['testPaymentNetbankingEntity'];

        $gatewayData['amount'] = 600;

        $this->assertArraySelectiveEquals($gatewayData, $gatewayPayment);

        $this->assertEquals('CFL-000001118877-PRO', $gatewayPayment['bank_payment_id']);
    }

    public function testCallbackFailedDueDateMismatch()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $boundaryTime = Carbon::create(2018, 6, 21, 23, 58, 00,Timezone::IST);

        Carbon::setTestNow($boundaryTime);

        $iterator = 0;

        $this->mockServerContentFunction(function(& $content, $action) use (& $iterator)
        {
            if ($action === 'verify')
            {
                if ($iterator === 0)
                {
                    $content[ResponseFields::STATUS] = 'failed';
                }

                $iterator++;
            }
        });

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals('captured',$payment['status']);

        $this->assertSame(2, $iterator);

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->fixtures->edit('netbanking', $netbanking['id'], ['date' => null]);

        $this->verifyPayment($payment['id']);

        $this->assertSame(3, $iterator);

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($netbanking['date']);
    }

    /**
     * Backward compatibility test
     **/
    public function testRetailPaymentWithCorpTerminalPresent()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_icici_corp_terminal');

        $this->testPayment();
    }

    public function testPaymentCorporate()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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

    public function testCorporatePendingPayment()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_icici_corp_terminal');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $this->payment = $this->getDefaultNetbankingPaymentArray('ICIC_C');

        $server = $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'auth')
            {
                $content['PAID'] = 'P';
                unset($content['BID']);
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC_C');

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('created', $payment['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION', $payment['internal_error_code']);

        $this->mockCheckerCallbackForPaymentFromBank($server, $payment);

        // Entry refreshed with the actual payment
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testAmountTampering()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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

    public function testVerifyAmountMismatch()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $this->mockAmountMismatch();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });
    }

    public function testPaymentVerify()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefund()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $refund = $this->doAuthCaptureAndRefundPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 50000);
        $this->assertEquals($payment['amount'], $refund['amount']);
    }

    public function testPartialRefund()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund the payment above partially
        $refund = $this->refundPayment($payment['id'], 10000);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 10000);
        $this->assertEquals($refund['amount'], 10000);
    }

    public function testFailedRefund()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockVerifyFailure();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['razorpay_payment_id']);
            });

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertNull($netbanking['date']);
    }

    public function testEmptyVerifyResponse()
    {
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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
        $this->markTestSkipped('This test is depricated and moved to nbplus service');

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

    public function testAuthorizeFailedPayment()
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing(function(& $content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED',
                        'gateway_error_code'                            =>  '',
                        'gateway_error_description'                     =>  '',
                    ]
                ],
            ];
        });

        $this->makeRequestAndCatchException(function ()
        {
            $this->doAuthAndCapturePayment($this->payment);
        }, GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEmpty($payment['transaction_id']);

        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        $content['payment']['id'] = substr($payment['id'],4);

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference1']);
    }

    /**
     * Validate negative case of authorizing succesfulpayment
     */
    public function testForceAuthorizeSucessfulPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        $content['payment']['id'] = substr($payment['id'],4);

        $content['meta']['force_auth_payment'] = true;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/nbplus/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        unset($content['payment']['amount']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/nbplus/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testVerifyAuthorizeFailedPayment()
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED',
                            'gateway_error_code'                            =>  '',
                            'gateway_error_description'                     =>  '',
                        ]
                    ],
                ];
            }
        });

        $this->makeRequestAndCatchException(function ()
        {
            $this->doAuthAndCapturePayment($this->payment);
        }, GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        $content['payment']['id'] = substr($payment['id'], 4);

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference1']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
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

        $sheet = (new ExcelImport)->toArray($filePath)[0];

        $this->assertEquals(count($sheet[0]), 10);

        $this->assertEquals($sheet[0]['refund_amount'], 500);
        $this->assertEquals($sheet[1]['refund_amount'], 100);
        $this->assertEquals($sheet[2]['refund_amount'], 400);

        unlink($filePath);
    }

    protected function checkMailQueue()
    {
        Mail::assertQueued(RefundFileMail::class, function ($mail)
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

    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::AMOUNT] = '300';
            }
        });
    }

    protected function mockCheckerCallbackForPaymentFromBank($server, $payment)
    {
        // Initial pending reponse based entry
        list($content, $url) = $server->getCheckerCallbackForPaymentFromBank($payment);

        $request = [
            'content' => $content,
            'url'     => $url,
            'method'  => 'post'
        ];

        $this->sendRequest($request);
    }

    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/nbplus/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
