<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class NonVirtualAccountQrCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateBharatQrCode()
    {
        $response = $this->createQrCode();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testCreateUpiQrCode()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testCreateUpiQrCodeUpiIntentLinkExposure()
    {
        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData['testCreateUpiQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->assertArrayHasKey('image_content', $response);

        $this->runEntityAssertions($response);
    }

    public function testCloseQrCode()
    {
        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $closeResponse = $this->closeQrCode($response['id']);

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $this->runEntityAssertions($closeResponse);
    }

    private function runEntityAssertions($response)
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->assertNotNull($qrCodeEntity['short_url']);
        $tr = 'RZP' . substr($response['id'], 3, 14) . 'qrv2';
        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
        $this->assertStringContainsString('qrmoremegast', $qrCodeEntity['qr_string']);
        $this->assertStringContainsString('@icici', $qrCodeEntity['qr_string']);
    }

    public function testProcessIciciQrPayment()
    {
        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $this->ba->directAuth();

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrPayment['expected'], true);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentForQrNotFound()
    {
        $this->ba->directAuth();

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'H1234567890abcqrv2';

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getLastEntity('payment', true, 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
        $this->assertEquals($qrPayment['expected'], false);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentOnClosedQrCode()
    {
        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $qrCode = $this->closeQrCode($qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);

        $this->ba->directAuth();

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];
        $response = $this->parseResponseXml($xmlResponse);
        $this->assertEquals('OK', $response[0]);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals($qrPayment['expected'], false);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentOnSingleUseQrCode()
    {
        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer');

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $this->ba->directAuth();

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];
        $response    = $this->parseResponseXml($xmlResponse);
        $this->assertEquals('OK', $response[0]);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment   = $this->getLastEntity('payment', true, 'live');
        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals($qrPayment['expected'], true);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testCardQrPaymentProcess()
    {
        $qrCode = $this->createQrCode();

        $this->ba->directAuth();

        $qrCodeId = substr($qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId . 'qrv2', 'random123');

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
        $qrPayment = $this->getLastEntity('qr_payment', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(true, $qrPayment['expected']);
        $this->assertEquals('random123', $qrPayment['provider_reference_id']);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('Random Name', $card['name']);
    }

    public function testQrCodeTestPayments()
    {
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $qrCode = $this->createQrCode();

        $qrCodeId = substr($qrCode['id'], 3);

        $this->ba->privateAuth();

        $content = [
            'reference' => $qrCodeId . 'qrv2',
            'method'    => 'upi',
            'amount'    => '100',
        ];

        $request['content'] = $content;

        $request['method'] = 'post';

        $request['url'] = '/bharatqr/pay/test';

        $this->makeRequestAndGetContent($request);

        //Created Qr Entity As Expected
        $qrPayment = $this->getLastEntity('qr_payment', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrPayment['expected'], true);
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    public function testReminderCallback()
    {
        $input = $this -> getDefaultQrCodeRequestArray();

        $input['close_by'] = Carbon::now()->getTimestamp() + 1000;

        $qrCode = $this->createQrCode($input);

        $qrCodeId = $qrCode['id'];

        $testData = $this->testData[__FUNCTION__];

        $callback_url = $testData['base_url'].$qrCodeId;

        $request = [
            'method'  => 'POST',

            'url'     => $callback_url
        ];

        $this->ba->reminderAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $qrCodeEntity= $this->getDbLastEntity('qr_code');

        $this->assertEquals($testData['expected_status'],$qrCodeEntity->getStatus());
        
    }


    public function testFetchQrCodePayments()
    {
        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $this->processPaymentForQr($qrCodeId);

        $expectedResponse = $this->testData['testFetchPaymentsrQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment());
    }

    public function testFetchPaymentsForQrCode()
    {
        $this->markTestSkipped();

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $this->processPaymentForQr($qrCodeId);

        $expectedResponse = $this->testData['testFetchPaymentsrQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment($qrCode['id']));
    }

    protected function processPaymentForQr($qrCodeId)
    {
        $this->ba->directAuth();

        $request = $this->testData['testProcessIciciQrPayment'];

        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $content = $this->getMockServer(Gateway::UPI_ICICI)
                        ->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

    }
}
