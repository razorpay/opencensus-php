<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    public function processWebhook(String $event, array $input)
    {
        $merchant = $this->merchant;

        $merchantId = $merchant->getId();

        Merchant\Entity::verifyIdAndStripSign($merchantId);

        /*
        * If the merchant is a linked account, use the parent merchant.
        */
        if ($merchant->isLinkedAccount() === true)
        {
            $merchant = $merchant->parent;
        }

        $webhook = $this->repo->webhook->findByMerchant($merchant);

        $enabledForMerchant = $this->core()->isWebhookActiveAndEnabled($webhook,$event);

        if ($enabledForMerchant === false)
        {
            return;
        }

        $this->core()->prepareAndDispatchWebhook($merchant, $event, $input, $webhook);
    }
}
