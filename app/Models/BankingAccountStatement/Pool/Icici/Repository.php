<?php

namespace RZP\Models\BankingAccountStatement\Pool\Icici;

use RZP\Constants;
use RZP\Models\BankingAccountStatement\Pool\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNT_STATEMENT_POOL_ICICI;

    // This is another approach to do inserts. Keeping it here for future reference if required.
    //public function bulkInsert($records)
    //{
    //    Entity::insert($records);
    //}
}
