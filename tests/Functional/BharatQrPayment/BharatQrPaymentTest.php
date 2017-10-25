<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BharatQrPaymentTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures('bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->appAuth();
    }

    public function testQrPaymentProcess()
    {
        $request = $this->testData[__FUNCTION__];

        $request['content']['PurchaseID'] = $this->qrCode['id'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['valid']);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);
    }

    public function testUnexpectedPayment()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['valid']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($bharatQr['expected'], false);
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }
}
