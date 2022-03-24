<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;


class PaymentMarketplaceRefundLAWithExistingEmailTest extends PaymentMarketplaceRefundTest
{
    protected function initializeTestSetup()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $existingMerchant1 = $this->fixtures->create('merchant');

        $account1 = $this->fixtures->create(
            'merchant:marketplace_account',
            [
                MerchantEntity::EMAIL => $existingMerchant1[MerchantEntity::EMAIL]
            ]
        );

        $attributes1 =  [
            MerchantDetailEntity::MERCHANT_ID                       => $account1[MerchantEntity::ID],
            MerchantDetailEntity::CONTACT_EMAIL                     => $account1[MerchantEntity::EMAIL],
            MerchantDetailEntity::ACTIVATION_STATUS                 => "activated",
            MerchantDetailEntity::BANK_DETAILS_VERIFICATION_STATUS  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $attributes1);

        $existingMerchant2 = $this->fixtures->create('merchant');

        $account2 = $this->fixtures->create(
            'merchant:marketplace_account',
            [
                MerchantEntity::ID      => '10000000000002',
                MerchantEntity::EMAIL   => $existingMerchant2[MerchantEntity::EMAIL]
            ]
        );

        $attributes2 =  [
            MerchantDetailEntity::MERCHANT_ID                       => $account2[MerchantEntity::ID],
            MerchantDetailEntity::CONTACT_EMAIL                     => $account2[MerchantEntity::EMAIL],
            MerchantDetailEntity::ACTIVATION_STATUS                 => "activated",
            MerchantDetailEntity::BANK_DETAILS_VERIFICATION_STATUS  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $attributes2);

        $this->fixtures->merchant->addFeatures([FeatureConstants::MARKETPLACE]);

        $this->ba->privateAuth();
    }
}
