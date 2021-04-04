<?php

namespace RZP\Tests\Functional\Gateway\File;

use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingFederalGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingFederalGatewayTestData.php';

        parent::setUp();

        $this->bank = 'FDRL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_federal_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray('FDRL');

    }

    public function testPaymentWithDisabledMerchant()
    {
//        $now       = Carbon::now()->timestamp;
//        $merchantId = '10000000000001';
//        $partnerAttributes =
//            [
//                'name'          => 'test',
//                'activated'     => 1,
//                'live'          => 1,
//                'activated_at'  => $now,
//                'email'         => 'email.test@test.com',
//                'website'       => 'www.test.com',
//                'billing_label' => 'test Label',
//            ];
//
//        $this->fixtures->merchant->createMerchantWithDetails(Org::RZP_ORG, $merchantId, $partnerAttributes);
//
//        $this->fixtures->edit('terminal', $this->terminal->getId(),
//            [
//                'merchant_id' => '10000000000001'
//            ]);

        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals(3, $paymentEntity['cps_route']);
    }

}
