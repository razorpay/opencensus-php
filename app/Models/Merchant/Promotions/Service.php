<?php

namespace RZP\Models\Merchant\Promotions;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function updateCredits(string $id)
    {
        $merchantPromotion = $this->repo->merchant_promotion->findOrFailPublic($id);

        (new Core)->updateCredits($merchantPromotion);
    }
}
