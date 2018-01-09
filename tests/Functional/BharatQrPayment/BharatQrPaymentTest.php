<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Exception;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BharatQrPaymentTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures('bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->activate();

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();
    }

    public function testQrPaymentProcess()
    {
        $request = $this->testData[__FUNCTION__];

        $request['content']['PurchaseID'] = $this->qrCode['id'];

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

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);
    }

    public function testUnexpectedPayment()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        // Live because by default mode is live
        // if entity id is not given
        $bharatQr = $this->getLastEntity('bharat_qr', true, 'live');

        $payment =  $this->getLastEntity('payment', true, 'live');

        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($bharatQr['expected'], false);
    }

    public function testFailedPayment()
    {
        $request = $this->testData['testQrPaymentProcess'];

        unset($request['content']['F038']);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $response[0]);
    }

    public function testDuplicateNotification()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $request['content']['PurchaseID'] = $this->qrCode['id'];

        $response = $this->makeRequestAndGetContent($request);

        $response = $this->makeRequestAndGetContent($request);

        $bharatQr = $this->getEntities('bharat_qr', [], true);

        $this->assertEquals(count($bharatQr['items']) , 1);
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
