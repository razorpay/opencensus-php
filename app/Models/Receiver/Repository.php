<?php

namespace RZP\Models\Receiver;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\BankAccount;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::RECEIVER;

    const WITH_TRASHED = 'deleted';

    public function getValidVirtualBankAccountFromNumber($accountNumber, $ifsc = null)
    {
        $rId   = $this->dbColumn(Entity::ENTITY_ID);
        $rType = $this->dbColumn(Entity::ENTITY_TYPE);
        $rValid = $this->dbColumn(Entity::VALID);

        $baTable = $this->repo->bank_account->getTableName();

        $baId      = $this->repo->bank_account
                          ->dbColumn(BankAccount\Entity::ID);
        $baNumber  = $this->repo->bank_account
                          ->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);
        $baVirtual = $this->repo->bank_account
                          ->dbColumn(BankAccount\Entity::VIRTUAL);
        $baIfsc    = $this->repo->bank_account
                          ->dbColumn(BankAccount\Entity::IFSC_CODE);

        $query = $this->newQuery()
                      ->join($baTable, $rId, '=', $baId)
                      ->where($rType, Type::BANK_ACCOUNT)
                      ->where($baNumber, '=', $accountNumber)
                      ->where($baVirtual, '=', 1)
                      ->where($rValid, 1);

        if ($ifsc !== null)
        {
            $query->where($baIfsc, 'like', '%'.$ifsc.'%');
        }

        return $query->first();
    }
}
