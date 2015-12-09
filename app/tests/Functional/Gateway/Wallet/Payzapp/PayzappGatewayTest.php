<?php

namespace Tests\Functional\Gateway\Wallet\Payzapp;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class PayzappGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PayzappGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payzapp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'wallet_payzapp';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
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

        $payment = $this->getLastEntity('billdesk', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentBilldeskEntity'], $payment);
    }

    public function testPaymentFailed()
    {
        $this->markTestIncomplete();

        $this->failPaymentOnBankPage = true;
    }

    public function testPaymentVerify()
    {
        $this->markTestIncomplete();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);
    }

    public function testPaymentRefund()
    {
        $this->markTestIncomplete();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('billdesk', true);
        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $this->markTestIncomplete();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('billdesk', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentRefund'], $refund);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterRefundingAuthorizedPayment'], $txn);
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
