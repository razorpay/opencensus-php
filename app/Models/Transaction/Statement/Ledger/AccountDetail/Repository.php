<?php

namespace RZP\Models\Transaction\Statement\Ledger\AccountDetail;

use Db;
use RZP\Models\Base;
use Illuminate\Database\Query\JoinClause;
use RZP\Models\Transaction\Statement\Ledger\Account;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement
 */
class Repository extends Base\Repository
{
    /**
     * {@inheritDoc}
     */
    protected $entity = 'account_detail';

    /**
     * select * from `ledger`.`account_details`
     *          inner join `ledger`.`accounts`
     *              on `ledger`.`accounts`.`id` = `ledger`.`account_details`.`account_id`
     *          where `ledger`.`account_details`.`merchant_id` = '10000000000000' and
     *                JSON_CONTAINS( ledger.account_details.entities, '["merchant_va"]', '$.fund_account_type');
     *
     * @param string $merchantId
     * @param string $fundAccountType
     * @param string|null $connectionType
     * @return array
     */
    public function fetchBalanceByFundAccountType(string $merchantId, string $fundAccountType, string $connectionType = null) :array
    {
        $connection = $this->getConnectionFromType($connectionType);
        $query = $this->newQueryWithConnection($connection);

        $accountTable = $this->repo->ledger_account->getTableName();
        $accountDetailMerchantIdColumn = $this->repo->account_detail->dbColumn(Entity::MERCHANT_ID);
        $accountDetailEntitiesColumn = $this->repo->account_detail->dbColumn(Entity::ENTITIES);

        $query->select('*');

        $query->join(
            $accountTable,
            function(JoinClause $join)
            {
                $accountDetailsAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);
                $accountIdColumn = $this->repo->ledger_account->dbColumn(Account\Entity::ID);

                $join->on($accountIdColumn, $accountDetailsAccountIdColumn);
            });

        $query->where($accountDetailMerchantIdColumn, $merchantId);
        $query->whereRaw('JSON_CONTAINS( ' . $accountDetailEntitiesColumn . ', \'["' . $fundAccountType . '"]\', \'$.fund_account_type\')');

        return $query->get()
                     ->toArray();
    }

    /**
     * select account_id from `ledger`.`account_details`
     *          where `ledger`.`account_details`.`account_name` = 'Merchant Balance Account - <MID>';
     *
     * @param string $accountName
     * @param string|null $connectionType
     * @return string
     */
    public function fetchAccountIDByAccountName(string $accountName, string $connectionType = null) :string
    {
        $connection = $this->getConnectionFromType($connectionType);
        $query = $this->newQueryWithConnection($connection);

        $accountTable = $this->repo->ledger_account->getTableName();
        $accountDetailAccountNameColumn = $this->repo->account_detail->dbColumn(Entity::ACCOUNT_NAME);
        $accountIDColumn = $this->repo->account_detail->dbColumn(Entity::ACCOUNT_ID);

        $query->select($accountIDColumn);
        $query->where($accountDetailAccountNameColumn, $accountName);

        return $query->get()->first()[Entity::ACCOUNT_ID];
    }
}
