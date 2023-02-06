<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiBilldeskOptimizerGatewayTest extends TestCase
{
    use UpiPaymentTrait;

    use PaymentTrait;

    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = "mozart";

        $this->setMockGatewayTrue();

        $this->gateway = "upi_mozart";

        $this->setMockGatewayTrue();

        $this->gateway = "billdesk_optimizer";

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->terminal = $this->fixtures->create(
            "terminal:billdesk_optimizer_terminal"
        );

        $this->fixtures->merchant->enableMethod(
            Account::TEST_ACCOUNT,
            Method::UPI
        );

        $this->fixtures->merchant->activate();
    }
}
