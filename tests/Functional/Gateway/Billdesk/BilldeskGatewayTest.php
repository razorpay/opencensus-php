<?php

namespace RZP\Tests\Functional\Gateway\Billdesk;

use DB;
use Mockery;
use Carbon\Carbon;

use Razorpay\IFSC\Bank;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Billdesk\Gateway;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class BilldeskGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/BilldeskGatewayTestData.php';

        parent::setUp();

        DB::table('terminals')->delete();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->gateway = 'billdesk';

        $this->setMockGatewayTrue();

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
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
        $this->assertRequestSecurityId('random');

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
    }

    public function testPaymentWithMerchantProcuredTerminal()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['description'] = '1234';

        $this->fixtures->edit('terminal', $this->sharedTerminal['id'], ['procurer' => 'merchant']);

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

        $this->fixtures->edit('terminal', $this->sharedTerminal['id'], ['procurer' => 'razorpay']);
    }

    // Test to check if gateway access code and secret is picked from terminal
    // instead of config
    public function testPaymentWithCredsFromTerminal()
    {
        $addtionalTerminalData = [
            'id'                    => '100BdeskTrmnl2',
            'gateway_access_code'   => 'access_code',
        ];

        $terminalWithAdditionalData = $this->fixtures
                                           ->on('live')
                                           ->create('terminal:shared_billdesk_terminal', $addtionalTerminalData);

        $this->assertRequestSecurityId('access_code');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->doAuthPayment($payment, null, 'rzp_live_TheLiveAuthKey');

        $this->fixtures->terminal->disableTerminal($terminalWithAdditionalData['id']);
    }

    public function testMakerCheckerPaymentNormalCallbackForFailed()
    {
        // Skipping this test for now, removing ICICI from billdesk
        $this->markTestSkipped();

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
        $this->ba->cronAuth();

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
        $this->markTestSkipped();

        $data = $this->testData['testPaymentVerifyError'];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = Bank::UBIN;

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAmountTampering()
    {
        $this->markTestSkipped();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'bank_preprocess')
            {
                $content['TxnAmount'] = '1';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = Bank::UBIN;

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentRefund()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('billdesk', true);

        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $this->markTestSkipped();

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

        $this->assertEquals(88, $count);
    }

    public function testServerToServerCallback()
    {
        $this->markTestSkipped();

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
        $this->markTestSkipped();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 40000);

        $refund = $this->getLastEntity('billdesk', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentMultiplePartialRefund()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 40000);

        $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('billdesk', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentMultipleInvalidPartialRefund()
    {
        $this->markTestSkipped();

        $data = $this->testData['testPaymentMultipleInvalidPartialRefund'];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 40000);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id'], 40000);
        });
    }

    public function testGatewayRefundVerify()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->fixtures->edit('refund', $refund['id'], ['status' => 'created']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(1, $refund['attempts']);
        $this->assertEquals('created', $refund['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);

        Carbon::setTestNow($time);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);
    }

    public function testReconcileCancelledTransactions()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $paymentTransaction = $this->getLastTransaction(true);

        $this->refundPayment($payment['id'], $payment['amount']);

        $billdeskRefund = $this->getLastEntity('billdesk', true);

        $this->fixtures->edit('billdesk', $billdeskRefund['id'], ['refStatus' => '0699']);

        $this->ba->cronAuth();
        $this->startTest();

        $paymentTransaction = $this->getEntityById('transaction', $paymentTransaction['id'], true);

        $this->assertNotNull($paymentTransaction['reconciled_at']);
        $this->assertNotNull($paymentTransaction['reconciled_type']);
        $this->assertEquals(0, $paymentTransaction['gateway_service_tax']);
        $this->assertEquals(0, $paymentTransaction['gateway_fee']);
    }

    public function testPaymentFailureBeforeRedirection()
    {
        $this->markTestSkipped();

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
        $this->markTestSkipped();

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

    protected function assertRequestSecurityId($id)
    {
        $this->mockServerRequestFunction(function (&$content, $action = null) use ($id)
        {
            $requestFields = [
                'MerchantID',
                'CustomerID',
                'AccountNumber',
                'TxnAmount',
                'BankID',
                'Unknown2',
                'Unknown3',
                'CurrencyType',
                'ItemCode',
                'TypeField1',
                'SecurityID',
                'Unknown4',
                'Unknown5',
                'TypeField2',
                'AdditionalInfo1',
                'Unknown6',
                'Unknown7',
                'Unknown8',
                'Unknown9',
                'Unknown10',
                'Unknown11',
                'RU',
                'Checksum'];

            $requestContent = explode('|', $content['content']['msg']);

            $input = array_combine($requestFields, $requestContent);

            $this->assertEquals($id, $input['SecurityID']);
        });
    }
}
