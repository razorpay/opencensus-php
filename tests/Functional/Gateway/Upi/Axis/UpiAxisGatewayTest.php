<?php

namespace RZP\Tests\Functional\Gateway\Upi\Axis;

use RZP\Gateway\Upi\Base\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class UpiAxisGatewayTest extends TestCase
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

        $this->assertNotNull($upi['npci_reference_id']);
        $this->assertNotNull($upi['gateway_payment_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        return $payment;
    }

    public function testVerifyPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi = $this->getDBLastEntity('upi');

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $this->assertEquals('authorized', $payment['status']);

        $response = $this->verifyPayment($payment->getPublicId());

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

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
    }

    public function testUpiAmountCap()
    {
        $this->payment['vpa'] = 'vishnu@upi';

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
        $this->payment['vpa'] = 'vishnu@ICICI';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertSame('created', $payment->getStatus());

        $upi = $this->getDbLastEntity('upi');

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

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment->getPublicId());
        });

        $payment->reload();

        $this->assertSame(0, $payment->verified);
        $this->assertNotNull($payment->getVerifyAt());
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

}

