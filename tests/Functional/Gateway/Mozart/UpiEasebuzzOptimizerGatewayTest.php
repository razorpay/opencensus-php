<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiOptimizerRazorpayGatewayTest extends TestCase
{
    use UpiPaymentTrait;

    use PaymentTrait;

    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'easebuzz_optimizer';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->terminal = $this->fixtures->create('terminal:easebuzz_optimizer_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }
}
