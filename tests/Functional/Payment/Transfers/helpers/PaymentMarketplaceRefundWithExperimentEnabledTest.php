<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\MocksRazorx;


class PaymentMarketplaceRefundWithExperimentEnabledTest extends PaymentMarketplaceRefundTest
{
    use MocksRazorx;

    protected function initializeTestSetup()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $account1 = $this->fixtures->create('merchant:marketplace_account');

        $attributes1 =  [
            'merchant_id'   => $account1['id'],
            'contact_email' => $account1['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $attributes1);

        $account2 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $attributes2 =  [
            'merchant_id'   => $account2['id'],
            'contact_email' => $account2['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $attributes2);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->privateAuth();

        $this->mockRazorxTreatmentV2(RazorxTreatment::REFUND_AFTER_TRANSFER_REVERSAL, 'on');
    }

    public function testRefundTransferPayment()
    {
        $this->markTestSkipped('negative scenario');
    }

    public function testReverseAllPartialRefundMultipleTransfers()
    {
        $this->markTestSkipped('negative scenario');
    }
}
