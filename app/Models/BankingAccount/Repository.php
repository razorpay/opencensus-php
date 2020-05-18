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
                    ->with(['balance'])
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    public function fetchAccountNumbersByChannel(string $channel, int $limit)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $accountTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $bankingAccountAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($bankingAccountAttrs)
                    ->where($channelColumn, '=', $channel)
                    ->whereIn(Entity::STATUS, Status::getActivatedStatuses())
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($accountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->oldest(Entity::LAST_STATEMENT_ATTEMPT_AT)
                    ->limit($limit)
                    ->get();
    }

    public function getBankingAccountByMerchantIdAndChannel($merchantId, string $channel)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn            = $this->repo->balance->dbColumn(Entity::ID);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $bankingAccountAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($bankingAccountAttrs)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($balanceAccountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($channelColumn, '=', $channel)
                    ->first();
    }

    public function getMerchantIdsByChannel($channel, $limit)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $accountTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $bankingAccountAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($bankingAccountAttrs)
                    ->where($channelColumn, '=', $channel)
                    ->where(Entity::STATUS, '=', Status::ACTIVATED)
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($accountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->oldest(Entity::BALANCE_LAST_FETCHED_AT)
                    ->limit($limit)
                    ->pluck($merchantIdColumn);
    }

    public function fetchByMerchantIdAndAccountType(string $merchantId, string $accountType)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $balanceAccountTypeColumn       = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        return $this->newQuery()
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', $accountType)
                    ->get();
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
