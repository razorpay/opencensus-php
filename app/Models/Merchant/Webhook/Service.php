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

        if ($webhook === null)
        {
            return;
        }

        $enabledForMerchant = $this->core()->isWebhookActiveAndEnabled($webhook, $event);

        if ($enabledForMerchant === false)
        {
            return;
        }

        $this->core()->prepareAndDispatchWebhook($merchant, $event, $input, $webhook);
    }

    /**
     * @see Core::webhookStorkMigrate()
     *
     * @param array $input
     *
     * @return array
     */
    public function webhookStorkMigrate(array $input): array
    {
        return $this->core()->webhookStorkMigrate($input);
    }

    /**
     * @see Core::webhookDeactivate()
     *
     * @param string $id
     */
    public function webhookDeactivate(string $id)
    {
        $disableWebhookUpdate = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::DISABLE_WEBHOOK_UPDATE,
            'live'
        );

        if (strtolower($disableWebhookUpdate) === 'on')
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_WEBHOOK_UPDATE_DISABLED);
        }
        $this->core()->webhookDeactivate($id);
    }
}
