<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Airtelmoney;

use Carbon\Carbon;

use RZP\Http\Route;
use RZP\Gateway\Wallet\Airtelmoney\TestAmount;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class AirtelmoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AirtelmoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_airtelmoney_terminal');

        $this->gateway = 'wallet_airtelmoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testPaymentFailureFlow()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = ((float) TestAmount::FAIL_PAYMENT_AMOUNT) * 100;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('unknown', $payment['two_factor_auth']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testFailedPaymentWalletEntity');
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create(
            'payment:failed',
            [
                'email'         => 'a@b.com',
                'amount'        => 50000,
                'contact'       => '9918899029',
                'method'        => 'wallet',
                'wallet'        => 'airtelmoney',
                'gateway'       => 'wallet_airtelmoney',
                'card_id'       => null,
                'terminal_id'   => $this->sharedTerminal->id
            ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testPartialRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        // Refund half the amount
        $this->refundPayment($capturePayment['id'], $payment['amount']/2);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = ((float) TestAmount::FAIL_REFUND_AMOUNT) * 100;

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $capturePaymentId = $capturePayment['id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($capturePaymentId)
        {
            $this->refundPayment($capturePaymentId);
        });

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund, 'testRefundFailedPaymentEntity');
    }

    public function testRefundExcelFile()
    {
        $defaultPayment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment = $this->doAuthAndCapturePayment($defaultPayment);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForAirtelmoneyWallet();

        $this->assertEquals(4, $data['wallet_airtelmoney']['count']);
        $this->assertTrue(file_exists($data['wallet_airtelmoney']['file']));
    }

    public function testRefundExcelFileForAParticularMonth()
    {
        $knownDate = Carbon::create(2016, 5, 21);
        Carbon::setTestNow($knownDate);

        $defaultPayment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment = $this->doAuthAndCapturePayment($defaultPayment);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], [
                'created_at' => $createdAt,
                'updated_at' => $createdAt
            ]);
        }

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForAirtelmoneyWallet(true);

        $this->assertEquals(3, $data['wallet_airtelmoney']['count']);
        $this->assertTrue(file_exists($data['wallet_airtelmoney']['file']));

        Carbon::setTestNow();
    }

    protected function generateRefundsExcelForAirtelmoneyWallet($date = false)
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'method'    => 'wallet',
                'wallet'    => 'airtelmoney',
                'frequency' => 'monthly'
            ],
        );

        if ($date)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    protected function runPaymentCallbackFlowWalletAirtelmoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            // It's a redirect url. Airtelmoney use callback flow for payment authorization.
            $request = [
                'url' => $requestUrl,
            ];

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
