<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin\Entity as AdminEntity;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    protected $expands = [
        Entity::BANKING_ACCOUNT_DETAILS,
        Entity::MERCHANT
    ];

    public function getFromBalanceId(string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->first();
    }

    public function findByAccountNumberAndChannel(string $accountNumber, string $channel)
    {
        return $this->whereAccountNumberAndChannelAre($accountNumber, $channel)
                    ->firstOrFail();
    }

    public function findByAccountNumberAndChannelPublic(string $accountNumber, string $channel)
    {
        return $this->whereAccountNumberAndChannelAre($accountNumber, $channel)
                    ->firstOrFailPublic();
    }

    public function whereAccountNumberAndChannelAre($accountNumber, $channel)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->where(Entity::CHANNEL, '=', $channel);
    }

    /**
     * @param string      $channel
     * @param string|null $bankReference
     *
     * @return Entity
     */
    public function findByBankReferenceAndChannel(string $channel, string $bankReference = null): Entity
    {
        return $this->newQuery()
                    ->where(Entity::BANK_REFERENCE_NUMBER, '=', $bankReference)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->firstOrFail();
    }

    public function getBankingAccountOfMerchant(Merchant\Entity $merchant, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->first();
    }

    public function getLatestInsertedBankingAccountEntity(string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->whereNotNull(Entity::BANK_REFERENCE_NUMBER)
                    ->latest(Entity::CREATED_AT)
                    ->first();
    }

    public function getBankingAccountsWithBalance($merchantId)
    {
        return $this->newQuery()
                    ->with(['balance:id,balance,currency'])
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    public function getBankingAccountByMerchantIdAndChannel($merchantId, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->first();
    }

    public function getMerchantIdsByChannel($channel, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->where(Entity::STATUS, '=', Status::ACTIVATED)
                    ->orderBy(Entity::BALANCE_LAST_FETCHED_AT, 'asc')
                    ->limit($limit)
                    ->pluck(Entity::MERCHANT_ID);
    }

    // To be used for x test mode migration purpose only
    public function fetchBankingAccounts(array $merchantIds, int $skip, int $limit)
    {
         $query = $this->newQuery()
                       ->where(Entity::CHANNEL, '=', Channel::YESBANK)
                       ->orderBy(Entity::CREATED_AT)
                       ->skip($skip)
                       ->take($limit);

        if (empty($merchantIds) === false)
        {
            $query->whereIn(Entity::MERCHANT_ID, $merchantIds);
        }

         return $query->get();
    }

    public function addQueryParamReviewerId($query, $params)
    {
        AdminEntity::verifyIdAndStripSign($params[Entity::REVIEWER_ID]);

        return $query->whereExists(function ($q) use ($params) {
            $q->select('admin_id')
                ->from(Table::ADMIN_AUDIT_MAP)
                ->where('admin_id', '=', $params[Entity::REVIEWER_ID])
                ->where(Entity::AUDITOR_TYPE,'=','reviewer')
                ->where('entity_type','=','banking_account')
                ->whereRaw(Table::BANKING_ACCOUNT.'.'.Entity::ID.' = '.Table::ADMIN_AUDIT_MAP.'.'.Entity::ENTITY_ID);
        });
    }
}
