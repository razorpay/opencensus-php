<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\BankAccount\Bank;

/**
 * @property Bank\Entity $bank
 *
 * Trait HasBank
 * @package RZP\Models\P2p\Base\Traits
 */
trait HasBank
{

    public function associateBank(Bank\Entity $bank)
    {
        return $this->parentBank()->associate($bank);
    }

    public function scopeBank(BuilderEx $query, Bank\Entity $bank)
    {
        return $query->where(self::BANK_ID, $bank->getId());
    }

    public function bank()
    {
        return $this->belongsTo(Bank\Entity::class, self::BANK_ID);
    }

    public function setPublicBankAttribute(array & $array)
    {
        if (empty($this->getAttribute(self::BANK_ID)) or
           (empty($this->bank) === true))
        {
            return;
        }

        $array[self::BANK] = [
            Bank\Entity::NAME       => $this->bank->getName(),
            Bank\Entity::IFSC       => $this->bank->getIfsc(),
            Bank\Entity::UPI_FORMAT => $this->bank->getUpiFormat()
        ];
    }
}
