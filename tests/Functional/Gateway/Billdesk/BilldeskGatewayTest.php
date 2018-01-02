<?php

namespace RZP\Tests\Functional\Gateway\Billdesk;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Billdesk\Gateway;

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

    public function testPaymentAndNewPaymentOnDeleteTerminal()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $terminal = $this->getLastEntity('terminal', true);

        $t = $this->deleteTerminal2($terminal['id']);

        $this->assertNotNull($t['deleted_at']);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $data = $this->testData['testPaymentAndNewPaymentOnDeleteTerminal'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentAndVerifyOnDeleteTerminal()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $terminal = $this->getLastEntity('terminal', true);

        $t = $this->deleteTerminal2($terminal['id']);

        $this->assertNotNull($t['deleted_at']);

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);
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

    public function testMakerCheckerPaymentNormalCallbackForFailed()
    {
        $this->fixtures->create('terminal:billdesk_terminal', [
            'corporate' => 1
        ]);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $data = $this->testData['testMakerCheckerPaymentNormalCallbackForFailed'];

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC_C');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
           $this->doAuthPayment($payment);
        });

        $billdesk = $this->getLastEntity('billdesk', true);
        $payment = $this->getLastEntity('payment', true);

        // Choose ICICI corporate bank
        $this->assertEquals($billdesk['BankID'], 'ICO');
        $this->assertEquals($payment['status'], 'failed');

        $time = Carbon::now()->getTimestamp();

        // Change created_at to allow payments to be picked up,
        // Allow verify to pick up payment
        $this->fixtures->edit('payment', $payment['id'], ['created_at' => $time - 150]);

        $this->runVerify('payments_failed');

        $billdesk = $this->getLastEntity('billdesk', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals($payment['status'], 'captured');
    }

    /**
     * Asynchronous payment with corporate banking, the initial response
     * provides a pending status which can be used to wait leave the
     * payment in the created state.
     *
     * Assuming the authorization takes place in the meanwhile.
     * The payment will be successful with verify post the authorization.
     * */
    public function testMakerCheckerPaymentNormalCallback()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:billdesk_terminal', [
            'corporate' => 1
        ]);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');
        $payment = $this->doAuthPayment($payment);

        $billdesk = $this->getLastEntity('billdesk', true);
        $payment = $this->getLastEntity('payment', true);

        // Choose ICICI corporate bank
        $this->assertEquals($billdesk['BankID'], 'ICO');
        $this->assertEquals($payment['status'], 'created');

        $time = Carbon::now()->getTimestamp();

        // Change created_at to allow payments to be picked up,
        // Allow verify to pick up payment
        $this->fixtures->edit('payment', $payment['id'], ['created_at' => $time - 150]);

        $this->runVerify();

        $billdesk = $this->getLastEntity('billdesk', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals($payment['status'], 'captured');
    }

    public function testMakerCheckerPaymentS2SCallback()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:billdesk_terminal', [
            'corporate' => 1
        ]);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        // Setup mock server
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

        // Set mock server
        $this->setMockServer($server);

        $data = $this->testData['testServerToServerCallback'];

        $this->runRequestResponseFlow($data, function()
        {
            $payment = $this->getDefaultNetbankingPaymentArray('ICIC');
            $payment = $this->doAuthPayment($payment);
        });

        $billdesk = $this->getLastEntity('billdesk', true);
        $payment = $this->getLastEntity('payment', true);

        // // Choose ICICI corporate bank
        $this->assertEquals($billdesk['BankID'], 'ICO');
        $this->assertEquals($payment['status'], 'created');

        $time = Carbon::now()->getTimestamp();

        // Change created_at to allow payments to be picked up,
        // Allow verify to pick up payment
        $this->fixtures->edit('payment', $payment['id'], ['created_at' => $time - 150]);

        $this->runVerify();

        $billdesk = $this->getLastEntity('billdesk', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals($payment['status'], 'captured');
    }

    protected function runVerify($filter = 'payments_created')
    {
        $request = [
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);
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

    public function testPaymentVerifyError()
    {
        $data = $this->testData['testPaymentVerifyError'];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ANDB';

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'bank_preprocess')
            {
                $content['TxnAmount'] = '1';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ANDB';

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
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

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
    }

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->addFeatures('corporate_banks');

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

        $this->assertEquals(61, $count);
    }

    public function testServerToServerCallback()
    {
        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing(function (& $content, $action = null)
                        {
                            if ($action === 'bank')
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
                            }
                        })->mock();

        $this->setMockServer($server);

        $data = $this->testData['testServerToServerCallback'];

        $this->runRequestResponseFlow($data, function()
        {
            $payment = $this->getDefaultNetbankingPaymentArray();
            $payment = $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 40000);

        $refund = $this->getLastEntity('billdesk', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentMultiplePartialRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 40000);

        $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('billdesk', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentMultipleInvalidPartialRefund()
    {
        $data = $this->testData['testPaymentMultipleInvalidPartialRefund'];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 40000);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id'], 40000);
        });
    }

    public function testReconcileCancelledTransactions()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $paymentTransaction = $this->getLastTransaction(true);

        $this->refundPayment($payment['id'], $payment['amount']);

        $billdeskRefund = $this->getLastEntity('billdesk', true);

        $this->fixtures->edit('billdesk', $billdeskRefund['id'], ['refStatus' => '0699']);

        $this->startTest();

        $paymentTransaction = $this->getEntityById('transaction', $paymentTransaction['id'], true);

        $this->assertNotNull($paymentTransaction['reconciled_at']);
        $this->assertEquals(0, $paymentTransaction['gateway_service_tax']);
        $this->assertEquals(0, $paymentTransaction['gateway_fee']);
    }

    public function testPaymentFailureBeforeRedirection()
    {
        $this->mockServerRequestFunction(function(& $request)
        {
            $messages = explode('|', $request['content']['msg']);
            $callbackUrl = $messages[21];
            $request['url'] = $callbackUrl;

            $override = [
                'AuthStatus'        => '0399',
                'ErrorStatus'       => 'NA',
                'ErrorDescription'  => 'TRANSACTION TERMINATED BY USER'
            ];
            $msg = $this->getCallbackErrorMsg($override);

            $request['content']['msg'] = $msg;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $payment = $this->getDefaultNetbankingPaymentArray();
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testServerToServerFailureCallback()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['AuthStatus']        = '0399';
            $content['ErrorStatus']       = 'NA';
            $content['ErrorDescription']  = 'Insufficient-funds';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $payment = $this->getDefaultNetbankingPaymentArray();
            $payment = $this->doAuthPayment($payment);
        });
    }

    /**
     *
     * @param array $override
     * @param null $msg With explode msg and override it
     * @return string Updated Msg
     */
    private function getCallbackErrorMsg(array $override, $msg = null)
    {
        $gateway = new Gateway();

        $keys = $gateway->getFieldsForAction('callback');

        if ($msg)
        {
            $values = explode('|', $msg);
        }
        else
        {
            $values = array_flip($keys);
        }

        $content = array_combine($keys, $values);

        $overridden = array_merge($content, $override);

        unset($overridden['Checksum']);

        $msg = $gateway->getMessageStringWithHash($overridden);

        return $msg;
    }
}
