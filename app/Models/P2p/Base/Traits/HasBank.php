<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\BankAccount\Bank;

/**
 * @property Bank\Entity $parentBank
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
        return $query->where(self::BANK, $handle->getCode());
    }

    public function parentBank()
    {
        return $this->belongsTo(Bank\Entity::class, self::BANK);
    }

    public function setPublicBankNameAttribute(array & $array)
    {
        $array[self::BANK_NAME] = $this->parentBank->getName();
    }
}
