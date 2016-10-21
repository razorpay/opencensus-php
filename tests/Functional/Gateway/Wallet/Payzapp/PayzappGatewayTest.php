<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Payzapp;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class PayzappGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PayzappGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payzapp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableWallet('10000000000000', 'payzapp');

        $this->gateway = 'wallet_payzapp';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        // $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payzapp');

        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('wallet', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentPayzappEntity'], $payment);
    }

    public function testRefundPayment()
    {
        // $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payzapp');

        $postAuthPaymentInfo = $this->doAuthPayment($payment);

        $payment = $this->capturePayment($postAuthPaymentInfo['razorpay_payment_id'], $payment['amount']);

        $this->refundPayment($payment['id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterRefund'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('wallet', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentPayzappEntityAfterRefund'], $payment);
    }

    public function testPartialRefund()
    {
        $payment = $this->getDefaultWalletPaymentArray('payzapp');

        $response = $this->doAuthPayment($payment);

        $refundAmount = (int) ($payment['amount'] / 5);

        $payment = $this->capturePayment($response['razorpay_payment_id'], $payment['amount']);

        $this->mockServerContentFunction(function (&$content, $action) use ($refundAmount)
        {
            if ($action === 'refund')
            {
                $assertion = ($content['amount'] === $refundAmount);

                assertTrue($assertion, 'Actual refund amount different than expected amount');
            }
        });

        $this->refundPayment($payment['id'], $refundAmount);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('partial', $payment['refund_status']);
        $this->assertEquals($refundAmount, $payment['amount_refunded']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNotNull($wallet['refund_id']);
        $this->assertEquals($refundAmount, $wallet['amount']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payzapp');

        $postAuthPaymentInfo = $this->doAuthPayment($payment);

        $payment = $this->capturePayment($postAuthPaymentInfo['razorpay_payment_id'], $payment['amount']);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['verified'], 1);
    }

    protected function runPaymentCallbackFlowWalletPayzapp($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

//        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $content = $response->getContent();

            $ix = strpos($content, 'JSON.parse');
            $ix += strlen('JSON.parse(\'');

            $end = strpos($content, '"}\');', $ix);

            $url = getTextBetweenStrings($content, '***', '***');
            $method = 'post';
            $json = substr($content, $ix, $end - $ix + 2);
            $content = ['json' => $json];

            $this->ba->noAuth();
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                $url, $method, $content);
        }
        else
        {
            ;
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
