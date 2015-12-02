<?php

namespace Models\Merchant\Webhook;

use Models\Base;
use Models\Merchant\Webhook;

class Core extends Base\Core
{
    public function createWebhook($merchant, $input)
    {
        $webhook = (new Webhook\Entity)->build($input);

        $webhook->merchant()->associate($merchant);

        (new Webhook\Repository)->saveOrFail($webhook);

        return $webhook;
    }

    public function editWebhook($merchant, $webhookId, $input)
    {
        $repo = new Webhook\Repository;

        $webhook = $repo->findByIdAndMerchantId($webhookId, $merchant->getId());

        $webhook->edit($input);

        $repo->saveOrFail($webhook);

        return $webhook;
    }

    public function getWebhooks($merchant)
    {
        $webhooks = (new Webhook\Repository)->findByMerchant($merchant);

        return $webhooks;
    }
}
