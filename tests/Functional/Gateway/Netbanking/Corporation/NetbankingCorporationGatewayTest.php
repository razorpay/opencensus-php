<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Corporation;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCorporationGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingCorporationGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_corporation';

        $this->bank = 'CORP';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment
        );

        $this->assertArrayHasKey('bank_payment_id', $payment);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    protected function doNetbankingCorporationAuthAndCapturePayment($order = [])
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'CORP';

        if (empty($order) === false)
        {
            $payment['order_id'] = $order['id'];
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
