<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class QrPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/QrPaymentTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->bharatQr = $this->createVirtualAccount();

        $this->ba->appAuth();
    }

    public function testQrPaymentProcess()
    {
        $request = $this->testData[__FUNCTION__];

        $request['content']['PurchaseID'] = $this->bharatQr['id'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['valid']);

        //Created Qr Entity As Expected
        $qr = $this->getLastEntity('qr', true);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($qr['payment_id'], $payment['id']);
        $this->assertEquals($qr['expected'], true);
    }

    public function testUnexpectedPayment()
    {
        $request = $this->testData['testQrPaymentProcess'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['valid']);

        $qr = $this->getLastEntity('qr', true);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($qr['expected'], false);
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
