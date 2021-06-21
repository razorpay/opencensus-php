<?php


namespace RZP\Models\Merchant\Escalations\Actions\Handlers;


use RZP\Models\Merchant\Escalations\Actions\Constants;
use RZP\Models\Merchant\Escalations\Actions\Entity;

class DisablePaymentsHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        $merchant->deactivate();
        $this->repo->saveOrFail($merchant);
    }
}
