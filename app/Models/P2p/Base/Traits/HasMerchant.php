<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\Merchant;

/**
 * @property Merchant\Entity $merchant
 *
 * Trait HasMerchant
 * @package RZP\Models\P2p\Base\Traits
 */
trait HasMerchant
{
    public function hasMerchant(): bool
    {
        return true;
    }

    public function associateMerchant(Merchant\Entity $merchant)
    {
        return $this->merchant()->associate($merchant);
    }

    public function scopeMerchant(BuilderEx $query, Merchant\Entity $merchant)
    {
        return $query->where(self::MERCHANT_ID, $merchant->getId());
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
