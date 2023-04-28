<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Gateway\Base\Action;
use RZP\Gateway\Isg\Entity;
use RZP\Gateway\Isg\Field;
use RZP\Gateway\Isg\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrIsgGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BharatQrIsgGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'isg';

        $this->fixtures->create('terminal:bharat_qr_isg_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

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

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::APPROVED, $response[Field::STATUS_CODE]);

        $this->assertEquals($request['content'][Field::TRANSACTION_ID], $response[Field::TRANSACTION_ID]);

        $gatewayPayment = $this->getLastEntity('isg', true);

        $this->assertEquals($response[Field::NOTIFICATION_REF_NO], $gatewayPayment['payment_id']);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(100, $payment['amount']);

        $this->assertEquals('isg', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        return $payment;
    }

    public function testQrPaymentBadVerifyCallback()
    {
        $this->markTestSkipped();
        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $this->mockServerContentFunction(function (&$content, $action = null) use ($request)
        {
            if ($action === Action::VERIFY)
            {
                $content = $request['content'];

                $content[Field::TRANSACTION_AMOUNT] = $content[Field::TRANSACTION_AMOUNT] * 2;
            }
        }, $this->gateway);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::NO_RECORDS, $response[Field::STATUS_CODE]);

        $this->assertNull($response[Field::NOTIFICATION_REF_NO]);

        $this->assertEquals('Amount mismatch in Verify response and callback response', $response[Field::STATUS_DESC]);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $this->assertNull($bharatQr);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment);
    }

    public function testVerifyQrPayment()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::APPROVED, $response[Field::STATUS_CODE]);

        $this->assertEquals($request['content'][Field::TRANSACTION_ID], $response[Field::TRANSACTION_ID]);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(100, $payment['amount']);

        $this->assertEquals('isg', $payment['gateway']);

        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $this->assertEquals($bharatQr['expected'], true);

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['verified'], 1);
    }

    public function testBharatQrFailedVerifyCallback()
    {
        $this->markTestSkipped();

        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $request['content'][Field::STATUS_CODE] = Status::NO_RECORDS;

        $request['content'][Field::STATUS_DESC] = Status::getStatusCodeDescription(Status::NO_RECORDS);

        $this->mockServerContentFunction(function (&$content, $action = null) use ($request)
        {
            if ($action === Action::VERIFY)
            {
                $content = $request['content'];
            }
        }, $this->gateway);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response[Field::STATUS_CODE], Status::NO_RECORDS);

        $this->assertEquals($response[Field::STATUS_DESC], 'No records present for given transaction in Isg Gateway');

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $this->assertNull($bharatQr);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment);
    }

    public function testDuplicateNotification()
    {
        $this->markTestSkipped();
        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $request = $this->testData['testQrPaymentProcess'];

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $response = $this->makeRequestAndGetContent($request);

        $duplicateResponse = $this->makeRequestAndGetContent($request);

        $this->assertEquals($duplicateResponse[Field::STATUS_DESC], Status::SUCCESS);

        $bharatQr = $this->getDbEntities('bharat_qr', []);

        $this->assertEquals(count($bharatQr) , 1);
    }

    public function testDecryptionFailureInPaymentNotification()
    {
        $this->markTestSkipped();
        $request = $this->testData['testQrPaymentProcess'];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $request['content'][Field::CONSUMER_PAN] = 'random';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::NO_RECORDS, $response[Field::STATUS_CODE]);

        $this->assertEquals('Input string cannot be decrypted', $response[Field::STATUS_DESC]);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        $this->assertNull($bharatQr);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment);
    }

    public function testPaymentRefund()
    {
        $this->markTestSkipped();
        $payment = $this->testQrPaymentProcess();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);

        $gatewayEntity = $this->getLastEntity('isg', true);

        $this->assertEquals('00', $gatewayEntity[Entity::STATUS_CODE]);
    }

    public function testPaymentFailedRefund()
    {
        $this->markTestSkipped();
        $payment = $this->testQrPaymentProcess();

        $this->mockServerContentFunction(function (&$content, $action = null) {

            if ($action === 'refund')
            {
                $content[Field::STATUS_DESC] = 'Refund amount more than payment amount';
                $content[Field::STATUS_CODE] = '01';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $gatewayEntity = $this->getLastEntity('isg', true);

        $this->assertEquals('01', $gatewayEntity[Entity::STATUS_CODE]);
    }

    public function testPartialRefund()
    {
        $this->markTestSkipped();
        $request = $this->testData[__FUNCTION__];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $this->mockServerContentFunction(function (&$content, $action = null) {

            $content[Field::TRANSACTION_AMOUNT] = '2.00';
        });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::APPROVED, $response[Field::STATUS_CODE]);

        $this->assertEquals($request['content'][Field::TRANSACTION_ID], $response[Field::TRANSACTION_ID]);

        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id'], '100');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);

        $gatewayEntity = $this->getLastEntity('isg', true);

        $this->assertEquals('00', $gatewayEntity[Entity::STATUS_CODE]);

        $this->assertEquals('100', $gatewayEntity['amount']);
    }
}
