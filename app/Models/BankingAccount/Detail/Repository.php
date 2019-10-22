<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Models\Base;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Detail\Entity;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_detail';

    public function getDetailsForKeyAndBankingAccount(BankingAccount\Entity $bankingAccount, string $key)
    {
        return $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccount->getId())
                    ->where(Entity::GATEWAY_KEY, '=', $key)
                    ->first();
    }
}
