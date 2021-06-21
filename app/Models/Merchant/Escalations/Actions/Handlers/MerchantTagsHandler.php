<?php


namespace RZP\Models\Merchant\Escalations\Actions\Handlers;


use RZP\Models\Merchant;
use RZP\Models\Merchant\Escalations\Actions\Entity;

class MerchantTagsHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        (new Merchant\Core)->appendTag($merchant, 'transacted_before_l2');
    }
}
