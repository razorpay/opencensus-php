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

    public function associateBank(Bank\Entity $handle)
    {
        return $this->parentBank()->associate($handle);
    }

    public function scopeBank(BuilderEx $query, Bank\Entity $handle)
    {
        return $query->where(self::BANK_ID, $handle->getCode());
    }

    public function bank()
    {
        return $this->belongsTo(Bank\Entity::class, self::BANK_ID);
    }

    public function setPublicBankAttribute(array & $array)
    {
        if (empty($array[self::BANK_ID]) === true)
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
