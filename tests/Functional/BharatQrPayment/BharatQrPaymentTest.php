<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Gateway\Upi\Icici\Fields;
use RZP\Models\Merchant\Account;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Hitachi\ResponseFields;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class BharatQrPaymentTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BharatQrPaymentTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->activate();

        $this->t1 = $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->t2 = $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $this->t3 = $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->gateway = 'hitachi';
    }

    public function testQrPaymentProcess()
    {
        $request = $this->testData[__FUNCTION__];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        // Notes from the VA are copied over to the payment
        $this->assertArrayHasKey('notes', $payment);
        $this->assertArrayHasKey('key', $payment['notes']);
        $this->assertEquals('value', $payment['notes']['key']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);
    }

    public function testFetchBQRPaymentForBankReference()
    {
        $this->qrCode = $this->createVirtualAccount();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $response = $this->fetchVirtualAccountPayments(null, 'somethingabc');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testQrPaymentProcessForFailedPayment()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        // marking the payment failed
        $input = [
            ResponseFields::STATUS_CODE => '01'
        ];

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId, null, $input);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $response[0]);

        $bharatQr = $this->getLastEntity('bharat_qr', true);
        $this->assertNull($bharatQr);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment);
    }

    public function testQrPaymentProcessWithoutCardName()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId, null, [
           'SenderName' => '   ',
        ]);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        // Notes from the VA are copied over to the payment
        $this->assertArrayHasKey('notes', $payment);
        $this->assertArrayHasKey('key', $payment['notes']);
        $this->assertEquals('value', $payment['notes']['key']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);
    }

    public function testMakeTestPayments()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->proxyAuth();

        $content = [
            'reference' => $this->qrCode['id'],
            'method'    => 'card',
            'amount'    => '100',
        ];

        $request['content'] = $content;

        $request['method'] = 'post';

        $request['url'] = '/bharatqr/pay/test';

        $response = $this->makeRequestAndGetContent($request);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);
    }

    public function testMakeTestPaymentsViaScService()
    {
        $this->enableRazorXTreatmentForRoutingFromApiToScService();

        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->proxyAuth();

        $content = [
            'reference' => $this->qrCode['id'],
            'method'    => 'card',
            'amount'    => '100',
        ];

        $request['content'] = $content;

        $request['method'] = 'post';

        $request['url'] = '/bharatqr/pay/test';

        $response = $this->makeRequestAndGetContent($request);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);
    }

    protected function enableRazorXTreatmentForRoutingFromApiToScService()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === RazorxTreatment::SMARTCOLLECT_SERVICE_QR_PAYMENTS_CALLBACK)
                    {
                        return 'on';
                    }

                    return 'off';
                }));
    }

    public function testMakeTestPaymentSuccess()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->qrCode = $this->createVirtualAccount();

        $this->testData[__FUNCTION__]['request']['content']['reference'] = $this->qrCode['id'];

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals($payment['receiver_id'], $this->qrCode['reference']);
    }

    public function testMakeTestPaymentFailure()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->qrCode = $this->createVirtualAccount();

        $this->testData[__FUNCTION__]['request']['content']['reference'] = $this->qrCode['id'].'invalidlength';

        $this->startTest();
    }

    public function testMakeUnexpectedTestPayments()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $t = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->edit('terminal', $t['id'], ['expected' => true]);

        $request = [
            'url'     => '/bharatqr/pay/test',
            'method'  => 'post',
            'content' => [
                'reference' => 'randomrefrandomre',
                'method'    => 'card',
                'amount'    => '100',
            ]
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);
        $this->assertEquals('', $card['name']);
    }


    public function testHitachiVerifyAndRefund()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('hitachi', true);

        $this->assertEquals('refund', $refund['action']);
    }

    public function testWithGlobalCustomer()
    {
        $this->ba->privateAuth();

        $request = $this->testData['createVirtualAccount'];

        $request['content']['customer_id'] = 'cust_100011customer';

        $response = $this->makeRequestAndGetContent($request);

        $qrCode = $response['receivers'][0];

        $this->ba->directAuth();

        $qrCodeId = substr($qrCode['id'], 3);

        $request = $this->testData['testQrPaymentProcess'];

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('hitachi', true);

        $this->assertEquals('refund', $refund['action']);
    }

    public function testHitachiBadCheckSum()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        $content['CheckSum'] = 'random';

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $response[0]);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment);
    }

    public function testUnexpectedPayment()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->fixtures->edit(
            'merchant',
            '10000000000000',
            [
                'pricing_plan_id' => '1hDYlICobzOCYt',
            ]);

        $this->ba->directAuth();

        $content = $this->getMockServer('hitachi')->getBharatQrCallback('tobefilled');

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        // Live because by default mode is live
        // if entity id is not given
        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');

        $payment =  $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['expected'], false);

        $virtualAccount =  $this->getDbLastEntity('virtual_account', 'live');

        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertEquals('ShrdVirtualAcc', $virtualAccount['id']);
        $this->assertEquals('active', $virtualAccount['status']);
    }

    public function testUnexpectedPaymentWithTerminalUnexpected()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->fixtures->edit(
            'merchant',
            '10000000000000',
            [
                'pricing_plan_id' => '1hDYlICobzOCYt',
            ]);

        $this->fixtures->on('live')->edit(
            'terminal',
            $this->t3['id'],
            [
                'expected' => true
            ]);

        $this->ba->directAuth();

        $content = $this->getMockServer('hitachi')->getBharatQrCallback('tobefilled');

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        // Live because by default mode is live
        // if entity id is not given
        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');

        $payment =  $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['expected'], true);

        $virtualAccount =  $this->getDbLastEntity('virtual_account', 'live');

        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertNotEquals('ShrdVirtualAcc', $virtualAccount['id']);
        $this->assertEquals('active', $virtualAccount['status']);

        $qrCode = $this->getDbLastEntity('qr_code','live');

        $vid = $virtualAccount['id'];

        $content = $this->getMockServer('hitachi')->getBharatQrCallback('tobefilled', 'abc');

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $virtualAccount =  $this->getDbLastEntity('virtual_account', 'live');

        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');

        $this->assertEquals($bharatQr['expected'], true);

        $this->assertEquals($vid, $virtualAccount['id']);

        $this->assertEquals(400, $virtualAccount['amount_paid']);

    }

    public function testUpiQrPaymentProcess()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData[__FUNCTION__];

        $qrCodeId = substr($this->qrCode['id'], 3);

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId;

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);
        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        //Check RRN capture
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testUpiQrPaymentProcessAmountMismatch()
    {
        $this->testData['createVirtualAccount']['content']['amount_expected'] = 10001;

        $this->qrCode = $this->createVirtualAccount();

        $va = $this->getDbLastEntity('virtual_account');

        $this->assertSame(10001, $va->getAmountExpected());

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        // For VPA type receiver as the shared sharp terminal is not seeded
//        $this->fixtures->create('terminal:vpa_shared_terminal');
        $this->fixtures->create('terminal:shared_bank_account_terminal');

        $this->ba->directAuth();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $qrCodeId = substr($this->qrCode['id'], 3);

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId;

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getDbLastEntity('bharat_qr');
        // Payment is automatically captured
        $payment = $this->getDbLastPayment();

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], false);

        //Check RRN capture
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);

        $va = $this->getDbLastEntity('virtual_account');
        $this->assertSame('ShrdVirtualAcc', $va->getId());

        $qrCode = $this->getDbLastEntity('qr_code');
        $this->assertSame($qrCode->getId(), $payment->receiver_id);

        $this->assertSame($va->getId(), $qrCode->entity_id);
    }

    public function testUpiQrPaymentProcessForFailedPayment()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $request['content'][Fields::TXN_STATUS] = 'FAILURE';

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request['content']['merchantTranId'] = $qrCodeId;

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $this->assertNull($bharatQr);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment);
    }

    public function testUpiVerifyAndRefundPayment()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request['content']['merchantTranId'] = $qrCodeId;

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);

        $this->refundPayment($payment['id']);
    }

    public function testFailedPayment()
    {
        $this->markTestSkipped('We wont be getting notifications for failed payments');

        $request = $this->testData['testQrPaymentProcess'];

        unset($request['content']['F038']);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $response[0]);
    }

    public function testDuplicateNotification()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCodeId = substr($this->qrCode['id'], 3);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $response = $this->makeRequestAndGetContent($request);

        $bharatQr = $this->getDbEntities('bharat_qr', []);

        $this->assertEquals(count($bharatQr) , 1);
    }

    public function testQrPaymentProcessWithZeroesPrependedToQrId()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        // tampering the qrcode id
        $qrCodeIdWithZeroes = '000' . $qrCodeId . '000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['F002'] = '416021XXXXXX3047';
        });

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeIdWithZeroes);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        // Notes from the VA are copied over to the payment
        $this->assertArrayHasKey('notes', $payment);
        $this->assertArrayHasKey('key', $payment['notes']);
        $this->assertEquals('value', $payment['notes']['key']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('Random Name', $card['name']);
    }

    public function testQrPaymentWithMerchantDetails()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $qrCode = $this->getLastEntity('qr_code', true);

        $this->assertMatchesRegularExpression('/5399/', $qrCode['qr_string']);

        $this->assertMatchesRegularExpression('/2223330048827001/', $qrCode['qr_string']);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        // Notes from the VA are copied over to the payment
        $this->assertArrayHasKey('notes', $payment);
        $this->assertArrayHasKey('key', $payment['notes']);
        $this->assertEquals('value', $payment['notes']['key']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    public function testUpiQrPaymentProcessWithCardNumberedVpa()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $qrCodeId = substr($this->qrCode['id'], 3);

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId;
        $request['content']['PayerVA'] = "9931724380000000@paytm";

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);
        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        //Check RRN capture
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    protected function processDuplicateUpiQrPayment($request)
    {
        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);
        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($request['content']['PayerAmount'] * 100, $payment['amount']);
        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        //Check RRN capture
        $rrn = '000011100101';
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testDuplicateUpiQrPaymentProcess()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $qrCodeId = substr($this->qrCode['id'], 3);

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId;
        $request['content']['PayerAmount'] = 100;

        $this->processDuplicateUpiQrPayment($request);

        $request['content']['PayerAmount'] = 200;
        $this->processDuplicateUpiQrPayment($request);
    }
}
