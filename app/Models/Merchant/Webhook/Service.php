<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Feature\Constants;

class Service extends Base\Service
{
    public function createWebhook(Merchant\Entity $merchant, array $input)
    {
        if ($this->auth->isProductBanking() and
            $this->merchant->isFeatureEnabled(Constants::BANKING_STORK_MIGRATION))
        {
            return $this->core()->createToStork(
                $merchant,
                $input,
                Merchant\Product::BANKING
            );
        }

        return $this->core()->createWebhook($merchant, $input);
    }

    public function editWebhook(Merchant\Entity $merchant, string $webhookId, array $input)
    {
        if ($this->auth->isProductBanking() and
            $this->merchant->isFeatureEnabled(Constants::BANKING_STORK_MIGRATION))
        {
            return $this->core()->updateToStork($merchant, $webhookId, $input, Merchant\Product::BANKING);
        }

        return $this->core()->editWebhook($merchant, $webhookId, $input);
    }

    public function processWebhook(string $event, array $input)
    {
        $merchant = $this->merchant->isLinkedAccount() ? $this->merchant->parent : $this->merchant;

        $this->core()->prepareAndDispatchWebhook($merchant, $event, $input);
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
        $this->core()->webhookDeactivate($id);
    }

    public function fetchWebhooks($params)
    {
        // return stork setting if banking stork migration feature is enabled
        if ($this->auth->isProductBanking() and
            $this->merchant->isFeatureEnabled(Constants::BANKING_STORK_MIGRATION))
        {
            $webhooks = $this->core()->fetchFromStork($this->merchant, Merchant\Product::BANKING);
        }
        else
        {
            $webhooks = $this->repo->webhook->fetch($params, $this->merchant->getId());
        }

        if ($this->app['basicauth']->isHosted() === true)
        {
            return $webhooks->toArrayHosted();
        }

        return $webhooks->toArrayPublic();
    }
}
