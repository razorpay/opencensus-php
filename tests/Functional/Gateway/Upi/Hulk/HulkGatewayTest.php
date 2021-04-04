<?php

namespace RZP\Tests\Functional\Gateway\Upi\Hulk;

use Cache;
use Mail;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HulkGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__.'/HulkGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->gateway = 'upi_hulk';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        $gatewayHit = false;

        $this->mockServerRequestFunction(
            function($content, $action) use (& $gatewayHit)
            {
                $gatewayHit = true;
                $this->assertSame('authorize', $action);
                // Preset category for test merchant
                $this->assertSame('1100', $content['category_code']);
            });

        unset($this->payment['description']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Checking whether gateway was hit
        $this->assertTrue($gatewayHit);

        $upiPayment = $this->getDbLastEntity('upi');

        $this->assertSame('collect', $upiPayment['type']);

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentCallback()
    {
        $this->mockServerRequestFunction(
            function($content, $action)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('pull', $content['type']);

                    $this->assertArrayNotHasKey('receiver_id', $content);
                }
            });

        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);
        $this->assertSame('vishnu@icici', $upi->vpa);

        $callback = $this->mockServer()->getAsyncCallbackRequest($upi, $payment);

        $response = $this->makeRequestAndGetContent($callback);
        $this->assertEquals(['success' => true], $response);

        $payment->reload();
        $upi->reload();

        $this->assertTrue($payment->isAuthorized());
        $this->assertSame('completed', $upi['status_code']);
        $this->assertSame('00100100100', $upi['account_number']);
        $this->assertSame('RZP10010011', $upi['ifsc']);
    }

    public function testPaymentFailedCallbackMappedError()
    {
        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);

        $override = [
            'status'                => 'failed',
            'error_code'            => 'BAD_REQUEST_ERROR',
            'error_description'     => 'Invalid vpa for request',
            'internal_error_code'   => 'BAD_REQUEST_INVALID_VPA',
        ];

        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($override)
            {
                if ($action === 'callback')
                {
                    $content['type'] = 'p2p_failed';
                    $content['data'] = array_merge($content['data'], $override);
                }
            });

        $callback = $this->getMockServer()->getAsyncCallbackRequest($upi, $payment);

        $this->makeRequestAndCatchException(
            function() use ($callback)
            {
                 $this->sendRequest($callback);
            },
            GatewayErrorException::class);

        $payment->reload();

        $this->assertSame('BAD_REQUEST_ERROR', $payment->getErrorCode());
        $this->assertSame('BAD_REQUEST_PAYMENT_UPI_INVALID_VPA', $payment->getInternalErrorCode());
        $this->assertSame('Invalid VPA. Please enter a valid Virtual Payment Address',
                          $payment->getErrorDescription());

    }

    public function testPaymentFailedCallbackDefinedError()
    {
        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);

        $override = [
            'status'                => 'failed',
            'error_code'            => 'GATEWAY_ERROR',
            'error_description'     => 'Payment failed because of risk score',
            'internal_error_code'   => 'GATEWAY_ERROR_DENIED_BY_RISK',
        ];

        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($override)
            {
                if ($action === 'callback')
                {
                    $content['type'] = 'p2p_failed';
                    $content['data'] = array_merge($content['data'], $override);
                }
            });

        $callback = $this->getMockServer()->getAsyncCallbackRequest($upi, $payment);

        $this->makeRequestAndCatchException(
            function() use ($callback)
            {
                $this->sendRequest($callback);
            },
            GatewayErrorException::class,
            "Payment processing failed due to error at bank or wallet gateway\n".
            "Gateway Error Code: GATEWAY_ERROR_DENIED_BY_RISK\n".
            "Gateway Error Desc: Payment failed because of risk score");

        $payment->reload();
        $upi->reload();

        $this->assertSame('GATEWAY_ERROR', $payment->getErrorCode());
        $this->assertSame('GATEWAY_ERROR_DENIED_BY_RISK', $payment->getInternalErrorCode());
        $this->assertSame('Payment processing failed due to error at bank or wallet gateway',
                          $payment->getErrorDescription());

        $this->assertSame('failed', $upi['status_code']);
    }

    public function testPaymentFailedCallbackInvalidError()
    {
        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);

        $override = [
            'status'                => 'failed',
            'error_code'            => 'GATEWAY_ERROR',
            'error_description'     => 'Payment failed because of invalid error',
            'internal_error_code'   => 'TOTALLY_INVALID_ERROR_CODE',
        ];

        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($override)
            {
                if ($action === 'callback')
                {
                    $content['type'] = 'p2p_failed';
                    $content['data'] = array_merge($content['data'], $override);
                }
            });

        $callback = $this->getMockServer()->getAsyncCallbackRequest($upi, $payment);

        $this->makeRequestAndCatchException(
            function() use ($callback)
            {
                $this->sendRequest($callback);
            },
            GatewayErrorException::class,
            "Payment processing failed due to error at bank or wallet gateway\n".
            "Gateway Error Code: TOTALLY_INVALID_ERROR_CODE\n".
            "Gateway Error Desc: Payment failed because of invalid error");

        $payment->reload();
        $upi->reload();

        $this->assertSame('GATEWAY_ERROR', $payment->getErrorCode());
        $this->assertSame('GATEWAY_ERROR_FATAL_ERROR', $payment->getInternalErrorCode());
        $this->assertSame('Payment processing failed due to error at bank or wallet gateway',
                          $payment->getErrorDescription());

        $this->assertSame('failed', $upi['status_code']);
    }

    public function testPaymentCallbackFailed()
    {
        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);

        $callback = $this->mockServer()->getAsyncCallbackRequest($upi, $payment);

        $callback['server']['HTTP_X-Hulk-Signature'] .= 'wow';

        $this->makeRequestAndCatchException(
            function() use ($callback)
            {
                $this->sendRequest($callback);
            },
            GatewayErrorException::class,
            "Payment processing failed due to error at bank or wallet gateway\n".
            "Gateway Error Code: \n".
            "Gateway Error Desc: ");

        $payment->reload();

        $this->assertSame('GATEWAY_ERROR_CHECKSUM_MATCH_FAILED', $payment->getInternalErrorCode());
    }

    public function testPaymentWithExpiryPublicAuth()
    {
        $payment = $this->payment;

        unset($payment['description']);

        $payment['upi']['expiry_time'] = 10;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testPaymentWithExpiryPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['upi']['expiry_time'] = 10;

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals(10, $upiEntity['expiry_time']);
    }

    public function testPaymentViaRedirection()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        // Payment status is a polling API which checkout hits
        // continously. Replicating the same in test case
        $this->checkPaymentStatus($paymentId, 'created');
        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testPaymentS2S()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testRefund()
    {
        $payment = $this->fixtures->create('payment:upi_captured',
            [
                'gateway'       => 'upi_hulk',
                'terminal_id' => $this->sharedTerminal->getId(),
            ]);

        $this->fixtures->create('upi',
            [
                'action'            => 'authorize',
                'payment_id'        => $payment->getId(),
                'npci_reference_id' => '123123123123',

            ]);

        $this->refundPayment($payment->getPublicId(), 5000);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $payment = $this->authorizedFailedPayment($payment['id']);

        $this->assertNull($payment['verified']);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal');

        $merchant = $this->getDbLastEntity('merchant', 'test');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->mockServerRequestFunction(
            function($content, $action) use ($merchant)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('expected_push', $content['type']);
                    $this->assertSame((string) $merchant['category'], $content['category_code']);
                    $this->assertArrayNotHasKey('receiver_id', $content);
                }
            });

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $expectedUrl = 'upi://pay?pa=testmerchant@razor&pn=TestMerchant&'.
                       'tr=A11zpSL1413XHi&tn=TestMerchant&am=500&cu=INR&mc=1100';

        $this->assertSame($expectedUrl, $response['data']['intent_url']);

        $upiEntity = $this->getDbLastEntity('upi');
        $payment = $this->getDbLastPayment('payment');

        $this->assertSame('created', $payment->getStatus());
        $this->assertEquals('pay', $upiEntity['type']);
        $this->assertEquals('1UPIInHulkTrml', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $payment = $this->authorizedFailedPayment($paymentId);

        $this->assertNull($payment['verified']);
        $this->assertEquals($payment['status'], 'authorized');

        $upiEntity = $this->getDbLastEntity('upi');
        $this->assertNotNull($upiEntity['npci_txn_id']);
    }

    public function testIntentPaymentWithTxnId()
    {
        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upiEntity = $this->getDbLastEntity('upi');
        $payment = $this->getDbLastPayment('payment');

        $this->assertEquals('HDF2C8B11D1FBDB4FC78F4E37A19AB6413D', $upiEntity['npci_txn_id']);

        $newTxnId = 'HDF2C8B_RANDOM_STRING_RANDOM_STRING';

        $override = [
            'txn_id'                => $newTxnId,
        ];

        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($override)
            {
                if ($action === 'callback')
                {
                    $content['data'] = array_merge($content['data'], $override);
                }
            });

        $callback = $this->getMockServer()->getAsyncCallbackRequest($upiEntity, $payment);

        $this->sendRequest($callback);

        $upiEntity->reload();

        $this->assertEquals($newTxnId, $upiEntity['npci_txn_id']);
    }

    public function testIntentTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_hulk_tpv_terminal');

        $this->fixtures->merchant->enableTPV();

        $merchant = $this->getDbLastEntity('merchant', 'test');

        $this->createOrder([
            'amount'         => 50000,
            'currency'       => 'INR',
            'receipt'        => 'rcptid42',
            'method'         => 'upi',
            'bank'           => 'RATN',
            'account_number' => '04030403040304',
        ]);

        $order = $this->getDbLastEntity('order');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';
        $this->payment['order_id'] = $order->getPublicId();

        $this->mockServerRequestFunction(
            function($content, $action) use ($order, $merchant)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('expected_push', $content['type']);
                    $this->assertSame($order->getAccountNumber(), $content['caller_account_number']);
                    $this->assertSame((string) $merchant['category'], $content['category_code']);
                }
            });

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertEquals('1UPITpvHulkTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getDbLastEntity('upi');

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function testCollectForceAuthorized()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment->reload();
        $upi->reload();

        $this->assertSame('failed', $payment->getStatus());
        $this->assertSame('initiated', $upi->status_code);

        $this->forceAuthorizeFailedPayment($payment->getPublicId(),
            [
                'ifsc'              => 'HDFC0000011',
                'account_number'    => '110011001100',
            ]);

        $payment->reload();
        $upi->reload();

        $this->assertSame('authorized', $payment->getStatus());
        $this->assertSame('completed', $upi->status_code);
        $this->assertSame('HDFC0000011', $upi->ifsc);
        $this->assertSame('110011001100', $upi->account_number);
    }

    public function testIntentForceAuthorized()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('created', $upi->status_code);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment->reload();
        $upi->reload();

        $this->assertSame('failed', $payment->getStatus());
        $this->assertSame('created', $upi->status_code);
        $this->assertNotNull($upi->npci_reference_id);

        $this->forceAuthorizeFailedPayment($payment->getPublicId(),
            [
                'ifsc'              => 'HDFC0000011',
                'account_number'    => '110011001100',
                'vpa'               => 'vishnu@icici',
                'npci_reference_id' => '800800800800',
            ]);

        $payment->reload();
        $upi->reload();

        $this->assertSame('authorized', $payment->getStatus());
        $this->assertSame('completed', $upi->status_code);
        $this->assertSame('HDFC0000011', $upi->ifsc);
        $this->assertSame('110011001100', $upi->account_number);
        $this->assertSame('vishnu@icici', $upi->vpa);
        $this->assertSame('icici', $upi->provider);
        $this->assertSame('800800800800', $upi->npci_reference_id);
    }

    public function testCollectOnGatewayAppAuth()
    {
        $this->fixtures->edit('terminal', $this->sharedTerminal->getId(),
            [
                'gateway_access_code' => 'app'
            ]);

        $this->mockServerRequestFunction(
            function($content, $action)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('pull', $content['type']);

                    $this->assertSame('vpa_merchantsVpaId', $content['receiver_id']);
                }
            });

        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));
    }

    public function testIntentOnGatewayAppAuth()
    {
        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal',
            [
                'gateway_access_code' => 'app'
            ]);

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->mockServerRequestFunction(
            function($content, $action)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('expected_push', $content['type']);

                    $this->assertSame('vpa_merchantsVpaId', $content['receiver_id']);
                }
            });

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
    }

    public function testVerifyFailedPayments()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $auth = $this->doAuthPaymentViaAjaxRoute($this->payment);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment = $this->getDbLastPayment();

        $this->assertSame('failed', $payment->getStatus());

        $this->ba->appAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertSame(1, $content['verifiable_count']);
        $this->assertSame(1, $content['authorized']);
        $this->assertSame(1, $content['verified_payments']);

        $payment->reload();

        $this->assertSame('authorized', $payment->getStatus());
    }

    public function testVerifyFailedIntentPayments()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal',
            [
                'gateway_access_code' => 'app'
            ]);

        unset($this->payment['description']);
        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment = $this->getDbLastPayment();

        $this->assertSame('failed', $payment->getStatus());

        $this->ba->appAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertSame(1, $content['verifiable_count']);
        $this->assertSame(1, $content['authorized']);
        $this->assertSame(1, $content['verified_payments']);

        $payment->reload();

        $this->assertSame('authorized', $payment->getStatus());

        // Intent when failed will not have vpa details
        // It must get updated when the gateway return in completed response
        $this->assertSame('vishnu@icici', $payment->getVpa());
    }

    public function testVerifyFailedIntentPaymentsFailedAtGateway()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal',
            [
                'gateway_access_code' => 'app'
            ]);

        unset($this->payment['description']);
        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment = $this->getDbLastPayment();

        $this->assertSame('failed', $payment->getStatus());

        $this->ba->appAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                // Marking transaction incomplete
                $content['status'] = 'created';
                $content['sender'] = [];
            });

        $content = $this->makeRequestAndGetContent($request);

        $this->assertSame(1, $content['verifiable_count']);
        $this->assertSame(1, $content['verified_payments']);
        $this->assertSame(0, $content['authorized']);
        $this->assertSame(1, $content['success']);

        $payment->reload();

        $this->assertSame('failed', $payment->getStatus());

        $this->assertSame(null, $payment->getVpa());
    }
}
