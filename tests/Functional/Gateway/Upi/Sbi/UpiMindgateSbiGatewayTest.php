<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

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

        $this->gateway = 'upi_mindgate_sbi';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
    }
}