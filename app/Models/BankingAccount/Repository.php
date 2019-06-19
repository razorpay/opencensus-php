<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function findByBankReferenceAndChannel(string $bankReference = null, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_REFERENCE_NUMBER, '=', $bankReference)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->firstOrFail();
    }
}
