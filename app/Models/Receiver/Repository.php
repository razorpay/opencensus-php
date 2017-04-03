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
        $rId   = $this->getAttributeWithTableName(Entity::ENTITY_ID);
        $rType = $this->getAttributeWithTableName(Entity::ENTITY_TYPE);
        $rValid = $this->getAttributeWithTableName(Entity::VALID);

        $baTable = $this->manager->bank_account->getTableName();

        $baId      = $this->manager->bank_account
                          ->getAttributeWithTableName(BankAccount\Entity::ID);
        $baNumber  = $this->manager->bank_account
                          ->getAttributeWithTableName(BankAccount\Entity::ACCOUNT_NUMBER);
        $baVirtual = $this->manager->bank_account
                          ->getAttributeWithTableName(BankAccount\Entity::VIRTUAL);
        $baIfsc    = $this->manager->bank_account
                          ->getAttributeWithTableName(BankAccount\Entity::IFSC_CODE);

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
