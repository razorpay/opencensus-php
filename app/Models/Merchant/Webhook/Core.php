<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Webhook;
use Crypt;

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

        $this->repo->saveOrFail($webhook);

        return $webhook;
    }

    public function editWebhook($merchant, $webhookId, $input)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchant($webhookId, $merchant);

        $webhook->edit($input);

        $this->repo->saveOrFail($webhook);

        return $webhook;
    }

    public function getWebhooks($merchant)
    {
        return $this->repo->webhook->findMultipleByMerchant($merchant);
    }
}
