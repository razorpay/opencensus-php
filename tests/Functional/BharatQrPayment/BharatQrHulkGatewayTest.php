<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Models\Terminal\Type;
use RZP\Models\Payment\Method;
use RZP\Gateway\Upi\Hulk\Fields;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Hulk\Mock\Server;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrHulkGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__ . '/BharatQrHulkGatewayTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->activate();

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway_merchant_id'       => 'vpa_TstMrchtVpaBqr',
            'vpa'                       => 'TstMerchantVPA.bqr@razor',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::BHARAT_QR => '1',
            ],
        ];

        $this->fixtures->create('terminal:shared_upi_hulk_terminal', $attributes);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');

        $this->gateway = 'upi_hulk';
    }

    public function testBharatQrPayment()
    {
        $qrCode = $this->createVirtualAccount();

        $request = $this->getMockServer()->fillBharatQrNotification(substr($qrCode[Fields::ID], 3));

        $requestArray = json_decode($request[Fields::RAW], true);

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $responseBody = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $responseBody[0]);

        $upiEntity = $this->getDbLastEntity(Entity::UPI);

        $this->assertNotNull($upiEntity);

        $this->assertEquals('50000', $upiEntity['amount']);

        $this->assertEquals('pay', $upiEntity['type']);

        $this->assertEquals('vishnu@icici', $upiEntity['vpa']);

        $this->assertEquals($requestArray[Fields::DATA][Fields::MERCHANT_REFERENCE_ID],
            $upiEntity['merchant_reference']);

        $this->assertEquals('completed', $upiEntity['status_code']);

        $this->assertTrue($upiEntity['received']);

        $this->assertEquals($requestArray[Fields::DATA][Fields::ID], $upiEntity['gateway_payment_id']);

        $paymentEntity = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals(Method::UPI, $paymentEntity['method']);

        $this->assertEquals('captured', $paymentEntity['status']);

        $this->assertEquals('50000', $paymentEntity['amount']);

        $this->assertEquals(Entity::UPI_HULK, $paymentEntity['gateway']);

        $this->assertEquals(Entity::QR_CODE, $paymentEntity['receiver_type']);

        $this->assertEquals('10000000000000', $paymentEntity['merchant_id']);

        $bharatQrEntity = $this->getDbLastEntity(Entity::BHARAT_QR);

        $this->assertNotNull($bharatQrEntity);

        $this->assertEquals($bharatQrEntity['expected'], true);
    }

    public function testUnexpectedPayment()
    {
        $request = $this->getMockServer()->fillBharatQrNotification();

        $requestArray = json_decode($request[Fields::RAW], true);

        $this->ba->directAuth();

        $this->fixtures->edit(
            'merchant',
            '10000000000000',
            [
                'pricing_plan_id' => '1hDYlICobzOCYt',
            ]);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $upiEntity = $this->getDbLastEntity(Entity::UPI, Mode::LIVE);

        $this->assertNotNull($upiEntity);

        $this->assertEquals('50000', $upiEntity['amount']);

        $this->assertEquals('pay', $upiEntity['type']);

        $this->assertEquals('vishnu@icici', $upiEntity['vpa']);

        $this->assertEquals($requestArray[Fields::DATA][Fields::MERCHANT_REFERENCE_ID],
            $upiEntity['merchant_reference']);

        $this->assertEquals('completed', $upiEntity['status_code']);

        $this->assertTrue($upiEntity['received']);

        $this->assertEquals($requestArray[Fields::DATA][Fields::ID], $upiEntity['gateway_payment_id']);

        // Live because by default mode is live
        // if entity id is not given
        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');

        $payment =  $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['expected'], false);

        $virtualAccount =  $this->getDbLastEntity('virtual_account', 'live');

        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertEquals('ShrdVirtualAcc', $virtualAccount['id']);
        $this->assertEquals('active', $virtualAccount['status']);
    }

    public function testDuplicateNotification()
    {
        $qrCode = $this->createVirtualAccount();

        $request = $this->getMockServer()->fillBharatQrNotification(substr($qrCode[Fields::ID], 3));

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($request);

        $duplicateResponse = $this->makeRequestAndGetContent($request);

        $xmlResponse = $duplicateResponse['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $bharatQr = $this->getDbEntities('bharat_qr', []);

        $this->assertEquals(count($bharatQr) , 1);
    }

    public function testUnexpectedPaymentWithTerminalUnexpected()
    {
        $request = $this->getMockServer()->fillBharatQrNotification();

        $this->fixtures->edit(
            'merchant',
            '10000000000000',
            [
                'pricing_plan_id' => '1hDYlICobzOCYt',
            ]);

        $this->fixtures->on('live')->edit(
            'terminal',
            '100UPIHulkTrml',
            [
                'expected' => true
            ]);

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        // Live because by default mode is live
        // if entity id is not given
        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');

        $payment =  $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['expected'], true);

        $virtualAccount =  $this->getDbLastEntity('virtual_account', 'live');

        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertNotEquals('ShrdVirtualAcc', $virtualAccount['id']);
        $this->assertEquals('active', $virtualAccount['status']);
    }

    public function testPaymentWithInValidCallbackSignature()
    {
        $qrCode = $this->createVirtualAccount();

        $request = $this->getMockServer()->fillBharatQrNotification(substr($qrCode[Fields::ID], 3));

        $request['server']['HTTP_X-Hulk-Signature'] = 'random_signature';

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $responseBody = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $responseBody[0]);

        $upiEntity = $this->getDbLastEntity(Entity::UPI);

        $this->assertNull($upiEntity);

        $paymentEntity = $this->getDbLastEntity(Entity::PAYMENT);

        $bharatQrEntity = $this->getDbLastEntity(Entity::BHARAT_QR);

        $this->assertNull($bharatQrEntity);

        $this->assertNull($paymentEntity);
    }

    public function testValidateBharatQrPayment()
    {
        $request = $this->getMockServer()->getBharatQrValidateData();

        $response = $this->makeRequestAndGetContent($request);

        assertTrue($response['success']);

        $this->testBharatQrPayment();
    }

    public function testBharatQrPaymentWithInvalidType()
    {
        $qrCode = $this->createVirtualAccount();

        $request = $this->getMockServer()->fillBharatQrNotification(substr($qrCode[Fields::ID], 3));

        $request[Fields::RAW] = preg_replace('/push/', 'random', $request[Fields::RAW]);

        $request['server']['HTTP_X-Hulk-Signature'] = Server::getHmac($request[Fields::RAW]);

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $responseBody = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $responseBody[0]);
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
