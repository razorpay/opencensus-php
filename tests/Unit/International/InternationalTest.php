<?php

namespace RZP\Tests\Unit\International;

use RZP\Models\Payment\Processor\Authorize;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment;
use RZP\Http\Route;

class InternationalTest extends TestCase{

    use Authorize;

    protected $app;

    protected $route;

    protected $payment;

    public function setupInternationalData(){

        $this->payment = new \RZP\Models\Payment\Entity;

        $this-> trace = \Mockery::mock('RZP\Trace\Trace');
        $this-> trace->shouldReceive("info");

        $this->payment->setMethod(Payment\Method::NETBANKING);

        $this->route = $this->getMockBuilder(Route::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["getCurrentRouteName"])
            ->getMock();

        $this->route->expects($this->once())->method('getCurrentRouteName')->willReturn('payment_update_and_redirect');

        $inputDetails =[
            'gateway_input'     => [
                'selected_terminals_ids'=> [
                    '123456',
                    '213456'
                ]
            ]
        ];
        return $inputDetails;
    }
    /**
     * Test if the terminal call is made second time in case of DCC applied
     */
    public function testSecondCallToTerminal()
    {

        $inputDetails = $this->setupInternationalData();

        $input=[
            'dcc_currency'          =>  'USD',
            'currency_request_id'   =>  '12345'
        ];

        $this->ValidateAndProcessDccInput($this->payment,$input,$inputDetails);

        $this->assertTrue(empty($inputDetails['gateway_input']['selected_terminals_ids']));
    }

    public function testSecondCallToTerminalFailed()
    {

        $inputDetails = $this->setupInternationalData();

        $input=[
            'currency_request_id'   =>  '12345'
        ];

        $this->ValidateAndProcessDccInput($this->payment,$input,$inputDetails);

        $this->assertFalse(empty($inputDetails['gateway_input']['selected_terminals_ids']));
    }
}
