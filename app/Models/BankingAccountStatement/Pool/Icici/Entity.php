<?php

namespace RZP\Models\BankingAccountStatement\Pool\Icici;

use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\BankingAccountStatement\Pool\Base\Entity as BaseEntity;

class Entity extends BaseEntity
{
    protected $entity = EntityConstants::BANKING_ACCOUNT_STATEMENT_POOL_ICICI;

    protected $table  = Table::BANKING_ACCOUNT_STATEMENT_POOL_ICICI;
}
