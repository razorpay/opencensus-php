<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\FundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Repository extends Base\Repository
{
    protected $entity = 'payout_link';

    public function getActiveFundAccountsByPayoutLinkIdAndMerchant(string $payoutLinkId, MerchantEntity $merchant)
    {
        return $this->findByPublicIdAndMerchant($payoutLinkId, $merchant)
                    ->contact
                    ->fundAccounts->where(FundAccount\Entity::ACTIVE, '=', '1');
    }
}
