<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'transfer';

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::SOURCE_ID           => 'sometimes|alpha_num|min:14',
        Entity::TO_ID               => 'sometimes|alpha_num|min:14'
    ];

    public function fetchByAccountIdAndMerchant(string $accountId, Merchant\Entity $marketplace) : Entity
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, SourceType::PAYMENT)
                    ->where(Entity::TO_TYPE, 'merchant')
                    ->where(Entity::TO_ID, $accountId)
                    ->where(Entity::MERCHANT_ID, $marketplace->getId())
                    ->firstOrFail();
    }
}
