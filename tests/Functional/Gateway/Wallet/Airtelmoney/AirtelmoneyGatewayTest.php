<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Airtelmoney;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Gateway\Wallet\Airtelmoney\Status;
use RZP\Gateway\Wallet\Airtelmoney\Mock\TestAmount;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class AirtelmoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    protected function setUp(): void
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

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if($action === 'callback')
            {
                $content['TRAN_AMT'] = '1.00';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
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

        $this->mockSetVerifyTransactionId();

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $authPayment = $this->doAuthPayment($payment);

        $data1 = [
            'status'             => 'failed',
            'authorized_at'      =>  null,
        ];

        $this->fixtures->base->editEntity('payment', $authPayment['razorpay_payment_id'], $data1);

        $this->mockSetVerifyTransactionId();

        $id = $authPayment['razorpay_payment_id'];

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testFailedVerify()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = ((float) TestAmount::FAIL_VERIFY_AMOUNT) * 100;

        $authPayment = $this->doAuthPayment($payment);

        $this->fixtures->base->editEntity('payment', $authPayment['razorpay_payment_id'], ['status' => 'failed']);

        $this->mockSetVerifyTransactionId();

        $id = $authPayment['razorpay_payment_id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });
    }

    public function testVerifyLateAuthorizedPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create(
            'payment',
            [
                'email'           => 'a@b.com',
                'amount'          => 50000,
                'contact'         => '9918899029',
                'status'          => 'authorized',
                'method'          => 'wallet',
                'wallet'          => 'airtelmoney',
                'gateway'         => 'wallet_airtelmoney',
                'card_id'         => null,
                'terminal_id'     => $this->sharedTerminal->id,
                'late_authorized' => 1,
            ]);

        $wallet = $this->fixtures->create('wallet', [
            'payment_id'         => $payment->getId(),
            'amount'             => $payment->getAmount(),
            'wallet'             => 'airtelmoney',
            'action'             => 'authorize',
            'gateway_payment_id' => null,
            'reference1'         => null,
        ]);

        $id = $payment->getPublicId();

        $this->verifyPayment($id);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNotNull($wallet['gateway_payment_id']);
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
        $this->refundPayment($capturePayment['id'], $payment['amount'] / 2);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = TestAmount::FAIL_REFUND_AMOUNT * 100;

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $capturePaymentId = $capturePayment['id'];

        $this->refundPayment($capturePaymentId);

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
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
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
        $knownDate = Carbon::create(2016, 5, 21, null, null, null);

        Carbon::setTestNow($knownDate);

        $defaultPayment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment = $this->doAuthAndCapturePayment($defaultPayment);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id']);

        $createdAt = Carbon::today(Timezone::IST)->addMonth(1)->timestamp + 5;

        $this->fixtures->edit('refund', $refund['id'], [
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);

        $data = $this->generateRefundsExcelForAirtelmoneyWallet(true);

        $this->assertEquals(3, $data['wallet_airtelmoney']['count']);
        $this->assertTrue(file_exists($data['wallet_airtelmoney']['file']));

        Carbon::setTestNow();
    }

    protected function generateRefundsExcelForAirtelmoneyWallet($date = false)
    {
        $this->ba->adminAuth();

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

    protected function mockSetVerifyTransactionId()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $gatewayPayment = $this->getLastEntity('wallet', true);

            if (isset($content['txns'][0]['txnid']) === true)
            {
                $content['txns'][0]['txnid'] = $gatewayPayment['gateway_payment_id'];
            }
        });
    }

    public function testUndefinedHashFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = ((float) TestAmount::FAIL_PAYMENT_AMOUNT) * 100;

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if($action === 'hash')
            {
                $content['STATUS'] = 'FAL';
                $content['CODE']   = '900';
                $content['HASH']   = 'undefined';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testEmptyHashFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = ((float) TestAmount::FAIL_PAYMENT_AMOUNT) * 100;

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if($action === 'hash')
            {
                $content['STATUS'] = 'FAL';
                $content['CODE']   = '900';
                $content['HASH']   = '';
            }
        });

        $data = $this->testData['testUndefinedHashFailedPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testUndefinedHashSuccessPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if($action === 'hash')
            {
                $content['HASH'] = 'undefined';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
