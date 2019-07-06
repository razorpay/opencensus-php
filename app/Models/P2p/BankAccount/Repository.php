<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_bank_account';

    public function fetchAllForBank(string $bank)
    {
        return $this->newP2pQuery()
                    ->where(Entity::BANK_ID, $bank)
                    ->get();
    }

    public function findByAccountDetails(string $accountNumber, string $ifsc)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::IFSC, $ifsc)
                    ->latest()
                    ->first();
    }
}
