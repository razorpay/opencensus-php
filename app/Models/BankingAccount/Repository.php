<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Models\Merchant;

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

    public function fetchAccountNumberByChannel($channel, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->orderBy(Entity::LAST_STATEMENT_ATTEMPT_AT, 'asc')
                    ->limit($limit)
                    ->pluck(Entity::ACCOUNT_NUMBER);
    }
}
