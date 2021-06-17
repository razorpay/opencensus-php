<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiPaymentServiceTest extends TestCase {
    
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        // We have Airtel Gateway Enabled for Service
        $this->terminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);
        
        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPaymentCreateSuccess(){
        
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::REFUND_AT       => null,
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
        ], $payment->toArray());

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    public function testPaymentFailureMockDisabled()
    {
        $this->app['config']->set(['applications.upi_payment_service.mock' => false]);

        $this->makeRequestAndCatchException(
            function() {
                $this->testPaymentCreateSuccess();
            },
            Exception\LogicException::class,
            'Action is not implemented for UPI payment service');
    }
}
