<?php

namespace RZP\Tests\Functional\Gateway\Billdesk;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\TwoFaStatus;

class BilldeskGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BilldeskGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'billdesk';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
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

    public function testPaymentOnDirectBilldeskTerminal()
    {
        $terminal = $this->fixtures->create('terminal:billdesk_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals($terminal['id'], $payment['terminal_id']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('billdesk', true);

        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
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

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'billdesk',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay billdesk',
            'gateway_terminal_id'       => 'nodal account billdesk',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $terminal = $this->fixtures->on('live')->create('terminal', $attributes);

        $content = $this->startTest();

        $count = count($content['netbanking']);
        $this->assertEquals(59, $count);
    }

    public function testServerToServerCallback()
    {
        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing(function (& $content)
                        {
                            $request = array(
                                'content' => $content,
                                'url' => '/callback/billdesk',
                                'method' => 'post');

                            // Fire s2s callback request
                            $response = $this->makeRequestAndGetContent($request);

                            $this->assertEquals($response['success'], true);

                            // Stop the progress here.
                            throw new Exception\RuntimeException(
                                'Stop here.');

                        })->mock();

        $this->setMockServer($server);

        try
        {
            $payment = $this->getDefaultNetbankingPaymentArray();
            $payment = $this->doAuthPayment($payment);
        }
        catch (Exception\RuntimeException $e)
        {
            ;
        }

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');
    }
}
