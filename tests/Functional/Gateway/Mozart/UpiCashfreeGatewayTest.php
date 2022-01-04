<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiCashfreeGatewayTest extends TestCase
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

        $this->gateway = 'cashfree';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->terminal = $this->fixtures->create('terminal:cashfree_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }

    public function testSendAndStoreGatewayError()
    {
        $this->fixtures->merchant->addFeatures(['raas', 'expose_gateway_errors']);
        $this->fixtures->edit('terminal', $this->terminal['id'], ['procurer' => 'merchant']);

        $this->testUpiPaymentCallbackFailed();

        $paymentDbEntry = $this->getDbLastPayment();

        $payment = $this->fetchPayment($paymentDbEntry['public_id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertArrayHasKey('gateway_data', $payment);
        $this->assertEquals('U30', $payment['gateway_data']['error_code']);
    }
}
