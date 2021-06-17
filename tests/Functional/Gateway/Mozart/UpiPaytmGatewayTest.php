<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Type;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;

class UpiPaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    use UpiPaymentTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'paytm';

        $this->setMockGatewayTrue();

        $this->createTestTerminal();

    }

    protected function createTestTerminal()
    {
        $this->terminal = $this->fixtures->create('terminal:upi_paytm_terminal', [
            'type'=>    [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '0'
            ]
        ]);
        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }

    /**
     * This test case verifies that correct cps route is getting picked for aggregator gateway.
     *
     * @return void
     */
    public function testCpsRouteForPayment()
    {
        $this->testUpiCollectPaymentCreateSuccess();

        $payment = $this->getDbLastPayment();

        // Assert the cps_route set is 0 for aggregator gateways which are not live on 
        // UPI payment service 
        $this->assertEquals(0, $payment->getCpsRoute());
    }
}
