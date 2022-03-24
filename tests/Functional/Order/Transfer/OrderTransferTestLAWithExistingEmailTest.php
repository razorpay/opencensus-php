<?php

namespace RZP\Tests\Functional\Order\Transfers;

use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;


class OrderTransferTestLAWithExistingEmailTest extends OrderTransferTest
{
    protected function initializeTestSetup()
    {

        $this->fixtures->merchant->addFeatures([FeatureConstants::MARKETPLACE]);

        $existingMerchant = $this->fixtures->create('merchant');

        $account = $this->fixtures->create(
            'merchant:marketplace_account',
            [
                MerchantEntity::EMAIL => $existingMerchant[MerchantEntity::EMAIL]
            ]
        );

        $merchantDetailAttributes =  [
            MerchantDetailEntity::MERCHANT_ID                       => $account[MerchantEntity::ID],
            MerchantDetailEntity::CONTACT_EMAIL                     => $account[MerchantEntity::EMAIL],
            MerchantDetailEntity::ACTIVATION_STATUS                 => 'activated',
            MerchantDetailEntity::BANK_DETAILS_VERIFICATION_STATUS  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->linkedAccountId = $account['id'];
    }
}
