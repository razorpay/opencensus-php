<?php

namespace RZP\Models\Merchant\Partner;

use RZP\Models\Merchant;

class Entity extends Merchant\Entity
{
    public function setPartnerType($partnerType)
    {
        $this->setAttribute(self::PARTNER_TYPE, $partnerType);
    }
}
