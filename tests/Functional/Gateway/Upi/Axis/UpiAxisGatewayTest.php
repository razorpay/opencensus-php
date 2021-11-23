<?php

namespace RZP\Tests\Functional\Gateway\Upi\Axis;

use RZP\Gateway\Upi\Axis\Url;
use RZP\Services\RazorXClient;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Axis\Fields;
use RZP\Gateway\Upi\Axis\Status;
use RZP\Gateway\Upi\Axis\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Account;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Jobs\CorePaymentServiceSync;


class UpiAxisGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;


    /**
     * Payment array
     * @var array
     */
    protected $payment;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->gateway = 'upi_axis';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();

        unset($this->payment['description']);
    }

    public function testPayment($status = 'created')
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertSame('created', $payment->getStatus());

        $upi = $this->getDBLastEntity('upi');

        $this->assertNotNull($upi[Entity::NPCI_TXN_ID]);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $response);

        $payment->reload();

        $this->assertEquals('authorized', $payment['status']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        $this->assertNotNull($upi['npci_txn_id']);

        $this->assertEquals($payment['reference1'], $upi['npci_txn_id']);

        $this->assertNotNull($payment['acquirer_data']['rrn']);

        $this->assertNotNull($payment['acquirer_data']['upi_transaction_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        return $payment;
    }

    public function testTpvPayment()
    {
        $this->fixtures->create('terminal:shared_upi_axis_tpv_terminal', ['tpv' => 3]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->startTest();

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = $order['amount'];

        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('100UPIAXISTpvl', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('collect', $gatewayEntity['type']);

        $this->assertEquals('vishnu@icici', $gatewayEntity['vpa']);
    }

    public function testFailedTpvPayment()
    {
        $this->fixtures->create('terminal:shared_upi_axis_tpv_terminal', ['tpv' => 3]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->startTest();

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = $order['amount'];

        $payment['order_id'] = $order['id'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['code'] = '111';
        }, $this->gateway);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            \RZP\Exception\GatewayErrorException::class);
    }

    public function testVerifyPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi = $this->getDBLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $this->assertEquals('authorized', $payment['status']);

        $response = $this->verifyPayment($payment->getPublicId());

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $this->assertEquals($upi['vpa'], 'vishnu@icici');

        $this->assertSame(1, $payment['verified']);
    }

    protected function getDefaultUpiPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'upi';

        $payment['vpa'] = 'vishnu@icici';

        return $payment;
    }

    public function testRefundSuccess()
    {
        $payment = $this->testPayment();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content[Fields::CODE] = '111';
            }
        }, $this->gateway);

        // Attempt a partial refund
        $this->refundPayment($payment->getPublicId(), 100);

        $upi1 = $this->getDbLastEntity('upi');

        $this->refundPayment($payment->getPublicId(), 100);

        $upi2 = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi1['refund_id']);
        $this->assertNotNull($upi2['refund_id']);

        $this->assertSame($upi1['payment_id'], $upi2['payment_id']);

        $this->assertNotSame($upi1['refund_id'], $upi2['refund_id']);

        $this->assertSame('refund', $upi1['action']);
        $this->assertSame('refund', $upi2['action']);

        $this->assertSame(true, $upi1['received']);
        $this->assertSame(true, $upi2['received']);

        $this->assertNotNull($upi1['status_code']);
        $this->assertNotNull($upi2['status_code']);
    }

    public function testUpiAmountCap()
    {
        $this->payment['vpa'] = 'vishnu@upi';

        $payment = $this->payment;

        $payment['amount'] = 20000001;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
    }

    public function testUpiAmountCapSuccess()
    {
        $this->payment['vpa'] = 'vishnu@upi';

        $payment = $this->payment;

        $payment['amount'] = 19000000;

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertNotNull($response['payment_id']);
    }

    public function testVpaWithCapitalPspValidation($status = 'created')
    {
        $this->payment['vpa'] = 'vishnu@ICICI';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertSame('created', $payment->getStatus());

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $response);

        $this->assertEquals('vishnu@icici', $upi[Entity::VPA]);
    }

    public function testVpaWithoutPspValidation()
    {
        $this->payment['vpa'] = 'invalidvpa';

        $payment = $this->payment;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment) {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
    }

    // API Payment = created
    // Gateway = success
    public function testVerificationFailure()
    {
        $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $this->assertNull($upi->getNpciReferenceId());

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment->getPublicId());
        });

        $payment->reload();

        $upi->reload();

        $this->assertSame(0, $payment->verified);
        $this->assertNotNull($payment->getVerifyAt());

        $this->assertNotNull($upi->getNpciReferenceId());
        $this->assertNotNull($upi->getVpa());
    }

    public function testLateAuthorization()
    {
        $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $this->assertNull($upi->getNpciReferenceId());

        $this->authorizedFailedPayment($payment->getPublicId());

        $payment->reload();

        $this->assertTrue($payment->isAuthorized());
        $this->assertTrue($payment->isLateAuthorized());
        $this->assertSame('714513318376', $payment->getReference16());

        $upi->reload();

        $this->assertSame('714513318376', $upi->getNpciReferenceId());
        $this->assertSame('vishnu@icici', $upi->getVpa());
        $this->assertSame('icici', $upi->provider);
        $this->assertSame('ICIC', $upi->bank);
        $this->assertNotNull($upi->getNpciTransactionId());
    }

    public function testIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_axis_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->fixtures->merchant->setCategory('1111');

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $intentUrl = $response['data']['intent_url'];
        $mccFromIntentUrl = substr($intentUrl, strpos($intentUrl,'&mc=') + 4, 4);

        $this->assertEquals('1111', $mccFromIntentUrl);

        $this->checkPaymentStatus($paymentId, 'created');

        $upi = $this->getDBLastEntity('upi');

        $payment = $this->getDbLastPayment();

        $this->assertEquals('UPIAXISIntTmnl', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $response);

        $payment->reload();

        $this->assertEquals('authorized', $payment['status']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        return $payment;
    }

    public function testIntentPaymentVerifyAndRefund()
    {
        $payment = $this->testIntentPayment();

        $response = $this->verifyPayment($payment->getPublicId());

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $this->assertEquals($upi['vpa'], 'default@axis');

        $this->assertSame(1, $payment['verified']);

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content[Fields::CODE] = '111';
            }
        }, $this->gateway);

        $this->refundPayment($payment->getPublicId(), 100);

        $upi1 = $this->getDbLastEntity('upi');

        $this->refundPayment($payment->getPublicId(), 100);

        $upi2 = $this->getDbLastEntity('upi');

        $refund = $this->getDbLastRefund();

        $this->assertEquals('processed', $refund['status']);

        $this->assertNotNull($upi1['refund_id']);
        $this->assertNotNull($upi2['refund_id']);

        $this->assertSame($upi1['payment_id'], $upi2['payment_id']);

        $this->assertNotSame($upi1['refund_id'], $upi2['refund_id']);

        $this->assertSame('refund', $upi1['action']);
        $this->assertSame('refund', $upi2['action']);

        $this->assertSame(true, $upi1['received']);
        $this->assertSame(true, $upi2['received']);

        $this->assertNotNull($upi1['status_code']);
        $this->assertNotNull($upi2['status_code']);
    }

    public function testIntentPaymentFailure()
    {
        $this->fixtures->create('terminal:shared_upi_axis_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            // validation error
            $content[Fields::CODE] = '111';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });
    }

    public function testIntentPaymentCallbackFailure()
    {
        $this->fixtures->create('terminal:shared_upi_axis_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDBLastEntity('upi');

        $payment = $this->getDbLastPayment();

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray(), 'U30');

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $this->assertEquals('failed', $payment['status']);
    }

    public function testIntentDisabledPayment()
    {
        $this->fixtures->merchant->addFeatures(['disable_upi_intent']);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['description']);
        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testIntentTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_axis_intent_tpv_terminal');

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

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('100UPIAXISTpvl', $payment['terminal_id']);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $response);

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertNotNull($upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        $payment->reload();

        $this->assertEquals('captured', $payment['status']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getDbLastEntity('upi');

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function testRefundFailure()
    {
        $payment = $this->testPayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $content[Fields::CODE] = 'A79';
        });

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
    }

    public function testVerifyRefund()
    {
        $payment = $this->testPayment();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['code'] = 'A79';
        }, $this->gateway);

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getLastEntity('refund', true);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $this->resetMockServer();

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
    }

    public function testVerifyRefundFailed()
    {
        $payment = $this->testPayment();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if (($action === 'refund') or ($action === 'verify_refund'))
            {
                $content[Fields::CODE] = '111';
            }
        }, $this->gateway);

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->resetMockServer();

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
    }

    public function testRefundAndVerifyOnIntent()
    {
        $payment = $this->testIntentPayment();

        $this->mockServerRequestFunction(
            function(& $request)
            {
                $requestArray = json_decode($request, true);

                $this->assertEquals('RAZORPPROD4264718195', $requestArray['merchId']);
                $this->assertEquals('RAZORPPRODAPP4264718195', $requestArray['merchChanId']);
            });

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content[Fields::CODE] = '111';
            }
        }, $this->gateway);

        $this->refundPayment('pay_' . $payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);

        $gateway = $this->getLastEntity('upi', true);

        $this->assertEquals('pay', $gateway['type']);

        $this->mockServerRequestFunction(
            function(& $request)
            {
                $requestArray = $request;

                $this->assertEquals('RAZORPPROD4264718195', $requestArray['merchid']);
                $this->assertEquals('RAZORPPRODAPP4264718195', $requestArray['merchchanid']);
            });

        $this->verifyPayment('pay_' . $payment['id']);

        $payment->reload();

        $this->assertEquals('1', $payment['verified']);
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testDuplicateErrorCode()
    {
        $this->fixtures->create('terminal:shared_upi_axis_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDBLastEntity('upi');

        $payment = $this->getDbLastPayment();

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray(), '111',
            'TOKEN NOT FOUND');

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $this->assertEquals('GATEWAY_ERROR_TOKEN_NOT_FOUND', $payment['internal_error_code']);
        $this->assertEquals('Payment processing failed due to error at bank or wallet gateway',
            $payment['error_description']);
    }

    public function testDuplicateErrorCode2()
    {
        $this->fixtures->create('terminal:shared_upi_axis_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDBLastEntity('upi');

        $payment = $this->getDbLastPayment();

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray(), '111',
            'DUPLICATE TOKEN');

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $this->assertEquals('GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST', $payment['internal_error_code']);
        $this->assertEquals('Payment was unsuccessful due to a temporary issue. Any amount deducted will be refunded within 5-7 working days.',
            $payment['error_description']);
    }

    public function testUnexpectedPaymentSuccess()
    {
        $id = str_random(12);

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id));

        $this->assertCallbackResponse($response);

        $payment = $this->getDbLastEntity('payment');

        $this->assertArraySubset([
            'merchant_id'       => '100DemoAccount',
            'method'            => 'upi',
            'amount'            => 60000,
            'status'            => 'authorized',
            'amount_authorized' => 60000,
            'vpa'               => 'unexpected@axisbank',
            'gateway'           => 'upi_axis',
            'terminal_id'       => '100UPIAXISTmnl',
            'gateway_captured'  => true,
            'reference16'       => '714513318376'
        ], $payment->toArray());

        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'gateway'               => 'upi_axis',
            'action'                => 'authorize',
            'type'                  => 'pay',
            'amount'                => 60000,
            'vpa'                   => 'unexpected@axisbank',
            'merchant_reference'    => $id,
            'gateway_merchant_id'   => 'TSTMERCHI',
            'npci_txn_id'           => 'AXIS00090439839',
            'npci_reference_id'     => '714513318376'
        ], $upi->toArray());
    }

    public function testUnexpectedPaymentFailure()
    {
        $id = str_random(12);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['data'][0]['code']     = 'ZM';
                $content['data'][0]['result']   = 'F';
            }
        });

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id, 'ZM', 'Failed'));

        // We should have gotten a successful response

        $this->assertCallbackResponse($response, 'ZM', 'Failed');

        $collection = $this->getDbEntities('payment');

        $this->assertNull($collection->first());

        $collection = $this->getDbEntities('upi');

        $this->assertNull($collection->first());
    }

    public function testUnexpectedPaymentPending()
    {
        $id = str_random(12);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['data'][0]['code']     = 'D';
                $content['data'][0]['result']   = 'P';
            }
        });

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id, 'BT', 'Deemed'));

        // We should have gotten a successful response

        $this->assertCallbackResponse($response, 'BT', 'Deemed');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            // Payment is created for the merchant itself
            'merchant_id'       => '100DemoAccount',
            'method'            => 'upi',
            'amount'            => 60000,
            // Payment is also auto captured
            'status'            => 'failed',
            'amount_authorized' => 0,
            'vpa'               => 'unexpected@axisbank',
            'gateway'           => 'upi_axis',
            'gateway_captured'  => false,
        ], $payment->toArray());
    }

    public function testUnexpectedPaymentDuplicate()
    {
        $id = str_random(12);

        $content = $this->unexpectedPaymentContent($id);

        $response = $this->makeS2sCallbackAndGetContent($content);

        $this->assertCallbackResponse($response);

        // There should be only one payment/upi
        $collection = $this->getDbEntities('payment');

        $this->assertSame(1, $collection->count());

        $collection = $this->getDbEntities('upi');

        $this->assertSame(1, $collection->count());

        $response = $this->makeS2sCallbackAndGetContent($content);

        $this->assertCallbackResponse($response);

        // There should still be only one payment/upi
        $collection = $this->getDbEntities('payment');

        $this->assertSame(1, $collection->count());

        $collection = $this->getDbEntities('upi');

        $this->assertSame(1, $collection->count());
    }

    public function testUnexpectedPaymentAmountMismatch()
    {
        $id = str_random(12);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['data'][0]['amount']     = '600.50';
            }
        });

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id));

        // We should have gotten a successful response
        $this->assertCallbackResponse($response);

        $collection = $this->getDbEntities('payment');

        $this->assertNull($collection->first());

        $collection = $this->getDbEntities('upi');

        $this->assertNull($collection->first());
    }

    public function testUnexpectedPaymentSuccessOnDirectSettlementMerchant()
    {
        // Same GMID is set for both terminal, changing in callback requires significant refactor
        $this->sharedTerminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_axis_terminal');

        $id = str_random(12);

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id));

        $this->assertCallbackResponse($response);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            // Payment is created for the merchant itself
            'merchant_id'       => '10000000000000',
            'method'            => 'upi',
            'amount'            => 60000,
            // Payment is also auto captured
            'status'            => 'captured',
            'amount_authorized' => 60000,
            'vpa'               => 'unexpected@axisbank',
            'gateway'           => 'upi_axis',
            'terminal_id'       => $terminal->getId(),
            'gateway_captured'  => true,
        ], $payment->toArray());
    }

    public function testUnexpectedPaymentPendingOnDirectSettlementMerchant()
    {
        // Same GMID is set for both terminal, changing in callback requires significant refactor
        $this->sharedTerminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_axis_terminal');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['data'][0]['code']     = 'D';
                $content['data'][0]['result']   = 'P';
            }
        });

        $id = str_random(12);

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id, 'BT', 'Deemed'));

        $this->assertCallbackResponse($response, 'BT', 'Deemed');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            // Payment is created for the merchant itself
            'merchant_id'       => '10000000000000',
            'method'            => 'upi',
            'amount'            => 60000,
            // Payment is also auto captured
            'status'            => 'failed',
            'amount_authorized' => 0,
            'vpa'               => 'unexpected@axisbank',
            'gateway'           => 'upi_axis',
            'terminal_id'       => $terminal->getId(),
            'gateway_captured'  => false,
        ], $payment->toArray());
    }

    public function testUnexpectedPaymentFailureOnDirectSettlementMerchant()
    {
        // Same GMID is set for both terminal, changing in callback requires significant refactor
        $this->sharedTerminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_axis_terminal');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['data'][0]['code']     = 'ZM';
                $content['data'][0]['result']   = 'F';
            }
        });

        $id = str_random(12);

        $response = $this->makeS2sCallbackAndGetContent($this->unexpectedPaymentContent($id, 'ZM', 'Failed'));

        $this->assertCallbackResponse($response, 'ZM', 'Failed');

        $collection = $this->getDbEntities('payment');

        $this->assertNull($collection->first());

        $collection = $this->getDbEntities('upi');

        $this->assertNull($collection->first());
    }

    public function testInvalidGatewayResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = 'Invalid response from axis gateway which is usually html';
            }
        });

        $this->makeRequestAndCatchException(
            function()
            {
                $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
            },
            GatewayErrorException::class);

        $payment = $this->getDbLastPayment();

        $this->assertSame('GATEWAY_ERROR_INVALID_RESPONSE', $payment->getInternalErrorCode());
    }

    protected function unexpectedPaymentContent(string $id, string $status = '00', string $result = 'Success')
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);
        $upi = [
            'amount'    => '60000',
            'vpa'       => 'unexpected@axisbank'
        ];
        $payment = [
            'id'        => $id,
        ];
        $content = $this->mockServer()->getAsyncCallbackContent($upi, $payment, $status, $result);
        return $content;
    }

    protected function assertCallbackResponse(array $response, string $status = '00', string $result = 'Success')
    {
        $this->assertEquals(
            [
                'callBackstatusCode'        => $status,
                'callBackstatusDescription' => $result,
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $response);
    }
}
