<?php

namespace RZP\Tests\Functional\Gateway\Upi\Hdfc;

use Closure;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiHdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/HdfcGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_hdfc_terminal', [
            'gateway'   => 'upi_hdfc'
        ]);

        $this->gateway = 'upi_hdfc';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    /**
     * Tests the happy-flow of a complete payment
     */
    public function testPayment($status = 'created')
    {
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

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);

        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertNotNull($upiEntity['npci_reference_id']);
        $this->assertNotNull($upiEntity['gateway_payment_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
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

    public function testVerifyPayment()
    {
        // First we test that verification works
        // for a captured payment
        $payment = $this->testPayment();

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    // API Payment = created
    // Gateway = success
    public function testVerificationFailure()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($this->payment);

        $paymentId = $response['payment_id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($paymentId)
        {
            $this->payment = $this->verifyPayment($paymentId);
        });
    }

    /**
     * Create a payment, and reject it so callback
     * returns failure
     */
    public function testCollectRejectedFailure()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'failed@hdfcbank';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($content)
        {
            $response = $this->makeS2SCallbackAndGetContent($content);
        });

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testRefundSuccess()
    {
        $payment = $this->testPayment();

        // Attempt a partial refund
        $this->refundPayment($payment['id'], 10000);
    }

    public function testRefundFailure()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $payment = $this->testPayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $entity = $this->getEntityById('refund', $refund['id'], 'admin');

        $this->assertEquals('failed', $entity['status']);
        $this->assertEquals(false, $entity['gateway_refunded']);

    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }
}
