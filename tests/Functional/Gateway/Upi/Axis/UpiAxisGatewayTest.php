<?php

namespace RZP\Tests\Functional\Gateway\Upi\Axis;

use Mockery;
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
use RZP\Exception;
use RZP\Models\Payment;

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

    public function testCorrectPaymentDetailsPosted()
    {
        $asserted = false;

        $this->mockServerRequestFunction(function(& $request, $action = null) use (& $asserted) {

            $input = json_decode($request, true);

            $this->assertEquals(
                number_format((float)$this->payment['amount'] / 100, 2, '.', ''),
                $input['amount']);

            $this->assertEquals($this->payment['vpa'],
                $input['customervpa']);

            $asserted = true;
        });

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertTrue($asserted, 'The request contents were not asserted');

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

    public function testVerifyGatewayPayment()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi = $this->getDBLastEntity('upi');

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();
        $this->assertEquals('authorized', $payment['status']);
        $payment_updated_at = $payment['updated_at'];

        $upi = $this->getDBLastEntity('upi');
        $updated_at = $upi['updated_at'];

        sleep(1);

        $this->verifyGatewayPayment($payment->getPublicId());

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        // asserting that entities are not updated
        $this->assertEquals($updated_at, $upi['updated_at']);
        $this->assertEquals($payment_updated_at, $payment['updated_at']);
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

    /************  ART Related Testcases ****************/

    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/upi/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * Authorize the failed payment by force authorizing it
     */
    public function testAuthorizeFailedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $callbackResponse);

        $this->fixtures->payment->edit($payment['id'],
                                       [
                                           'status'              => 'failed',
                                           'authorized_At'       => null,
                                           'error_code'          => 'BAD_REQUEST_ERROR',
                                           'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                                           'error_description'   => 'Payment was not completed on time.',
                                       ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['upi']['gateway'] = 'upi_axis';

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertEquals('123456789013', $upiEntity['npci_reference_id']);

        $this->assertEquals('razor.pay@sbi', $upiEntity['vpa']);

        $this->assertEquals('00', $upiEntity['status_code']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('123456789013', $updatedPayment['reference16']);

        $this->assertEquals('razor.pay@sbi', $updatedPayment['vpa']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    /**
     * Validate negative case of authorizing successful payment
     */
    public function testForceAuthorizeSuccessfulPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $callbackResponse);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('authorized', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['upi']['gateway'] = 'upi_axis';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $this->makeRequestAndCatchException(function () use ($content) {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $content = $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
        'Non failed payment given for authorization');
    }

    /**
     * Checks for validation failure in case of missing payment_id
     */
    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['upi']['gateway'] = 'upi_axis';

        $this->makeRequestAndCatchException(function () use ($content) {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The payment.id field is required.');
    }

    /**
     * Checks for validation failure in case of missing vpa
     */
    public function testForceAuthorizePaymentValidationFailure2(){
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $callbackResponse);

        $this->fixtures->payment->edit($payment['id'],
                                       [
                                           'status'              => 'failed',
                                           'authorized_At'       => null,
                                           'error_code'          => 'BAD_REQUEST_ERROR',
                                           'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                                           'error_description'   => 'Payment was not completed on time.',
                                       ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['upi']['gateway'] = 'upi_axis';

        $content['meta']['force_auth_payment'] = true;

        unset( $content['upi']['vpa']);

        $this->makeRequestAndCatchException(function () use ($content) {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $content = $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The upi.vpa field is required.');

    }

    /**
     * Checks for amount mismatch case in force auth flow
     */
    public function testForceAuthorizePaymentAmountMismatch()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $callbackResponse);

        $this->fixtures->payment->edit($payment['id'],
                                       [
                                           'status'              => 'failed',
                                           'authorized_At'       => null,
                                           'error_code'          => 'BAD_REQUEST_ERROR',
                                           'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                                           'error_description'   => 'Payment was not completed on time.',
                                       ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['upi']['gateway'] = 'upi_axis';

        $content['meta']['force_auth_payment'] = true;

        // Change amount to 60000 for mismatch scenario
        $content['payment']['amount'] = 60000;

        $this->makeRequestAndCatchException(function () use ($content) {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $content = $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The amount does not match with payment amount');

    }

    /**
     * Authorize the failed payment by verifying at gateway
     */
    public function testVerifyAuthorizeFailedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $callbackResponse);

        $this->fixtures->payment->edit($payment['id'],
                                       [
                                           'status'              => 'failed',
                                           'authorized_At'       => null,
                                           'error_code'          => 'BAD_REQUEST_ERROR',
                                           'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                                           'error_description'   => 'Payment was not completed on time.',
                                       ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['upi']['gateway'] = 'upi_axis';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('714513318376', $upiEntity['npci_reference_id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    /**
     * Authorize the failed auto capture payment by verifying at gateway
     */
    public function testVerifyAuthorizeFailedAutoCapturePayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ],
            $callbackResponse);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
                'reference3'          => '1',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['upi']['gateway'] = 'upi_axis';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('714513318376', $upiEntity['npci_reference_id']);

        $this->assertEquals('captured', $updatedPayment['status']);

        $this->assertEquals(true, $response['success']);

        $this->assertNotNull($updatedPayment['reference16']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    public function testUnexpectedPaymentCreation()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $content['terminal']['gateway'] = 'upi_axis';

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }
    public function testUnexpectedPaymentCreationML01()
    {
        $content = $this->buildUnexpectedPaymentRequest();
        //used this merchant reference to mock verify with ML01
        $content['upi']['merchant_reference'] = 'IShcnbF6tsOz';

        $content['terminal']['gateway'] = 'upi_axis';

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }

    /**
     * Tests the payment create for multiple payments with same RRN
     */
    public function testUnexpectedPaymentForDuplicateRRN()
    {
        $unexpectedPaymentContent = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($unexpectedPaymentContent);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $this->payment[Payment\Entity::VPA] = 'unexpectedPayment@axisbank';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function() use ($unexpectedPaymentContent) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $unexpectedPaymentContent,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Multiple payments with same RRN');
    }

    /**
     * Test unexpected payment request for missing npci reference id
     */
    public function testUnexpectedPaymentValidationFailure()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        // Unsetting the npci_reference_id to mimic validation failure
        unset($content['upi']['npci_reference_id']);
        unset($content['terminal']['gateway_merchant_id']);

        $this->makeRequestAndCatchException(function () use ($content) {
            $request = [
                'url' => '/payments/create/upi/unexpected',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The upi.npci reference id field is required.');
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     * for amount mismatch cases
     */
    public function testDuplicateUnexpectedPaymentForAmountMismatch()
    {
        $this->payment[Payment\Entity::VPA] = 'unexpectedPayment@axisbank';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $content = $this->buildUnexpectedPaymentRequest();

        $content['upi']['merchant_reference'] = $upi->getPaymentId();

        $content['upi']['vpa'] = $upi->getVpa();

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function () use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
           'Multiple payments with same RRN');
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     */
    public function testDuplicateUnexpectedPayment()
    {
        $this->payment[Payment\Entity::VPA] = 'unexpectedPayment@axisbank';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $content = $this->buildUnexpectedPaymentRequest();

        $content['upi']['merchant_reference'] = $upi->getPaymentId();

        $content['upi']['vpa'] = $upi->getVpa();

        $content['payment']['amount'] = 50000;

        // Hit payment create again
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Duplicate Unexpected payment with same amount');
    }

    /**
     * Verifies the unexpected payment request at gateway
     * before creating unexpected payment
     */
    public function testInvalidUnexpectedPaymentCreation()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getDbLastUpi();

        $content = $this->buildUnexpectedPaymentRequest();

        $content['upi']['merchant_reference'] = $upi->getPaymentId();
        //Passing different amount in art request to verify unexpected payment request at gateway
        $content['payment']['amount'] = 10000;

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertEmpty($response['payment_id']);

        $this->assertFalse($response['success']);
    }

    /**
     *  Test Multiple credit payment
     */
    public function testMultipleCreditPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->fixtures->payment->edit($payment['id'],['vpa' => 'multipleRRN@axisbank']);

        $content = $this->buildUnexpectedPaymentRequest();

        $content['terminal']['gateway'] = 'upi_axis';

        $content['upi']['merchant_reference'] = $payment->getId();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        // Failing the verify response to assert the  verify request change
        $this->assertEmpty($response['payment_id']);

        $this->assertFalse($response['success']);
    }


    protected function makeUnexpectedPaymentAndGetContent(array $content)
    {
        $request = [
            'url' => '/payments/create/upi/unexpected',
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function buildUnexpectedPaymentRequest()
    {
        $this->fixtures->merchant->createAccount('100DemoAccount');
        $this->fixtures->merchant->enableUpi('100DemoAccount');

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        $content['terminal']['gateway']             = 'upi_axis';
        $content['terminal']['gateway_merchant_id'] = $this->sharedTerminal->getGatewayMerchantId();
        $content['payment']['amount'] = 60000;

        $content['upi']['npci_reference_id'] = '714513318376';

        return $content;
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
