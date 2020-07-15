<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_comment';

    public function addQueryOrder($query)
    {
        $query->orderBy($this->dbColumn(Entity::ADDED_AT), 'desc');
    }
}
