<?php

namespace RZP\Models\VirtualAccount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant\Entity as Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT;

    const WITH_TRASHED = 'deleted';

    protected $entityFetchParamRules = [
        Entity::STATUS     => 'sometimes|string|in:active,closed,paid',
    ];

    public function getActiveVirtualAccountFromBankAccountId(string $bankAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::BANK_ACCOUNT_ID, '=', $bankAccountId)
                    ->first();
    }

    public function findByIdAndMerchantWithRelations(
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

    public function findActiveByDescriptorAndMerchant(
        string $descriptor,
        Merchant $merchant)
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId())
                      ->where(Entity::STATUS, '=', Status::ACTIVE)
                      ->where(Entity::DESCRIPTOR, '=', $descriptor);

        return $query->get();
    }
}
