<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Gateway\Base\Action;
use RZP\Gateway\Worldline\Fields;
use RZP\Gateway\Worldline\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrWorldlineGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BharatQrWorldlineGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'worldline';

        $this->fixtures->create('terminal:bharat_qr_worldline_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->activate();
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    public function testQrPaymentProcess()
    {
        $this->markTestSkipped();
        $request = $this->testData[__FUNCTION__];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlinePaymentAuthorizeEntity'], $gatewayPayment);
    }

    public function testUpiQrPaymentProcess()
    {
        $this->markTestSkipped();
        $request = $this->testData[__FUNCTION__];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlineUpiPaymentAuthorizeEntity'], $gatewayPayment);
    }

    public function testVerifyQrPayment()
    {
        // skipping because we don't verify worldline callback currently
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['verified'], 1);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlinePaymentAuthorizeEntity'], $gatewayPayment);
    }

    public function testFailedVerifyQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    Fields::STATUS          => 'PENDING',
                    Fields::MESSAGE         => 'Transaction Not Found',
                    Fields::RESPONSE_OBJECT => null,
                ];
            }

            return $content;
        });

        $testData = $this->testData['testVerifyInvalidResponse'];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlinePaymentAuthorizeEntity'], $gatewayPayment);
    }

    public function testFailedVerifyCallbackQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    Fields::STATUS          => 'PENDING',
                    Fields::MESSAGE         => 'Transaction Not Found',
                    Fields::RESPONSE_OBJECT => null,
                ];
            }

            return $content;
        });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Failure', $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);
        $payment = $this->getLastEntity('payment', true);
        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertEquals(null, $bharatQr);
        $this->assertEquals(null, $payment);
        $this->assertEquals(null, $gatewayPayment);
    }

    public function testVerifyUpiQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['verified'], 1);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlineUpiPaymentAuthorizeEntity'], $gatewayPayment);
    }

    public function testDuplicateNotification()
    {
        $this->markTestSkipped();
        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testQrPaymentProcess'];

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $duplicateResponse = $this->makeRequestAndGetContent($request);

        $this->assertEquals($duplicateResponse[Fields::STATUS], Status::SUCCESS);

        $bharatQr = $this->getDbEntities('bharat_qr', []);

        $this->assertEquals(count($bharatQr) , 1);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlinePaymentAuthorizeEntity'], $gatewayPayment);
    }

    public function testRefundQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['body']['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['verified'], 1);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlinePaymentAuthorizeEntity'], $gatewayPayment);

        $response = $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
        $this->assertEquals(true, $refund['gateway_refunded']);

        $gatewayRefund = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlineRefundEntity'], $gatewayRefund);

    }

    public function testFailedRefundQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['body']['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['verified'], 1);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlinePaymentAuthorizeEntity'], $gatewayPayment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content = [
                    Fields::STATUS          => 'FAILED',
                    Fields::MESSAGE         => 'Duplicate RefundId',
                    Fields::RESPONSE_OBJECT => null,
                ];
            }

            return $content;
        });

        $response = $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('failed', $refund['status']);
    }

    public function testRefundUpiQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testUpiQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::SUCCESS, $response['body']['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(20000, $payment['amount']);

        $this->assertEquals('worldline', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $response = $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlineUpiPaymentAuthorizeEntity'], $gatewayPayment);

        $this->assertSame($response['payment']['verified'], 1);

        $response = $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testWorldlineRefundEntity'], $gatewayPayment);

    }

    public function testFailedVerifyCallbackAmountMismatchQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[Fields::RESPONSE_OBJECT][Fields::TXN_AMOUNT] = '1.00';
            }

            return $content;
        });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Failure', $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);
        $payment = $this->getLastEntity('payment', true);
        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertEquals(null, $bharatQr);
        $this->assertEquals(null, $payment);
        $this->assertEquals(null, $gatewayPayment);
    }

    public function testFailedVerifyCallbackMerchantMpanMismatchQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[Fields::RESPONSE_OBJECT][Fields::M_PAN] = '4604901004774121';
            }

            return $content;
        });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Failure', $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);
        $payment = $this->getLastEntity('payment', true);
        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertEquals(null, $bharatQr);
        $this->assertEquals(null, $payment);
        $this->assertEquals(null, $gatewayPayment);
    }

    public function testFailedVerifyCallbackConsumerMpanMismatchQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('worldline')->fillBharatQrCallback($request['content'], $qrCode['reference']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[Fields::RESPONSE_OBJECT][Fields::CONSUMER_PAN] = '';
            }

            return $content;
        });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Failure', $response['status']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);
        $payment = $this->getLastEntity('payment', true);
        $gatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertEquals(null, $bharatQr);
        $this->assertEquals(null, $payment);
        $this->assertEquals(null, $gatewayPayment);
    }
}
