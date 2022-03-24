<?php

namespace RZP\Tests\Functional\Transfer;

use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;

/**
 * Run TransferTest cases for LinkedAccounts created with an email that is associated with an existing merchant.
 * Refer Jira task PRTS-1030 for more details.
 */
class TransferTestLAWithExistingEmailTest extends TransferTest {

    protected function initializeTestSetup()
    {
        $this->fixtures->merchant->addFeatures([FeatureConstants::MARKETPLACE, FeatureConstants::DIRECT_TRANSFER]);

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

        $this->linkedAccountId = $account[MerchantEntity::ID];
    }

}
