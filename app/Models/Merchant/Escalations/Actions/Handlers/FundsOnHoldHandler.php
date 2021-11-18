<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Models\Merchant\Escalations\Actions\Entity;

class FundsOnHoldHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        $merchant->setHoldFunds(true);
        $merchant->setHoldFundsReason('GMV hard limit breached for the merchant.');
        $this->repo->merchant->saveOrFail($merchant);
    }
}
