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
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HulkGatewayTest extends TestCase
{
    use PaymentTrait;

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
        $payment = $this->fixtures->create('payment:upi_captured', ['gateway' => 'upi_hulk', 'terminal_id' => $this->sharedTerminal->getId()]);

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
}
