<?php

namespace RZP\Tests\Functional\Gateway\Upi\Hdfc;

use Closure;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        // $this->markTestSkipped();
        $this->testDataFilePath = __DIR__.'/HdfcGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_terminal', [
            'gateway'   =>  'upi_hdfc'
        ]);

        $this->gateway = 'upi_hdfc';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        $this->markTestSkipped();
        unset($this->payment['description']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentViaRedirection()
    {
        // $this->markTestSkipped();
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
    public function testInvalidVPA()
    {

        $payment = $this->getDefaultUpiPaymentArray();

        // Emails are not VPAs
        $payment['vpa'] = 'nemo@razorpay@com';

        $data = $this->testData['testInvalidVPA'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testSingleWordVPA()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        $payment['vpa'] = 's@dcb';

        $this->doAuthPaymentViaAjaxRoute($payment);
    }

    public function testInvalidResponsePayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $this->mockServerContentFunction(function (& $content)
        {
            $content = null;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testPaymentWithS2S($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        if ($assert)
        {
            $this->assertEquals($response, ['success' => true]);
        }

        $payment = $this->getEntityById('payment', $paymentId, true);

        return $payment;
    }

    /**
     * TODO: Move this test to Payment Test
     * But we can only do that once we have sharp support for async
     */
    public function testAsyncPaymentAutoCaptured()
    {
        $this->ba->privateAuth();

        $res = $this->startTest($this->testData['testCreateAutoCaptureOrder']);

        $this->payment['order_id'] = $res['id'];

        $payment = $this->testPaymentWithS2S(true);

        $this->assertEquals('captured', $payment['status']);

        $response = $this->getPaymentStatus($payment['id']);

        $this->assertEquals([
            'razorpay_payment_id',
            'razorpay_order_id',
            'razorpay_signature'],
        array_keys($response));
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }
}
