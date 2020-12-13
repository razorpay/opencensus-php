<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\BankAccount;

/**
 * @property BankAccount\Entity $bankAccount
 *
 * Trait HasBankAccount
 * @package RZP\Models\P2p\Base\Traits
 */
trait HasBankAccount
{

    public function associateBankAccount(BankAccount\Entity $handle)
    {
        return $this->bankAccount()->associate($handle);
    }

    public function dissociateBankAccount()
    {
        return $this->bankAccount()->dissociate();
    }

    public function scopeBankAccount(BuilderEx $query, BankAccount\Entity $bankAccount)
    {
        return $query->where(self::BANK, $bankAccount->getId());
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount\Entity::class);
    }

    public function setPublicBankAccountIdAttribute(array & $array)
    {
        $bankAccountId = $this->bankAccount()->getModel()->getSignedIdOrNull($this->getBankAccountId());

        $array[self::BANK_ACCOUNT_ID] = $bankAccountId;
    }

    public function setPublicBankAccountAttribute(array & $array)
    {
        $array[self::BANK_ACCOUNT] = $this->bankAccount ? $this->bankAccount->toArrayPublic() : null;
    }
}
