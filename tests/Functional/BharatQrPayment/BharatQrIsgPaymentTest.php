<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Gateway\Base\Action;
use RZP\Gateway\Isg\Field;
use RZP\Gateway\Isg\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BharatQrIsgPaymentTest extends TestCase
{
	use PaymentTrait;

	public function setUp()
	{
		$this->testDataFilePath = __DIR__ . '/BharatQrIsgPaymentTestData.php';

		parent::setUp();

		$this->gateway = 'isg';

		$this->fixtures->create('terminal:bharat_qr_isg_terminal');

		$this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

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
		$request = $this->testData[__FUNCTION__];

		$qrCode = $this->createVirtualAccount();

		$this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

		$this->mockServerContentFunction(function (&$content, $action = null) use ($request)
		{
			if ($action === Action::VERIFY)
			{
				$content = $request['content'];
			}
		}, $this->gateway);

		$response = $this->makeRequestAndGetContent($request);

		$responseArray = json_decode($response['original'], true);

		$this->assertEquals(Status::APPROVED, $responseArray[Field::STATUS_CODE]);

		$this->assertEquals($request['content'][Field::TRANSACTION_ID], $responseArray[Field::TRANSACTION_ID]);

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
	}

	public function testQrPaymentFailedVerifyCallback()
	{
		$request = $this->testData["testQrPaymentProcess"];

		$qrCode = $this->createVirtualAccount();

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

		$responseArray = json_decode($response['original'], true);

		$this->assertEquals(Status::NO_RECORDS, $responseArray[Field::STATUS_CODE]);

		$this->assertEquals('Amount mismatch in Verify response and callback response',
							$responseArray[Field::STATUS_DESC]);

		$bharatQr = $this->getLastEntity('bharat_qr', true);

		$this->assertNull($bharatQr);

		$payment = $this->getLastEntity('payment', true);

		$this->assertNull($payment);
	}

	public function testVerifyQrPayment()
	{
		$request = $this->testData["testQrPaymentProcess"];

		$qrCode = $this->createVirtualAccount();

		$this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

		$this->mockServerContentFunction(function (&$content, $action = null) use ($request)
		{
			if ($action === Action::VERIFY)
			{
				$content = $request['content'];
			}
		}, $this->gateway);

		$response = $this->makeRequestAndGetContent($request);

		$responseArray = json_decode($response['original'], true);

		$this->assertEquals(Status::APPROVED, $responseArray[Field::STATUS_CODE]);

		$this->assertEquals($request['content'][Field::TRANSACTION_ID], $responseArray[Field::TRANSACTION_ID]);

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

	public function testBharatQrFailedPaymentCallback()
	{
		$request = $this->testData["testQrPaymentProcess"];

		$qrCode = $this->createVirtualAccount();

		$this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

		$this->mockServerContentFunction(function (&$content, $action = null) use ($request)
		{
			if ($action === Action::VERIFY)
			{
				$content = $request['content'];

				$content[Field::STATUS_CODE] = Status::NO_RECORDS;

				$content[Field::STATUS_DESC] = Status::getStatusCodeDescription(Status::NO_RECORDS);

			}
		}, $this->gateway);

		$response = $this->makeRequestAndGetContent($request);

		$responseArray = json_decode($response['original'], true);

		$this->assertEquals($responseArray[Field::STATUS_CODE], Status::NO_RECORDS);

		$this->assertEquals($responseArray[Field::STATUS_DESC], 'Transaction is declined by Isg Gateway');

		$bharatQr = $this->getLastEntity('bharat_qr', true);

		$this->assertNull($bharatQr);

		// Payment is automatically captured
		$payment = $this->getLastEntity('payment', true);

		$this->assertNull($payment);
	}
}
