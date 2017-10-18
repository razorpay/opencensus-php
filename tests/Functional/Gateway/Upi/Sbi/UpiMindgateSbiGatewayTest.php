<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

use RZP\Models\Payment\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiMindgateSbiGatewayTest extends TestCase
{
    use PaymentTrait;

    /**
     * @var Payment variable
     */
    protected $payment;

    /**
     * @var Upi Mindgate Sbi terminal
     */
    protected $sharedTerminal;

    public function setUp()
    {
//        $this->testDataFilePath = __DIR__.'/MindgateGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->gateway = 'upi_sbi';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Coproto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, Status::CREATED);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        // TODO: Add methods for the code below
        $content = $this->mockServer()->makeAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // TODO: Complete flow
    }

    protected function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response['status']);
    }
}