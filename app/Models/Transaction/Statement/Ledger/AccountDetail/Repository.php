<?php

namespace RZP\Models\Transaction\Statement\Ledger\AccountDetail;

use Db;
use Illuminate\Database\Query\JoinClause;

use RZP\Models\Base;
use RZP\Constants\Environment;
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
     *                JSON_CONTAINS( ledger.account_details.entities, '["bacc_01234567890123"]', '$.banking_account_id');
     *
     * @param string $merchantId
     * @param string $bankingAccountId
     * @param string|null $connectionType
     * @return array
     */
    public function fetchBalance(string $merchantId, string $bankingAccountId, string $connectionType = null) :array
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
        $query->whereRaw('JSON_CONTAINS( ' . $accountDetailEntitiesColumn . ', \'["' . $bankingAccountId . '"]\', \'$.banking_account_id\')');

        return $query->get()
                     ->toArray();
    }
}
