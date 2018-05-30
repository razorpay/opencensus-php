<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Exception;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrPaymentTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BharatQrPaymentTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->activate();

        $this->t1 = $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->t2 = $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
    }

    public function testQrPaymentProcess()
    {
        $request = $this->testData[__FUNCTION__];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId);

        $request['content'] = $content;

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

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('Random Name', $card['name']);
    }

    public function testMakeTestPayments()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $request = $this->testData[__FUNCTION__];

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->proxyAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $content = [
            'reference' => $qrCodeId,
            'method'    => 'card',
            'amount'    => '100',
        ];

        $request['content'] = $content;

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

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('Razorpay', $card['name']);
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

    public function testUpiQrPaymentProcess()
    {
        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData[__FUNCTION__];

        $qrCodeId = substr($this->qrCode['id'], 3);

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
}
