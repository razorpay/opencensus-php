<?php

namespace Models\Merchant\Webhook;

use EE\Exception;
use Models\Base;
use Models\Merchant\Webhook;

class Core extends Base\Core
{
    public function createWebhook($merchant, $input)
    {
        $webhooks = $this->getWebhooks($merchant);

        if ($webhooks->count() !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Webhook already created.');
        }

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
