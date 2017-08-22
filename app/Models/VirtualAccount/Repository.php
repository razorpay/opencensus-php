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
        Entity::STATUS      => 'sometimes|in:active,closed,paid',
        Entity::CUSTOMER_ID => 'sometimes|string|min:14|max:19',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
    ];

    protected $signedIds = [
        Entity::CUSTOMER_ID,
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

    public function fetchExcessPaidVirtualAccounts()
    {
        $excessCondition = 'amount_received > (amount_expected + amount_reversed)';

        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::PAID)
                      ->whereNotNull(Entity::AMOUNT_EXPECTED)
                      ->whereRaw($excessCondition);

        return $query->get();
    }
}
