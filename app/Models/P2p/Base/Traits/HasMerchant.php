<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Models\Merchant;

trait HasMerchant
{
    protected static function bootHasMerchant()
    {
        self::$doesEntityHasMerchant = true;
    }

    public function setMerchant(Merchant\Entity $merchant)
    {
        $this->setMerchantId($merchant->getId());
    }

    public function scopeMerchant(BuilderEx $ex, Merchant\Entity $merchant)
    {
        $ex->where(self::MERCHANT_ID, $merchant->getId());
    }
}
