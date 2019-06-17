<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function getBankingAccountEntity(string $id): Entity
    {
        return $this->repo->banking_account->findOrFailPublic($id);
    }
}
