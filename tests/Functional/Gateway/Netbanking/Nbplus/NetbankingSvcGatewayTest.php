<?php

namespace RZP\Tests\Functional\Gateway\File;

use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NetbankingSvcGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingSvcGatewayTestData.php';

        parent::setUp();

        $this->bank = 'SVCB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_svc_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymentEntity);
    }

    public function testS2SPayment()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthAndCapturePayment($this->payment);

        $paymentEntity = $this->getEntityById('payment', $response['id'],true);

        $this->assertTestResponse($paymentEntity, 'testPayment');
    }
}
