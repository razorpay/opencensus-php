<?php

namespace RZP\Models\Device;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'device';

    public function findByVerificationTokenAndMerchant(string $verificationToken, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::VERIFICATION_TOKEN, '=', $verificationToken)
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->firstOrFail();
    }
}
