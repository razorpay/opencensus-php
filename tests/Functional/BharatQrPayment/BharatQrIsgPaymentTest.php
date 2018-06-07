<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Gateway\Base\Action;
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

		$this->fixtures->merchant->addFeatures(['virtual_accounts','bharat_qr']);

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

		$this->mockServerContentFunction(function(&$content, $action = null) use ($request)
		{
			if ($action === Action::VERIFY)
			{
				$content = $request['content'];
			}
		},$this->gateway);

		$response = $this->makeRequestAndGetContent($request);

		$response = $this->parseResponseXml($response['original']);

		$this->assertEquals('OK', $response[0]);

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

	protected function parseResponseXml(string $response): array
	{
		return (array) simplexml_load_string(trim($response));
	}
}
