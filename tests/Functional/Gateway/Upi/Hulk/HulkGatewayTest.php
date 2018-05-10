<?php

namespace RZP\Tests\Functional\Gateway\Upi\Hulk;

use Cache;
use Closure;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Exception\RuntimeException;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HulkGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/HulkGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->gateway = 'upi_hulk';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        unset($this->payment['description']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
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

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $payment = $this->authorizedFailedPayment($payment['id']);

        $this->assertNull($payment['verified']);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->mockServerRequestFunction(
            function($content, $action)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('expected_push', $content['type']);
                }
            });

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $expectedUrl = 'upi://pay?pa=testmerchant@razor&pn=TestMerchant&'.
                       'tr=p2p_A11zpSL1413XHi&tn=TestMerchant&am=500&cu=INR&mc=5411';

        $this->assertSame($expectedUrl, $response['data']['intent_url']);

        $upiEntity = $this->getDbLastEntity('upi');
        $payment = $this->getDbLastPayment('payment');

        $this->assertSame('created', $payment->getStatus());
        $this->assertEquals('push', $upiEntity['type']);
        $this->assertEquals('1UPIInHulkTrml', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $payment = $this->authorizedFailedPayment($paymentId);

        $this->assertNull($payment['verified']);
        $this->assertEquals($payment['status'], 'authorized');

        $upiEntities = $this->getDbEntities('upi');
    }

    public function testIntentTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_hulk_tpv_terminal');

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
        $this->payment['order_id'] = $order->getPublicId();
        $this->payment['bank'] = $order->getBank();

        $this->mockServerRequestFunction(
            function($content, $action) use ($order)
            {
                if ($action === 'authorize')
                {
                    $this->assertSame('expected_push', $content['type']);
                    $this->assertSame($order->getAccountNumber(), $content['caller_account_number']);
                }
            });

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertEquals('1UPITpvHulkTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getDbLastEntity('upi');

        $this->assertEquals('push', $gatewayEntity['type']);
    }
}
