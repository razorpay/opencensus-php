<?php

namespace RZP\Models\VirtualAccount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant\Entity as Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT;

    const WITH_TRASHED = 'deleted';

    public function getActiveVirtualAccountFromBankAccountId(string $bankAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::BANK_ACCOUNT_ID, '=', $bankAccountId)
                    ->first();
    }

    public function findByIdAndMerchantIdWithRelations(
        string $id,
        Merchant $merchant,
        array $relations = [],
        array $columns = ['*'])
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId());

        if (empty($relations) === false)
        {
            $query->with($relations);
        }

        return $query->findOrFailPublic($id, $columns);
    }
}
