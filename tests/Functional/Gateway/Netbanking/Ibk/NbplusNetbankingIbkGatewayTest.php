<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Ibk;

use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIbkGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingIbkGatewayTestData.php';

        parent::setUp();

        $this->bank = 'IDIB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ibk_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray('IDIB');

    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->doAuthAndCapturePayment($payment);

        $paymententity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymententity);

        $this->assertEquals($this->bank, $paymententity['bank']);

    }

    public function testPaymentFailedVerifySuccess() {}

    public function testPaymentFailedWebhookSuccess() {}

    public function testAuthorizeFailedPayment() {}

    public function testPaymentFailedVerifyFailed() {}

    public function testAuthorizeHandleErrorResponse() {}

    public function testAuthorizeHandleGatewayErrorResponse() {}
}
