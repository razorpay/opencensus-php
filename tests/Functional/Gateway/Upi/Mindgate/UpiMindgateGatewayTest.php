<?php

namespace RZP\Tests\Functional\Gateway\Upi\Mindgate;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Metric;
use RZP\Models\Payment\Method;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Base\Secure;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\RuntimeException;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\Metric as PaymentMetric;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\MocksMetricTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Unit\Mock\Metric\Driver as MockMetricDriver;

class UpiMindgateGatewayTest extends TestCase
{
    use PaymentTrait;
    use MocksMetricTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */
    protected $sharedTerminal;
    protected $bharatQrTerminal;

    /**
     * Payment array
     * @var array
     */
    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/MindgateGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->bharatQrTerminal = $this->fixtures->create('terminal:bharat_qr_upi_mindgate_terminal');

        $this->gateway = Gateway::UPI_MINDGATE;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');
    }

    /**
     * Tests the happy-flow of a complete payment
     * @param string $status
     * @return mixed
     */
    public function testPayment($status = 'created')
    {
        $metricDriver = $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        /**
           Adding a check that , refund_at should be null while the payment is
            in created state
        */
        $this->assertNull($payment['refund_at']);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotNull($payment['refund_at']);

        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertNotNull($upiEntity['npci_reference_id']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);
        $this->assertNotNull($payment['acquirer_data']['upi_transaction_id']);

        $this->assertEquals($payment['reference16'], $upiEntity['npci_reference_id']);
        $this->assertNotNull($upiEntity['gateway_payment_id']);
        $this->assertEquals($payment['reference1'],$upiEntity['gateway_payment_id']);
        $this->assertSame('00', $upiEntity['status_code']);

        // Here we are asserting for all both records i.e. authorize and callback
        $this->assertArraySelectiveEquals([
            [
                Metric::DIMENSION_ACTION            => 'authorize',
                Metric::DIMENSION_STATUS            => 'success',
                Metric::DIMENSION_INSTRUMENT_TYPE   => 'collect',
                Metric::DIMENSION_UPI_PSP           => 'none',
                Metric::DIMENSION_STATUS_CODE       => 200
            ],
            [
                Metric::DIMENSION_ACTION            => 'callback',
                Metric::DIMENSION_STATUS            => 'success',
                Metric::DIMENSION_INSTRUMENT_TYPE   => 'collect',
                Metric::DIMENSION_UPI_PSP           => 'none',
                Metric::DIMENSION_STATUS_CODE       => 200
            ],
        ], $metricDriver->metric(Metric::GATEWAY_REQUEST_COUNT_V3));

        // Here we are asserting for metric data being pushed to
        // histogram metric in callback flow
        $this->assertArraySelectiveEquals([
            [
                Metric::DIMENSION_ACTION    => 'callback',
                Metric::DIMENSION_GATEWAY   => 'upi_mindgate'
            ],
        ], $metricDriver->metric(Metric::GATEWAY_REQUEST_TIME, MockMetricDriver::HISTOGRAM));

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    public function testPaymentAsyncStatusException()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isCreated());

        $response = $this->getPaymentStatus($payment->getPublicId());

        $this->assertSame('created', $response['status']);

        // Adding 12 time out minutes and 3 minutes for cron buffer and 30 seconds for test case buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(15)->addSecond(30));

        $key = Payment\Entity::getCacheUpiStatusKey($payment->getPublicId());

        \Cache::forget($key);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->getPaymentStatus($payment->getPublicId());
            },
            BadRequestException::class,
            'Payment was not completed on time.');

        // Still in created state
        $this->assertTrue($payment->refresh()->isCreated());
    }

    public function testIntentPayment()
    {
        $metricDriver = $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        $this->fixtures->create('terminal:shared_upi_mindgate_intent_terminal');

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

        $upiEntity = $this->getLastEntity('upi_mindgate', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('pay', $upiEntity['type']);
        $this->assertEquals('1UpiIntMndgate', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[8] = 'user@hdfcbank';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['vpa'], 'user@hdfcbank');
        $this->assertEquals('HDFC', $upi['bank']);
        $this->assertEquals('hdfc', $upi['acquirer']);
        $this->assertEquals('hdfcbank', $upi['provider']);

        // Here we are asserting for all both records i.e. authorize and callback
        $this->assertArraySelectiveEquals([
            [
                Metric::DIMENSION_ACTION            => 'authorize',
                Metric::DIMENSION_STATUS            => 'success',
                Metric::DIMENSION_INSTRUMENT_TYPE   => 'pay',
                Metric::DIMENSION_UPI_PSP           => 'none',
            ],
            [
                Metric::DIMENSION_ACTION            => 'callback',
                Metric::DIMENSION_STATUS            => 'success',
                //TODO: This should be intent, fix this.
                Metric::DIMENSION_INSTRUMENT_TYPE   => 'pay',
                Metric::DIMENSION_UPI_PSP           => 'none',
            ],
        ], $metricDriver->metric(Metric::GATEWAY_REQUEST_COUNT_V3));
    }

    public function testIntentAuthorizeFailed()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $this->checkPaymentStatus($paymentId, 'created');

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertNull($payment['vpa']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['payer_va'] = 'random@rzp';
            }
        });

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('random@rzp', $payment['vpa']);
    }

    public function testSignedIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_signed_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);
        $this->assertArrayHasKey('qr_code_url', $response['data']);

        $upi = $this->getDbLastEntity('upi');
        $payment = $this->getDbLastPayment();

        $this->assertEquals('pay', $upi['type']);
        $this->assertEquals('1UpiIntMndgate', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $secure = new Secure([
            Secure::PUBLIC_KEY => $payment->terminal['gateway_access_code'],
        ]);

        $this->assertTrue($secure->verifyIntent($response['data']['intent_url']));
        $this->assertTrue($secure->verifyIntent($response['data']['qr_code_url']));
    }

    public function testSignedIntentInvoice()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_signed_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        // Adding current merchant for test only
        \Cache::forever('config:npci_upi_demo',
                        [
                            'merchants' => [
                                '10000000000000' => 'https://cdn.razorpay.com/i?',
                            ],
                        ]);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        \Cache::forget('config:npci_upi_demo');

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);
        $this->assertArrayHasKey('qr_code_url', $response['data']);

        $upi = $this->getDbLastEntity('upi');
        $payment = $this->getDbLastPayment();

        $this->assertEquals('pay', $upi['type']);
        $this->assertEquals('1UpiIntMndgate', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $secure = new Secure([
            Secure::PUBLIC_KEY => $payment->terminal['gateway_access_code'],
        ]);

        $this->assertTrue($secure->verifyIntent($response['data']['intent_url']));
        $this->assertTrue($secure->verifyIntent($response['data']['qr_code_url']));

        $this->assertStringContainsString('&url=', $response['data']['intent_url']);
        $this->assertStringContainsString('&url=', $response['data']['qr_code_url']);
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

    public function testFailedVpaValidation()
    {
        $this->markTestSkipped();

        $this->payment['vpa'] = 'invalidvpa@hdfcbank';

        $payment = $this->payment;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });

        $upiEntity = $this->getLastEntity(ConstantsEntity::UPI, true);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);
        $this->assertEquals(Status::FAILED, $payment['status']);

        $this->assertEquals('invalidvpa@hdfcbank', $upiEntity[Entity::VPA]);
        $this->assertNull($upiEntity[Entity::GATEWAY_PAYMENT_ID]);
        $this->assertNull($upiEntity[Entity::NPCI_REFERENCE_ID]);
        $this->assertEquals($payment['reference16'], $upiEntity['npci_reference_id']);
    }


    public function testVpaWithCapitalPspValidation($status = 'created')
    {
        $this->payment['vpa'] = 'vishnu@ICiCI';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);
        $this->assertEquals('vishnu@icici', $upiEntity[Entity::VPA]);

    }

    public function testVpaWithoutPspValidation()
    {
        $this->payment['vpa'] = 'invalidvpa';

        $payment = $this->payment;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
    }

    /**
     * Force the gateway to raise a failure on trying
     * to initiate web collect
     */
    public function testFailedCollect()
    {
        $this->payment['vpa'] = 'failedcollect@hdfcbank';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });
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

    public function testTpvPayment()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_tpv_terminal', ['tpv' => 3]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['amount'] = $order['amount'];
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('100UPIMndgtTpv', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('collect', $gatewayEntity['type']);
        $this->assertEquals('vishnu@icici', $gatewayEntity['vpa']);
    }

    public function testVerifyPayment()
    {
        // First we test that verification works
        // for a captured payment
        $payment = $this->testPayment();

        $this->payment = $this->verifyPayment($payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['account_number'], '004001551691');

        $this->assertEquals($upi['ifsc'], 'ICIC0000000');

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    // barricade verify
    public function testVerifyPaymentGateway()
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

    // In case callback does not return the bank account details, we call verify
    // and check that the details are saved in Upi Entity
    public function testSaveBankDetailsLaterInVerify()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $payment = $this->getEntityById('payment', $paymentId, true);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'callback')
                {
                    $content[16] = 'NA!NA!NA!NA';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $this->makeS2SCallbackAndGetContent($content);

        $this->payment = $this->verifyPayment($payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['account_number'], '004001551691');

        $this->assertEquals($upi['ifsc'], 'ICIC0000000');

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    // API Payment = created
    // Gateway = success
    public function testVerificationFailure()
    {
        $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($this->payment);

        $paymentId = $response['payment_id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($paymentId)
        {
            $this->verifyPayment($paymentId);
        });
    }

    /**
     * Create a payment, and reject it so callback
     * returns failure
     */
    public function testCollectRejectedFailure()
    {
        $metricDriver = $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'failed@hdfcbank';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertArraySelectiveEquals(['success' => true], $response);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER, $payment['internal_error_code']);

        $upiEntity = $this->getDbLastEntity('upi');

        $this->assertSame('ZA', $upiEntity['status_code']);

        $this->assertArraySubset([
            [
                Metric::DIMENSION_STATUS            => 'success',
                Metric::DIMENSION_ERROR             =>  null,
                Metric::DIMENSION_GATEWAY           => 'upi_mindgate',
                Metric::DIMENSION_ACTION            => 'authorize',
                Metric::DIMENSION_PAYMENT_METHOD    => 'upi',
                Metric::DIMENSION_INSTRUMENT_TYPE   => 'collect',
                Metric::DIMENSION_TPV               => '0',
                Metric::DIMENSION_STATUS_CODE       =>  200,
            ],
            [
                Metric::DIMENSION_STATUS            => 'failed',
                Metric::DIMENSION_ERROR             => 'BAD_REQUEST',
                Metric::DIMENSION_GATEWAY           => 'upi_mindgate',
                Metric::DIMENSION_ACTION            => 'callback',
                Metric::DIMENSION_PAYMENT_METHOD    => 'upi',
                Metric::DIMENSION_INSTRUMENT_TYPE   => 'collect',
                Metric::DIMENSION_TPV               => '0',
                Metric::DIMENSION_STATUS_CODE       =>  400,
            ],
        ], $metricDriver->metric(Metric::GATEWAY_REQUEST_COUNT_V3));
    }

    public function testCollectNumericRespCode()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'numericrespcode@hdfcbank';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertArraySelectiveEquals(['success' => true], $response);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals(ErrorCode::GATEWAY_ERROR_BANK_OFFLINE, $payment['internal_error_code']);
    }

    public function testCollectRejectedFailureUnknownRespCode()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'unknownrespcode@hdfcbank';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertArraySelectiveEquals(['success' => true], $response);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED, $payment['internal_error_code']);
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

    public function testRefundSuccess()
    {
        $payment = $this->testPayment();

        // Attempt a partial refund
        $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        // Mindgate refunds are processed via scrooge, so is_scrooge will be true
        $this->assertEquals($refund[RefundEntity::IS_SCROOGE], true);

        $this->assertNotNull($refund[PaymentEntity::ACQUIRER_DATA][RefundEntity::RRN]);
    }

    public function testRefundFailure()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $this->getFailureInVerifyRefund();

        $payment = $this->testPayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $entity = $this->getEntityById('refund', $refund['id'], 'admin');

        //
        // For scrooge refunds, status will always be created.
        //
        $this->assertEquals('created', $entity['status']);

        $this->assertEquals(false, $entity['gateway_refunded']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('BT', $upi['status_code']);
    }

    protected function getFailureInVerifyRefund()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }
        });
    }

    public function testRetryRefund()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $this->getFailureInVerifyRefund();

        $payment = $this->testPayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->mockServerContentFunction(function (& $content, $action = null) use($refund)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $refundId = substr($refund['id'], 5);

                $content[4] = 'SUCCESS';

                $this->assertEquals($refundId . 1, $content[1]);
            }
        });

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['status'], 'processed');
    }

    public function testRetryRefundWithBankAccount()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $this->getFailureInVerifyRefund();

        $payment = $this->testPayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->mockServerContentFunction(function (& $content, $action = null) use($refund)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $refundId = substr($refund['id'], 5);

                $content[4] = 'SUCCESS';

                $this->assertEquals($refundId . 1, $content[1]);
            }
        });

        $bankAccountData =
            [
                'bank_account' => [
                    'ifsc_code'         => '12345678911',
                    'account_number'    => '123456789',
                    'beneficiary_name'  => 'test'
                ]
            ];

        $this->retryFailedRefund($refund['id'], $refund['payment_id'], $bankAccountData);

        $refund = $this->getLastEntity('refund', true);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        // Refund will be in created state
        $this->assertEquals($refund['status'], 'created');
    }

    public function testBankDetailsAreSaved()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'callback')
                {
                    $content[16] = 'PNB!1000000000!PNB10010010!9800000000';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['account_number'], '1000000000');

        $this->assertEquals($upi['ifsc'], 'PNB10010010');

    }

    public function testBankDetailsAreNotSavedInCaseFailed()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'callback')
                {
                    $content[16] = 'NA!109090902020!NA!NA';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['account_number']);

        $this->assertNull($upi['ifsc']);

    }

    public function testValidateVpaSuccess()
    {
        $metricDriver = $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMindgate');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();

        $this->assertArraySelectiveEquals([
            [
                Metric::DIMENSION_STATUS            => 'success',
                Metric::DIMENSION_GATEWAY           => 'upi_mindgate',
                Metric::DIMENSION_ACTION            => 'validate_vpa',
                Metric::DIMENSION_UPI_PSP           => 'google_pay',
            ],
        ], $metricDriver->metric(Metric::GATEWAY_REQUEST_COUNT_V3));
    }

    public function testValidateVpaFailure()
    {
        $metricDriver = $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMindgate');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();

        $this->assertArraySelectiveEquals([
            [
                Metric::DIMENSION_STATUS            => 'failed',
                Metric::DIMENSION_GATEWAY           => 'upi_mindgate',
                Metric::DIMENSION_ACTION            => 'validate_vpa',
                Metric::DIMENSION_UPI_PSP           => 'none',
            ],
        ], $metricDriver->metric(Metric::GATEWAY_REQUEST_COUNT_V3));
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    protected function createUnexpectedPayment($data)
    {
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $data['meRes'] = $this->mockServer()->encrypt($data['meRes']);

        $response = $this->makeS2SCallbackAndGetContent($data);

        return $response;
    }

    public function testUnexpectedPaymentSuccess()
    {
        $this->disableUnexpectedPaymentRefundImmediately();

        $data = $this->testData[__FUNCTION__];

        $response = $this->createUnexpectedPayment($data);

        $this->assertTrue($response['success']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $callbackAmount = explode('|', $data['meRes'])[2];

        // Asserting that conversion to int has not changed amount
        $this->assertEquals(($callbackAmount), $paymentEntity['amount']/100);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            '826115528405'                         => $paymentEntity['reference16']
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertNull($paymentEntity['verified']);

        $this->verifyPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($paymentEntity['refund_at']);

        $this->assertEquals($paymentEntity['verified'], 1);

        $this->getFailureInVerifyRefund();

        $this->refundAuthorizedPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $refundEntity = $this->getLastEntity('refund', true);

        $refundUpiEntity = $this->getLastEntity('upi', true);

        $refundTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'refunded'                            => $paymentEntity['status'],
            $paymentEntity['id']                  => 'pay_' . $refundUpiEntity['payment_id'],
            'refund'                              => $refundUpiEntity['action'],
            'collect'                             => $refundUpiEntity['type'],
            $paymentEntity['amount']              => $refundUpiEntity['amount'],
            $refundEntity['id']                   => 'rfnd_' . $refundUpiEntity['refund_id'],
            $paymentEntity['amount']              => $refundEntity['amount'],
            'processed'                           => $refundEntity['status'],
            $refundTransactionEntity['id']        => 'txn_' . $refundEntity['transaction_id'],
            $refundTransactionEntity['entity_id'] => $refundEntity['id'],
            $refundTransactionEntity['type']      => 'refund',
            $refundTransactionEntity['amount']    => $refundEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUnexpectedPaymentWithPayerAccountTypeSuccess()
    {
        $this->disableUnexpectedPaymentRefundImmediately();

        $data = $this->testData[__FUNCTION__];

        $response = $this->createUnexpectedPayment($data);

        $this->assertTrue($response['success']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $callbackAmount = explode('|', $data['meRes'])[2];

        // Asserting that conversion to int has not changed amount
        $this->assertEquals(($callbackAmount), $paymentEntity['amount']/100);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            '826115528405'                         => $paymentEntity['reference16'],
            'bank_account'                         => $paymentEntity['reference2'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertNull($paymentEntity['verified']);

        $this->verifyPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($paymentEntity['refund_at']);

        $this->assertEquals($paymentEntity['verified'], 1);

        $this->getFailureInVerifyRefund();

        $this->refundAuthorizedPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $refundEntity = $this->getLastEntity('refund', true);

        $refundUpiEntity = $this->getLastEntity('upi', true);

        $refundTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'refunded'                            => $paymentEntity['status'],
            $paymentEntity['id']                  => 'pay_' . $refundUpiEntity['payment_id'],
            'refund'                              => $refundUpiEntity['action'],
            'collect'                             => $refundUpiEntity['type'],
            $paymentEntity['amount']              => $refundUpiEntity['amount'],
            $refundEntity['id']                   => 'rfnd_' . $refundUpiEntity['refund_id'],
            $paymentEntity['amount']              => $refundEntity['amount'],
            'processed'                           => $refundEntity['status'],
            $refundTransactionEntity['id']        => 'txn_' . $refundEntity['transaction_id'],
            $refundTransactionEntity['entity_id'] => $refundEntity['id'],
            $refundTransactionEntity['type']      => 'refund',
            $refundTransactionEntity['amount']    => $refundEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUnexpectedPaymentWithInvalidPayerAccountTypeSuccess()
    {
        $this->disableUnexpectedPaymentRefundImmediately();

        $data = $this->testData[__FUNCTION__];

        $response = $this->createUnexpectedPayment($data);

        $this->assertTrue($response['success']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $callbackAmount = explode('|', $data['meRes'])[2];

        // Asserting that conversion to int has not changed amount
        $this->assertEquals(($callbackAmount), $paymentEntity['amount']/100);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        // Null assertion in the case of invalid payer account type
        $this->assertNull($paymentEntity['reference2']);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            '826115528405'                         => $paymentEntity['reference16'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertNull($paymentEntity['verified']);

        $this->verifyPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($paymentEntity['refund_at']);

        $this->assertEquals($paymentEntity['verified'], 1);

        $this->getFailureInVerifyRefund();

        $this->refundAuthorizedPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $refundEntity = $this->getLastEntity('refund', true);

        $refundUpiEntity = $this->getLastEntity('upi', true);

        $refundTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'refunded'                            => $paymentEntity['status'],
            $paymentEntity['id']                  => 'pay_' . $refundUpiEntity['payment_id'],
            'refund'                              => $refundUpiEntity['action'],
            'collect'                             => $refundUpiEntity['type'],
            $paymentEntity['amount']              => $refundUpiEntity['amount'],
            $refundEntity['id']                   => 'rfnd_' . $refundUpiEntity['refund_id'],
            $paymentEntity['amount']              => $refundEntity['amount'],
            'processed'                           => $refundEntity['status'],
            $refundTransactionEntity['id']        => 'txn_' . $refundEntity['transaction_id'],
            $refundTransactionEntity['entity_id'] => $refundEntity['id'],
            $refundTransactionEntity['type']      => 'refund',
            $refundTransactionEntity['amount']    => $refundEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }
    public function testUnexpectedPaymentFail()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status']     = 'FAILURE';
            }
        });

        $response = $this->createUnexpectedPayment($data);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($paymentEntity);
    }

    public function testUnexpectedPaymentPending()
    {
        $data = $this->testData['testUnexpectedPaymentFail'];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status']     = 'PENDING';
            }
        });

        $response = $this->createUnexpectedPayment($data);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            // Payment is created for the merchant itself
            'merchant_id'       => '100DemoAccount',
            'method'            => 'upi',
            'amount'            => 37800,
            'status'            => 'failed',
            'amount_authorized' => 0,
            'vpa'               => '7013562166@okhdfcbank',
            'gateway'           => 'upi_mindgate',
            'gateway_captured'  => false,
        ], $payment->toArray());
    }

    public function testUnexpectedPaymentSuccessOnDirectSettlementMerchant()
    {
        // Same GMID is set for both terminal, changing in callback requires significant refactor
        $this->sharedTerminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_mindgate_terminal');

        $data = $this->testData['testUnexpectedPaymentSuccess'];
        $data['pgMerchantId'] = 'shared_merchant';

        $response = $this->createUnexpectedPayment($data);

        $this->assertTrue($response['success']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertNull($paymentEntity['verified']);

        $this->verifyPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['verified'], 1);

        $this->getFailureInVerifyRefund();

        $this->refundAuthorizedPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $refundEntity = $this->getLastEntity('refund', true);

        $refundUpiEntity = $this->getLastEntity('upi', true);

        $refundTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'refunded'                            => $paymentEntity['status'],
            $paymentEntity['id']                  => 'pay_' . $refundUpiEntity['payment_id'],
            'refund'                              => $refundUpiEntity['action'],
            'collect'                             => $refundUpiEntity['type'],
            $paymentEntity['amount']              => $refundUpiEntity['amount'],
            $refundEntity['id']                   => 'rfnd_' . $refundUpiEntity['refund_id'],
            $paymentEntity['amount']              => $refundEntity['amount'],
            'processed'                           => $refundEntity['status'],
            $refundTransactionEntity['id']        => 'txn_' . $refundEntity['transaction_id'],
            $refundTransactionEntity['entity_id'] => $refundEntity['id'],
            $refundTransactionEntity['type']      => 'refund',
            $refundTransactionEntity['amount']    => $refundEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUnexpectedPaymentPendingOnDirectSettlementMerchant()
    {
        // Same GMID is set for both terminal, changing in callback requires significant refactor
        $this->sharedTerminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_mindgate_terminal');

        $data = $this->testData['testDirectSettlementUnexpectedPaymentFail'];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status']  = 'PENDING';
            }
        });

        $response = $this->createUnexpectedPayment($data);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            // Payment is created for the merchant itself
            'merchant_id'       => '10000000000000',
            'method'            => 'upi',
            'amount'            => 37800,
            'status'            => 'failed',
            'amount_authorized' => 0,
            'vpa'               => '7013562166@okhdfcbank',
            'gateway'           => 'upi_mindgate',
            'terminal_id'       => $terminal->getId(),
            'gateway_captured'  => false,
        ], $payment->toArray());
    }

    public function testDuplicateUnexpectedPayment()
    {
        $data = $this->testData['testUnexpectedPaymentSuccess'];

        $response = $this->createUnexpectedPayment($data);

        /*
            Only api success is checked , as the rest of the validation
            is already done in testUnexpectedPaymentSuccess
        */
        $this->assertTrue($response['success']);

        $response = $this->createUnexpectedPayment($data);

        $paymentEntities = $this->getEntities('payment', array(), true);

        $upiEntities = $this->getEntities('upi', array(), true);

        $transactionEntities = $this->getEntities('transaction', array(), true);

        $this->assertEquals(1, $paymentEntities['count']);

        $this->assertEquals(1, $upiEntities['count']);

        $this->assertEquals(1, $transactionEntities['count']);
    }

    public function testUpiQrPaymentProcess()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQr($qrCodeId, 100, ['rrn' => '025403043687']);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('025403043687', $payment['reference16']);

        $this->assertArraySubset([
            'acquirer_data' => [
                'rrn' => '025403043687'
            ]
        ], $payment);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
    }

    public function testUpiQrPaymentProcessWithSavingsPayerAccountType()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQr($qrCodeId, 100, ['rrn' => '025403043688', 'payer_account_type' => 'SAVINGS!NA!NA!NA!NA']);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('025403043688', $payment['reference16']);
        $this->assertEquals('bank_account', $payment['reference2']);

        $this->assertArraySubset([
            'acquirer_data' => [
                'rrn' => '025403043688'
            ]
        ], $payment);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
    }

    public function testUpiQrPaymentProcessWithCreditPayerAccountType()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQr($qrCodeId, 100, ['rrn' => '025403043689', 'payer_account_type' => 'CREDIT!NA!NA!NA!NA']);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('025403043689', $payment['reference16']);
        $this->assertEquals('credit_card', $payment['reference2']);

        $this->assertArraySubset([
            'acquirer_data' => [
                'rrn' => '025403043689'
            ]
        ], $payment);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
    }

    public function testUpiQrPaymentProcessWithInvalidPayerAccountType()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQr($qrCodeId, 100, ['rrn' => '025403043699', 'payer_account_type' => 'INVALID!NA!NA!NA!NA']);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('025403043699', $payment['reference16']);
        $this->assertNull($payment['reference2']);


        $this->assertArraySubset([
            'acquirer_data' => [
                'rrn' => '025403043699'
            ]
        ], $payment);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
    }

    public function testUpiQrFailureStatusCallback()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request = $this->mockServer()->getAsyncFailureCallbackContentForBharatQr($qrCodeId);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('NOK', $response[0]);

        //Qr Entity will not get Created
        $bharatQr = $this->getLastEntity('bharat_qr', true);
        $this->assertNull($bharatQr);

    }

    public function testUpiVerifyAndRefundPayment()
    {
        $this->testUpiQrPaymentProcess();

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1', $payment['verified']);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
    }

    public function testUpiQrUnExpectedPaymentProcess()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQr(str_random(5));

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $payment = $this->getDbLastEntity('payment', 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(100, $payment['amount']);

        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');
        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], false);

        $upi = $this->getDbLastEntity('upi', 'live');
        $this->assertNotNull($upi['payment_id']);
    }

    public function testUpiQrExpectedOnExpectedTerminalPaymentProcess()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->on('live')->edit(
            'terminal',
            $this->bharatQrTerminal['id'],
            [
                'expected' => true
            ]);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQr(str_random(5));

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        $payment = $this->getDbLastEntity('payment', 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);

        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');
        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertTrue($bharatQr['expected']);

        $upi = $this->getDbLastEntity('upi', 'live');
        $this->assertNotNull($upi['payment_id']);
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    public function testIntentTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_intent_tpv_terminal');

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

        $this->assertEquals('UPIMGTEIntTpvl', $payment['terminal_id']);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            [
                'success' => true
            ],
            $response);

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertNotNull($upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        $payment->reload();

        $this->assertEquals('captured', $payment['status']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getDbLastEntity('upi');

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function testInitiateIntentTpvFailedPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_intent_tpv_terminal');

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

        $data = $this->testData[__FUNCTION__];

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';
        $this->payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'intent_tpv')
            {
                $content[1] = 'FAILURE';
                $content[2] = 'Transaction Initialization Failed';
            }
        });

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $payment = $this->getDbLastPayment();
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            'status'                    => 'failed',
            'internal_error_code'       => 'BAD_REQUEST_PAYMENT_FAILED',
        ], $payment->toArray(), true);

        $this->assertArraySubset([
            'payment_id'    => $payment->getId(),
            'action'        => 'authorize',
            'type'          => 'pay',
        ], $upi->toArray(), true);
    }

    public function testEmptyBodyInCallback()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->makeRequestAndCatchException(
            function()
            {
                $this->makeS2SCallbackAndGetContent(['meRes' => null]);
            },
            GatewayErrorException::class,
            "Payment processing failed due to error at bank or wallet gateway\n" .
            "Gateway Error Code: \n" .
            "Gateway Error Desc: Invalid input for decryption");
    }

    public function testDecryptedContentInCallback()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $content = 'NA|1231232112321|231|Your transaction failed';

        $this->makeRequestAndCatchException(
            function() use ($content)
            {
                $this->makeS2SCallbackAndGetContent(['meRes' => $content]);
            },
            GatewayErrorException::class,
            "Payment processing failed due to error at bank or wallet gateway\n" .
            "Gateway Error Code: \n" .
            "Gateway Error Desc: Invalid input for decryption");
    }

    public function testUpiQrPaymentProcessWithTerminalSecret()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $x = $this->fixtures->terminal->edit($this->bharatQrTerminal['id'],['gateway_secure_secret' => '93158d5892188161a259db660ddb1d0a']);

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $request = $this->mockServer()->getAsyncCallbackContentForBharatQrWithTerminalSecret($qrCodeId);

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($bharatQr['expected'], true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
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

        $upi->reload();
        $this->assertEquals($upi->getPaymentId(), $payment['id']);
        $this->assertSame('vishnu@icici', $upi->getVpa());
        $this->assertSame('icici', $upi->provider);
        $this->assertSame('ICIC', $upi->bank);
    }

    public function testPaymentForSingleCharacterVpaHandle()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'a@icici';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, 'created');
    }

    public function testPaymentForMultipleSecrets()
    {
        $metricDriver = $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        $rzpSecret = config('gateway.upi_mindgate.gateway_encryption_key');

        $vasSecret = 'b2950f37dd1df3926749b0e1c50f6063';

        config([ 'gateway.upi_mindgate.gateway_encryption_key' => $vasSecret]);

        $terminalId = '100UPIMindgate';

        $this->fixtures->terminal->edit($terminalId, [
            'gateway_secure_secret' =>  $vasSecret,
        ]);

        $terminal = $this->getDbEntityById('terminal', $terminalId);

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        config([ 'gateway.upi_mindgate.gateway_encryption_key' => $rzpSecret]);

        $upiEntity = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastPayment();

        $callbackMeta = [
            'key'           => hex2bin($vasSecret),
            'merchant_id'   => $terminal['gateway_merchant_id'],
        ];

        $content = $this->mockServer()->getAsyncCallbackContent(
                       $upiEntity->toArray(),
                       $payment->toArray(),
                       $callbackMeta);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $this->assertArraySubset([
            'status'    => 'authorized',
        ], $payment->refresh()->toArray());

        $this->assertArraySubset([
            'payment_id'        => $payment->getId(),
            'action'            => 'authorize',
            'type'              => 'collect',
            'received'          => 1,
            'npci_reference_id' => '910000123456',
            // TODO: Should be PNB
            'bank'              => 'ICIC'
        ], $upiEntity->refresh()->toArray());
    }

    public function testPaymentForMultipleSecretsNoTerminalFound()
    {
        $this->mockMetricDriver(Metric::DOGSTATSD_DRIVER);

        $rzpSecret = config('gateway.upi_mindgate.gateway_encryption_key');

        $vasSecret = 'b2950f37dd1df3926749b0e1c50f6063';

        config([ 'gateway.upi_mindgate.gateway_encryption_key' => $vasSecret]);

        $terminalId = '100UPIMindgate';

        $this->fixtures->terminal->edit($terminalId, [
            'gateway_secure_secret' =>  $vasSecret,
        ]);

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        config([ 'gateway.upi_mindgate.gateway_encryption_key' => $rzpSecret]);

        $upiEntity = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastPayment();

        // Attaching wrong pgMerchantId, to simulate the case.
        $callbackMeta = [
            'key'           => hex2bin($vasSecret),
            'merchant_id'   => 'terminal_not_available',
        ];

        $content = $this->mockServer()->getAsyncCallbackContent(
                       $upiEntity->toArray(),
                       $payment->toArray(),
                       $callbackMeta);

        $this->makeRequestAndCatchException(
            function () use ($content)
            {
                $this->makeS2SCallbackAndGetContent($content);
            },
            RuntimeException::class,
            'No terminal found');
    }

    public function testPaymentMetricForIntentPayment()
    {
        $metricDriver = $this->mockMetricDriver('mock');

        $this->fixtures->create('terminal:shared_upi_mindgate_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';
        $this->payment['_']['app']  = 'com.phonepe.app';

        $response   = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId  = $response['payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity  = $this->getLastEntity('upi_mindgate', true);
        $payment    = $this->getEntityById('payment', $paymentId, true);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[8] = 'user@hdfcbank';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $this->makeS2SCallbackAndGetContent($content);

        $this->assertArraySubset([
            [
                PaymentMetric::LABEL_PAYMENT_GATEWAY    => 'upi_mindgate',
                PaymentMetric::LABEL_UPI_FLOW           => 'intent',
                PaymentMetric::LABEL_UPI_PSP            => 'phonepe',
            ],
        ], $metricDriver->metric(PaymentMetric::PAYMENT_AUTHORIZED, 'histogram'));
    }

    public function testIntentTpvPaymentForDirectSettlementMerchant()
    {
        // creating direct settlement terminal (tpv) for merchant
        $this->fixtures->create('terminal:direct_settlement_upi_mindgate_terminal', ['tpv' => 1]);

        $this->fixtures->merchant->enableTPV();

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
        $this->payment['order_id']  = $order->getPublicId();

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('10DiSeUpMnTmnl', $payment['terminal_id']);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            [
                'success' => true
            ],
            $response);

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        $this->assertEquals('pay', $upi['type']);

        // the payment status should be 'captured' because
        // in case of DS merchant the payment is auto-captured
        // during the callback flow
        $this->assertEquals('captured', $payment['status']);

        $this->fixtures->merchant->disableTPV();
    }

    public function testTpvPaymentForDirectSettlementMerchant()
    {
        // creating direct settlement terminal (tpv) for merchant
        $this->fixtures->create('terminal:direct_settlement_upi_mindgate_terminal', ['tpv' => 1]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->createOrder([
            'amount'         => 50000,
            'currency'       => 'INR',
            'receipt'        => 'rcptid42',
            'method'         => 'upi',
            'bank'           => 'RATN',
            'account_number' => '04030403040304',
        ]);

        $order = $this->getLastEntity('order', true);

        $payment                = $this->getDefaultUpiPaymentArray();
        $payment['amount']      = $order['amount'];
        $payment['order_id']    = $order['id'];

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('10DiSeUpMnTmnl', $payment['terminal_id']);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            [
                'success' => true
            ],
            $response);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        $this->assertEquals('collect', $upi['type']);

        $this->assertEquals('vishnu@icici', $upi['vpa']);

        // the payment status should be 'captured' because
        // in case of DS merchant the payment is auto-captured
        // during the callback flow
        $this->assertEquals('captured', $payment['status']);

        $this->fixtures->merchant->disableTPV();
    }

    public function createTestOrg()
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', [
            'display_name'            => 'HDFC CollectNow',
            'business_name'            => 'HDFC Bank',
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'org_custom_upi_logo',
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        return $org;
    }

    public function testCreateUpiQRVirtualAccountMultipleUsage()
    {

        $this->fixtures->merchant->addFeatures(['virtual_accounts','upiqr_v1_hdfc']);

        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Test Name',
            'org_id' => $org->getId(),
        ]);

        $this->fixtures->create('terminal', [
            'id'                        => '10000000000112',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            // Sample hex for aes encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'unittest@hdfcbank',
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ]);

        $this->ba->privateAuth();

        $this->startTest();

        $qrCode = $this->getLastEntity('qr_code',true);

        $qrString = $qrCode['qr_string'];

        $status  = $qrCode['status'];

        $usage   = $qrCode['usage_type'];

        parse_str(str_replace('upi://pay?', '', $qrString), $params);

        $this->assertTrue(str_starts_with($params['tr'],'STQ'));

        $this->assertEquals('active', $status);

        $this->assertEquals('multiple_use',$usage);
    }

    public function testCreateUpiQRVirtualAccountSingleUsage()
    {

        $this->fixtures->merchant->addFeatures(['virtual_accounts','upiqr_v1_hdfc']);

        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Test Name',
            'org_id' => $org->getId(),
        ]);

        $this->fixtures->create('terminal', [
            'id'                        => '10000000000112',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            // Sample hex for aes encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'unittest@hdfcbank',
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ]);

        $this->ba->privateAuth();

        $this->startTest();

        $qrCode = $this->getLastEntity('qr_code',true);

        $qrString = $qrCode['qr_string'];

        $status  = $qrCode['status'];

        $usage   = $qrCode['usage_type'];

        parse_str(str_replace('upi://pay?', '', $qrString), $params);

        $this->assertFalse(str_starts_with($params['tr'],'STQ'));

        $this->assertEquals('active', $status);

        $this->assertEquals('single_use',$usage);
    }

    public function testCreateUpiQRVirtualAccountWithCloseBy()
    {

        $this->fixtures->merchant->addFeatures(['virtual_accounts','upiqr_v1_hdfc']);

        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Test Name',
            'org_id' => $org->getId(),
        ]);

        $this->fixtures->create('terminal', [
            'id'                        => '10000000000112',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            // Sample hex for aes encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'unittest@hdfcbank',
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ]);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['close_by'] = Carbon::now()->addMinutes(20)->getTimestamp();

        $this->ba->privateAuth();

        $this->startTest($data);

        $qrCode = $this->getLastEntity('qr_code',true);

        $va = $this->getLastEntity('virtual_account',true);

        $closeBy = $va['close_by'];

        $qrString = $qrCode['qr_string'];

        $status  = $qrCode['status'];

        $usage   = $qrCode['usage_type'];

        parse_str(str_replace('upi://pay?', '', $qrString), $params);

        $this->assertNull($closeBy);

        $this->assertNotNull($qrCode['close_by']);

        $this->assertFalse(str_starts_with($params['tr'],'STQ'));

        $this->assertEquals('active', $status);

        $this->assertEquals('single_use',$usage);
    }

    public function testCreateUpiQRVirtualAccount()
    {

        $this->fixtures->merchant->addFeatures(['virtual_accounts','upiqr_v1_hdfc']);

        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Test Name',
            'org_id' => $org->getId(),
        ]);

        $this->fixtures->create('terminal', [
            'id'                        => '10000000000112',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            // Sample hex for aes encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'unittest@hdfcbank',
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ]);

        $this->ba->privateAuth();

        $this->startTest();

        $qrCode = $this->getLastEntity('qr_code',true);

        $qrString = $qrCode['qr_string'];

        $status  = $qrCode['status'];

        $usage   = $qrCode['usage_type'];

        parse_str(str_replace('upi://pay?', '', $qrString), $params);

        $this->assertFalse(str_starts_with($params['tr'],'STQ'));

        $this->assertEquals('active', $status);

        $this->assertEquals('single_use',$usage);
    }

    /**
     * @dataProvider udfTestDataProvider
     */
    public function testUdfFields($inputData, $outputData)
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['enable_addtl_info_upi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['notes'] = $inputData;

        $requestAsserted['authorize'] = false;

        $this->mockServerContentFunction(
            function (& $content, $action = null) use($outputData, & $requestAsserted)
            {
                if($action === 'authorize')
                {
                    $notesValues = $outputData;

                    $udfValues = array_slice($content, 7, 5);

                    $requestAsserted['authorize'] = true;

                    $this->assertEquals($notesValues, $udfValues);
                }});

        $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($requestAsserted['authorize']);
    }

    public function udfTestDataProvider()
    {
        $testData = [
            'Application Id' => '1000230202020',
        ];

        $testCases = [
            ['No Notes Provided' => [], ['NA', 'NA', 'NA', 'NA', 'NA']],
            ['Application Id provided' => $testData,
                array_merge(array_values($testData), ['NA', 'NA', 'NA','NA'])],
        ];


        $testData['extra_field'] = 'randomValue';

        $testCases[2] = ['Extra notes provided'=> $testData,array_merge(array_values(array_slice($testData,0,1)),['NA', 'NA', 'NA','NA'])];

        unset($testData['extra_field']);

        $testData['Application Id'] = 'https:\/\/itcestore.myshopify.com\/services\/ping\/########)!))@)@\/razorpay_cards_upi_netbanking_wallets_\/17376444470';

        $sanitizeValue[] = substr(preg_replace('/[^A-Za-z0-9@_\-=.\/]/', '', $testData['Application Id']),0,60);

        $testCases[3] = ['Length of value more than 60 char and has special characters that are not allowed' => $testData, array_merge($sanitizeValue,['NA', 'NA', 'NA','NA'])];

        return $testCases;
    }

}
