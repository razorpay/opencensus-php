<?php

namespace RZP\Tests\Functional\Gateway\Card\Fss;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Card\Fss\Status;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class BobGatewayTest extends TestCase
{
    use PaymentTrait;

    /**
     * Instance of a terminal from the fixtures
     * @var Terminal
     */
    protected $sharedTerminal;

    /**
     * The payment array
     *
     * @var array
     */
    protected $payment;

    protected $acquirer = 'barb';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/FssGatewayTestData.php';

        parent::setUp();

        $this->createSharedTerminal();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'card_fss';

        $this->payment = $this->getDefaultPaymentArray();

        Carbon::setTestNow();
    }

    public function createSharedTerminal()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => $this->acquirer,
        ]);
    }

    public function testPaymentAuthAndCapture()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($authResponse['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('passed', $payment['two_factor_auth']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testPaymentRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(Status::CAPTURED, $gatewayPayment['status']);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
    }

    /**
     * Verify Refunds are tested.
     */
    public function testVerifyRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->setFailedStatusInReturn();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $fss = $this->getLastEntity('card_fss', true);

        $this->assertEquals($refund['id'], 'rfnd_'.$fss['refund_id']);

        $this->assertEquals(Status::NOT_CAPTURED, $fss['status']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $this->clearMockFunction();

        $this->setVerifyRefundNotCapturedResult();

        $response = $this->retryFailedRefunds();

        Carbon::setTestNow();

        $actualRefund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(2, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);

        $fss = $this->getLastEntity('card_fss', true);

        $this->assertEquals($actualRefund['id'], 'rfnd_'.$fss['refund_id']);
        $this->assertEquals('CAPTURED', $fss['status']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['amt'] = '100';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment();
        });
    }

    public function testPaymentVerifyResponseCaptured()
    {
        if ($this->acquirer !== 'fss')
        {
            $this->markTestSkipped();
        }

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['result'] = 'FAILURE';
            }
        }, $this->gateway);

        $this->makeRequestAndCatchException(function ()
        {
            $this->doAuthPayment($this->payment);
        }, GatewayErrorException::class);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $paymentEntity['status']);

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result'] = 'CAPTURED';
            }
        }, $this->gateway);

        $data = [];
        $data['exception']['class'] = 'RZP\Exception\PaymentVerificationException';
        $data['exception']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_VERIFICATION_FAILED';
        $data['response']['status_code'] = 400;
        $data['response']['content']['error']['code'] = 'BAD_REQUEST_ERROR';

        $this->runRequestResponseFlow($data, function () use ($paymentEntity)
        {
            $this->verifyPayment($paymentEntity['id']);
        });
    }

    protected function getDefaultPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '4111465616335132',
            'name'              => 'Praveen',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        return $payment;
    }
}
