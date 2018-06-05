<?php

namespace RZP\Models\Merchant\Partner;

use RZP\Models\Merchant;

class Core extends Merchant\Core
{
    public function markAsPartner(string $merchantId, string $partnerType)
    {
        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        (new Validator)->validatePartnerType($partnerType);

        $merchant->setPartnerType($partnerType);

        $this->repo->saveOrFail($merchant);
    }
}
