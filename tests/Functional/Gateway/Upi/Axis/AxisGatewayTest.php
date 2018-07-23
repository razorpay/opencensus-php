<?php

namespace RZP\Tests\Functional\Gateway\Upi\Axis;

use Cache;
use Closure;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Exception\RuntimeException;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;

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

        $upiEntity = $this->getLastEntity('upi', true);
        //
//        $payment = $this->getEntityById('payment', $paymentId, true);

//        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);
//        s($content);
//        $response = $this->makeS2SCallbackAndGetContent($content);
//        s($response);
//        // We should have gotten a successful response
//        $this->assertEquals(['success' => true], $response);
//
//        // The payment should now be authorized
//        $payment = $this->getEntityById('payment', $paymentId, true);
//        $this->assertEquals('authorized', $payment['status']);
//
//        $upiEntity = $this->getLastEntity('upi', true);
//        $this->assertNotNull($upiEntity['npci_reference_id']);
//        $this->assertNotNull($upiEntity['gateway_payment_id']);
//
//        // Add a capture as well, just for completeness sake
//        $this->capturePayment($paymentId, $payment['amount']);
//
//        return $payment;

        return $paymentId;
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];
        $this->assertEquals($expectedStatus, $status);
    }
}