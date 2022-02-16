<?php

namespace RZP\Models\BankingAccountStatement\Pool\Rbl;

use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\BankingAccountStatement\Pool\Base\Entity as PoolEntity;

class Entity extends PoolEntity
{
    protected $entity = EntityConstants::BANKING_ACCOUNT_STATEMENT_POOL_RBL;

    protected $table  = Table::BANKING_ACCOUNT_STATEMENT_POOL_RBL;

    public static function scopeWhereInMultiple(BuilderEx $query, array $columns, array $values)
    {
        collect($values)
            ->transform(function ($v) use ($columns) {
                $clause = [];
                foreach ($columns as $index => $column) {
                    $clause[] = [$column, '=', $v[$index]];
                }
                return $clause;
            })->each(function($clause, $index) use ($query) {
                $query->where($clause, null, null,  'or');
            });

        return $query;
    }
}
