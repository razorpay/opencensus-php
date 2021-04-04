<?php

namespace RZP\Tests\Functional\Gateway\File;

use RZP\Models\Terminal\Entity;
use RZP\Models\Feature\Constants;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NetbankingKotakCorpStaticGatewayTest extends StaticCallbackNbplusGatewayTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingKotakCorpStaticGatewayTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = Netbanking::KKBK_C;

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $terminalAttrs = [
            Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal', $terminalAttrs);
    }

    public function testPaymentOnCorporate()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymentEntity);
    }
}
