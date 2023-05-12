<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;

class UpiCcavenueGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    use UpiPaymentTrait;

    /**
     * @var array|mixed
     */
    private mixed $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'ccavenue';

        $this->setMockGatewayTrue();

        $this->createTestTerminal();

    }

    /**
     * This test case verifies that correct cps route is getting picked for aggregator gateway.
     *
     * @return void
     */
    public function testCpsRouteForPayment(): void
    {
        $this->testUpiCollectPaymentCreateSuccess();

        $payment = $this->getDbLastPayment();

        // Assert the cps_route set is 0 for aggregator gateways which are not live on UPS.
        $this->assertEquals(0, $payment->getCpsRoute());
    }

    protected function createTestTerminal()
    {
        $this->terminal = $this->fixtures->create('terminal:ccavenue_upi_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }
}
