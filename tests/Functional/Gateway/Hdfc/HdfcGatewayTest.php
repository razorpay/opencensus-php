<?php

namespace RZP\Tests\Functional\Gateway\Hdfc;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Services\RazorXClient;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Hdfc\Payment\Result;

class HdfcGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const ACTION_AUTHORIZE = '4';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/HdfcGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'hdfc';

        $this->setMockGatewayTrue();

        $this->mockCardVault();

        $this->fixtures->merchant->enableInternational();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
             ->method('getTreatment')
             ->will($this->returnCallback(function ($mid, $feature, $mode)
                    {
                        if ($feature === 'secure_3d_international')
                        {
                            return 'v2';
                        }

                        if ($feature === 'recurring_card_not_enabled')
                        {
                            return 'control';
                        }

                        return 'v1';
                    }));

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
    }

    public function testPayment()
    {
        $order = $this->fixtures->create('order', [
            'amount' => '50000'
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = 'order_' . $order['id'];

        $receipt = $order['receipt'];

        $this->mockServerContentFunction(function (& $content, $action) use (& $receipt)
        {
            if ($action === 'enroll')
            {
                $this->assertEquals($receipt, $content['udf1']);
            }
            else if($action === 'authorize')
            {
                $this->assertEquals($receipt, $content['udf1']);
            }
        });

        $this->doAuthPayment($payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $hdfc = $this->getDbEntity('hdfc', ['action' => $this::ACTION_AUTHORIZE])->toArrayAdmin();

        $this->assertEquals('6', $hdfc['eci']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcPaymentEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // $this->assertNull($payment['verify_at']);
    }

    public function testPaymentForAuthorizationTerminal()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['capability' => 2]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = [
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $payment = $this->defaultAuthPayment($payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $hdfc = $this->getDbEntity('hdfc', ['action' => $this::ACTION_AUTHORIZE])->toArrayAdmin();

        $this->assertEquals('Y', $hdfc['enroll_result']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcPaymentEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // After capture verify_at is set to current_time()
        // $this->assertNull($payment['verify_at']);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNotNull($mpi);
        $this->assertEquals('mpi_blade', $mpi['gateway']);
        $this->assertEquals('Y', $mpi['enrolled']);
    }

    public function testPaymentForAuthorizationTerminalRupay()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['capability' => 2]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = [
            'card' => [
                'number'       => '6073849700004947',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->defaultAuthPayment($payment);
        });
    }

    public function testInternationalPaymentFailureForRisk()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['capability' => 2]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->mockShield();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'Visa',
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
             ->method('getTreatment')
             ->will($this->returnCallback(function ($mid, $feature, $mode)
                    {
                        if ($feature === 'shield_risk_evaluation')
                        {
                            return 'shield_on';
                        }

                        if ($feature === 'secure_3d_international')
                        {
                            return 'v2';
                        }

                        return 'shield_off';
                    }));

        $payment = [
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->defaultAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals('PAYMENT_FAILED_RISK_CHECK_IN_GATEWAY', $riskEntity['reason']);

        $paymentAnalytic = $this->getLastEntity('payment_analytics', true);

        $this->assertEquals('payment_analytics', $paymentAnalytic['entity']);

        $this->assertEquals('shield_v2', $paymentAnalytic['risk_engine']);
    }

    public function testPaymentForAuthorizationTerminalWithShieldRiskMock()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['capability' => 2]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->mockShield();

        $this->fixtures->iin->create([
            'iin'     => '426451',
            'country' => 'US',
            'network' => 'Visa',
        ]);

        $payment = [
            'card' => [
                'number'       => '4264511038488895',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $payment = $this->defaultAuthPayment($payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $hdfc = $this->getDbEntity('hdfc', ['action' => $this::ACTION_AUTHORIZE])->toArrayAdmin();

        $this->assertEquals('Y', $hdfc['enroll_result']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcPaymentEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // After capture verify_at is set to current_time()
        // $this->assertNull($payment['verify_at']);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNotNull($mpi);
        $this->assertEquals('mpi_blade', $mpi['gateway']);
        $this->assertEquals('Y', $mpi['enrolled']);
    }

    public function testPaymentForAuthorizationTerminalFailure()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['capability' => 2]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = [
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                unset(
                    $content['auth'], $content['ref'],
                    $content['avr'], $content['postdate'],
                    $content['paymentid'], $content['transid']);

                $content['result'] = '!ERROR!-GV10009-Invalid pre authentication status';
                $content['udf1'] = 'PA';
                $content['udf2'] = 'test@razorpay.com';
                $content['udf3'] = ' 919876543210';
                $content['udf4'] = 'test';
                $content['udf5'] = 'test';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->defaultAuthPayment($payment);
        });

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNotNull($mpi);
        $this->assertEquals('mpi_blade', $mpi['gateway']);
        $this->assertEquals('Y', $mpi['enrolled']);
    }

    public function testPaymentForAuthorizationTerminalFailureDifferentErrorCode()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['capability' => 2]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = [
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                unset(
                    $content['auth'], $content['ref'],
                    $content['avr'], $content['postdate'],
                    $content['paymentid'], $content['transid']);

                $content['result'] = 'NOT APPROVED';
                $content['udf1'] = 'PA';
                $content['udf2'] = 'test@razorpay.com';
                $content['udf3'] = ' 919876543210';
                $content['udf4'] = 'test';
                $content['udf5'] = 'test';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->defaultAuthPayment($payment);
        });

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNotNull($mpi);
        $this->assertEquals('mpi_blade', $mpi['gateway']);
        $this->assertEquals('Y', $mpi['enrolled']);
    }

    public function testTamperedPayment()
    {
        $payment = $this->doAuthPayment();

        $id = Payment::verifyIdAndSilentlyStripSign($payment['razorpay_payment_id']);

        $this->mockServerContentFunction(function (& $content, $action) use ($id)
        {
            if ($action === 'authorize')
            {
                $content['trackid'] = $id;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card'] = [
                'name' => 'card holder',
                'number' => '4012001037167778',
                'expiry_month' => 1,
                'expiry_year' => 2099,
                'cvv' => '123'
            ];

            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPayment()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);

        $token = $paymentEntity['token_id'];
        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($response['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        // $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);
        $this->assertEquals('auto', $paymentEntity['recurring_type']);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($paymentId);

        $hdfc = $this->getDbEntity('hdfc', ['action' => $this::ACTION_AUTHORIZE])->toArrayAdmin();

        $this->assertNotNull($hdfc['ref']);
        $this->assertNotNull($hdfc['auth']);
        $this->assertEquals($paymentId, $hdfc['payment_id']);
        $this->assertEquals('APPROVED', $hdfc['result']);
        $this->assertEquals('authorized', $hdfc['status']);

        $payment = $this->capturePayment($paymentEntity['id'], $paymentEntity['amount']);

        $hdfcCaptured = $this->getLastEntity('hdfc', true);

        $hdfcData = $this->testData['testHdfcPaymentEntity'];

        $this->assertArraySelectiveEquals($hdfcData, $hdfcCaptured);
    }

    public function testDebitRecurringPayment()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->fixtures->create('iin',
                                [
                                    'iin'       => '607466',
                                    'issuer'    => 'HDFC',
                                    'type'      => 'debit',
                                    'recurring' => 1,
                                ]);

        $payment['card']['number'] = '6074661038443336';

        $type = [
            'recurring_3ds'     => '1',
            'debit_recurring'   => '1',
        ];

        $this->fixtures->edit('terminal', 'FssRecurringTl', ['type' => $type]);

        $this->fixtures->merchant->addFeatures(['hdfc_debit_si']);

        $response = $this->doAuthAndCapturePayment($payment);
        $paymentId = $response['id'];

        $type = [
            'recurring_non_3ds' => '1',
            'debit_recurring'   => '1',
        ];

        $this->fixtures->edit('terminal', 'FssRecurringTl', ['type' => $type]);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);

        $token = $paymentEntity['token_id'];
        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($response['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($paymentId);

        $hdfc = $this->getDbEntity('hdfc', ['action' => $this::ACTION_AUTHORIZE])->toArrayAdmin();

        $this->assertNotNull($hdfc['ref']);
        $this->assertNotNull($hdfc['auth']);
        $this->assertEquals($paymentId, $hdfc['payment_id']);
        $this->assertEquals('APPROVED', $hdfc['result']);
        $this->assertEquals('authorized', $hdfc['status']);

        $this->capturePayment($paymentEntity['id'], $paymentEntity['amount']);

        $hdfcCaptured = $this->getLastEntity('hdfc', true);
        $this->assertEquals('auto', $paymentEntity['recurring_type']);

        $hdfcData = $this->testData['testHdfcPaymentEntity'];

        $this->assertArraySelectiveEquals($hdfcData, $hdfcCaptured);
    }

    public function testDebitRecurringPaymentVerify()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->fixtures->create('iin',
            [
                'iin'       => '607466',
                'issuer'    => 'HDFC',
                'type'      => 'debit',
                'recurring' => 1,
            ]);

        $payment['card']['number'] = '6074661038443336';

        $type = [
            'recurring_3ds'     => '1',
            'debit_recurring'   => '1',
        ];

        $this->fixtures->edit('terminal', 'FssRecurringTl', ['type' => $type]);

        $this->fixtures->merchant->addFeatures(['hdfc_debit_si']);

        $response = $this->doAuthAndCapturePayment($payment);
        $paymentId = $response['id'];

        $this->verifyPayment($paymentId);

        $type = [
            'recurring_non_3ds' => '1',
            'debit_recurring'   => '1',
        ];

        $this->fixtures->edit('terminal', 'FssRecurringTl', ['type' => $type]);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
    }

    public function testDebitRecurringRefund()
    {
        $this->testDebitRecurringPayment();

        $payment = $this->getLastPayment(true);

        $this->mockServerRequestFunction(function (&$content, $action = null) use ($payment)
        {
            if ($action === 'refund')
            {
                $this->assertEquals($payment['id'], $content['transid']);
                $this->assertEquals($payment['id'], $content['trackid']);
                $this->assertEquals('TrackId',  $content['udf5']);
            }
        });

        $this->refundPayment($payment['id'], $payment['amount']);
    }

    public function testFailureInRecurringPayment()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        $payment[Payment::TOKEN] = $paymentEntity->toArrayAdmin()[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);

        $this->ba->privateAuth();

        $this->hdfcPaymentMockResultCode('NOT APPROVED', 'authorize');

        $response = $this->doS2SRecurringPayment($payment);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($response['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();
        $this->startTest();

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());

        $hdfc = $this->getDbEntities('hdfc');

        $this->assertCount(3, $hdfc);
        $this->assertSame('auth_recurring_failed', $hdfc[2]->getStatus());
    }

    public function testInternationalUSDPaymentOnApi()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $input = [
            'amount'   => 5000,
            'currency' => 'USD'
        ];

        $payment = $this->defaultAuthPayment($input);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount'], 'USD');

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcUSDPaymentEntity'], $gatewayPayment);

        $this->refundPayment($payment['id'], $payment['amount'] / 2);
    }

    public function testMaestroCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);
        $this->assertNotNull($payment['transaction_id']);

        $this->assertEquals(TwoFactorAuth::PASSED, $payment['two_factor_auth']);
    }

    public function testDebitPinAuthPayment()
    {
        // after addition of shared terminal filter in filtering there is no terminal in applicable terminals list
        // hence adding direct terminal so that it does not gets filtered out and payment flow can be tested
        $directHdfcTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000',
            'gateway_acquirer' => 'hdfc',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id' => 'SharedHdfcTrml',
            'gateway_acquirer' => 'hdfc',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'pin';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->verifyPayment($payment['id']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id']);
    }

    public function testDebitPinAuthorizeFailed()
    {
        // after addition of shared terminal filter in filtering there is no terminal in applicable terminals list
        // hence adding direct terminal so that it does not gets filtered out and payment flow can be tested
        $directHdfcTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000',
            'gateway_acquirer' => 'hdfc',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id' => 'SharedHdfcTrml',
            'gateway_acquirer' => 'hdfc',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'pin';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'debit_pin_authorization_response')
            {
                $content['result'] = 'NOT CAPTURED';
                $content['authRespCode'] = '';
                $content['ErrorText'] = 'Purchase/Capture/Refund not done. Response result code is "NOT CAPTURED"';
                $content['ErrorNo'] = 'RP00007';
                $content['error_service_tag'] = 'Purchase/Capture/Refund not done. Response result code is "NOT CAPTURED"';
                $content['error_code_tag'] = 'RP00007';
            }
        });

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testDebitPinVerifyFailed()
    {
        // after addition of shared terminal filter in filtering there is no terminal in applicable terminals list
        // hence adding direct terminal so that it does not gets filtered out and payment flow can be tested
        $directHdfcTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000',
            'gateway_acquirer' => 'hdfc',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id' => 'SharedHdfcTrml',
            'gateway_acquirer' => 'hdfc',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'pin';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'verify')
            {
                $content['result'] = 'NOT APPROVED';
                $content['authRespCode'] = '';
                $content['ErrorText'] = '';
                $content['ErrorNo'] = '';
                $content['error_service_tag'] = '';
                $content['error_code_tag'] = '';
            }
        });

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testTwoFaNotApplicable()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012001037411127';

        $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(TwoFactorAuth::NOT_APPLICABLE, $payment['two_factor_auth']);
    }

    public function testRupayCard()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals('passed', $payment['two_factor_auth']);
        $this->assertEquals('999999', $payment['reference2']);

        $this->verifyPayment($payment['id']);
        $this->capturePayment($payment['id'], $payment['amount']);
        $this->refundPayment($payment['id']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['amt'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testHdfcEntityAfterPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('hdfc', true);
        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->defaultAuthPayment();

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['id'], $input);

        $hdfcEntity = $this->getDbEntity('hdfc', ['action' => $this::ACTION_AUTHORIZE])->toArrayAdmin();

        $this->assertEquals('authorized', $hdfcEntity['status']);
        $this->assertEquals('APPROVED', $hdfcEntity['result']);

        $txn = $this->getLastTransaction(true);
        $this->assertNull($txn);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment();

        $this->verifyPayment($payment['razorpay_payment_id']);
    }

    public function testPaymentAuthorizedTimeoutPayment()
    {
        $payment = $this->doAuthPayment();

        $this->fixtures->payment->edit($payment['razorpay_payment_id'],
            [
                'status' => 'failed',
                'error_code' => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description' => 'Payment was not completed on time.',
                'verify_bucket' => 0,
                'verified' => null
            ]);

        $data = $this->authorizedFailedPayment($payment['razorpay_payment_id']);

        $this->assertEquals($data['status'], 'authorized');
    }

    public function testPaymentVerifyAndTransactionNotFoundInResponse()
    {
        $testData = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    'error_code_tag' => 'GW00201',
                    'error_service_tag' => 'null',
                    'result' => '!ERROR!-GW00201-Transaction not found.',
                ];
            }

            return $content;
        });

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->verifyPayment($payment['razorpay_payment_id']);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['verified']);
    }

    public function testLongErrorCode()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = [
                    'error_code_tag'    => 'IPAY0200121',
                    'result'            => '!ERROR!-IPAY0200121-FSSConnect Destination is down',
                    'error_service_tag' => 'null',
                ];
            }

            return $content;
        });

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment();
        });
    }

    public function testVerifyRefundFailedOnGatewayRetry()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->hdfcPaymentFailedDueToDeniedByRisk();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null) use ($refund)
        {
            if ($action === 'verify')
            {
                $refundId = explode('_', $refund['id'], 2)[1];

                $content['result']       = 'FAILURE(SUSPECT)';
                $content['trackid']      = $refundId;
                $content['amt']          = $refund['amount'] / 100;
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount'=>$refund['amount']]);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals(1, $refund['attempts']);
        $this->assertEquals('processed', $refund['status']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->hdfcPaymentFailedDueToDeniedByRisk();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    'error_code_tag' => 'GW00201',
                    'error_service_tag' => 'null',
                    'result' => '!ERROR!-GW00201-Transaction not found.',
                ];
            }

            return $content;
        });

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals(1, $refund['attempts']);
        $this->assertEquals('processed', $refund['status']);
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            throw new Exception\GatewayTimeoutException('Timed out');
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null) use ($refund)
        {
            if ($action === 'verify')
            {
                $refundId = explode('_', $refund['id'], 2)[1];

                $content['result']   = 'FAILURE(SUSPECT)';
                $content['auth']     = '123456';
                $content['ref']      = '725070182254';
                $content['postdate'] = '0000';
                $content['tranid']   = '6996066201872501';
                $content['trackid']  = $refundId;
                $content['amt']      = $refund['amount'] / 100;
                $content['payid']    = '8152480571771510';
                $content['udf2']     = '';
                $content['udf5']     = 'TrackID';
                $content['authRespCode'] = 'J';
            }

            return $content;
        });

        $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount'=>$refund['amount']]);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals(1, $refund['attempts']);
        $this->assertEquals('processed', $refund['status']);
    }

    public function testRupayPaymentAuthError()
    {
        $testData = [
            'response' => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'RZP\Exception\GatewayErrorException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
            ],
        ];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '6073840000000008';

            $this->doAuthPayment($payment);
        });
    }

    public function testAuthorizeFailedPayment()
    {
        $this->timeoutHdfcAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('failed', $payment['status']);

        $this->succeedPaymentVerify();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getLastEntity('hdfc', true);
    }

    public function testRefundDeniedByRisk()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']   = 'FAILURE(SUSPECT)';
                $content['auth']     = '123456';
                $content['ref']      = '725070182254';
                $content['postdate'] = '0000';
                $content['tranid']   = '6996066201872501';
                $content['payid']    = '8152480571771510';
                $content['udf2']     = '';
                $content['udf5']     = 'TrackID';
                $content['authRespCode'] = 'J';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->refundPayment($payment['id']);

        $hdfc = $this->getLastEntity('hdfc', true);
        $this->assertTestResponse($hdfc);

        $payment = $this->getLastPayment();
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testPaymentFailWithFailureResultCode()
    {
        $this->hdfcPaymentMockResultCode('FAILURE(DENIED BY RISK)', 'authorize');

        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->doAuthPayment();
            });

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals($hdfc['result'], 'DENIED BY RISK');
    }

    public function testPaymentFailWithFailureLongResultCode()
    {
        $this->hdfcPaymentMockResultCode(Result::DENIED_CAPTURE, 'authorize');

        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->doAuthAndCapturePayment();
            });

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals($hdfc['error_code2'], 'RP00021');
    }

    protected function timeoutHdfcAuthorizePayment()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                throw new Exception\GatewayTimeoutException('Timed out');
            }

            return $content;
        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $content = $this->doAuthPayment();
            });
    }

    protected function succeedPaymentVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['RESPCODE'] = '0';
            $content['RESPMSG'] = 'Transaction succeeded';
            $content['STATUS'] = 'TXN_SUCCESS';

            return $content;
        });
    }

    protected function authErrorOnRupayPayment()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['amt'] = '1.0';
            $content['result'] = 'AUTH ERROR';
            unset($content['PAReq'], $content['eci']);
            return $content;
        });
    }
}
