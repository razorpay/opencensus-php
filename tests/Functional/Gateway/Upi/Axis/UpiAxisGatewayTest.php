<?php

namespace RZP\Tests\Functional\Gateway\Upi\Axis;

use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class AxisGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;


    /**
     * Payment array
     * @var array
     */
    protected $payment;


    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->gateway = 'upi_axis';

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

        $upi = $this->getDBLastEntity('upi');

        $payment = $this->getDbEntityById('payment', $paymentId);

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('authorized', $payment['status']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['npci_reference_id']);
        $this->assertNotNull($upi['gateway_payment_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testVerifyPayment()
    {
        // First we test that verification works
        // for a captured payment
        $payment = $this->testPayment();

        $paymentId = $payment['id'];

        $this->payment = $this->verifyPayment($paymentId);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['vpa'], 'vijay@axis');

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    protected function getDefaultUpiPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'upi';

        $payment['vpa'] = 'vijay@axis';

        return $payment;
    }

    public function testRefundSuccess()
    {
        $payment = $this->testPayment();

        $paymentId = $payment['id'];
        // Attempt a partial refund
        $this->refundPayment($paymentId, 10000);
    }

    public function testUpiAmountCap()
    {
        $this->payment['vpa'] = 'vijay@axis';

        $payment = $this->payment;

        $payment['amount'] = 2100000;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
    }

    public function testVpaWithCapitalPspValidation($status = 'created')
    {
        $this->payment['vpa'] = 'vijay@AXiS';

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

        $this->assertEquals('vijay@axis', $upiEntity[Entity::VPA]);

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


}

