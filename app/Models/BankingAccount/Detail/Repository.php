<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Models\Base;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Detail\Entity;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_detail';

    public function getAccountDetailsForBankingAccount(BankingAccount\Entity $bankingAccount)
    {
        return $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccount->getId())
                    ->get();
    }
}
