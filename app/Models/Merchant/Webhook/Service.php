<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\Feature\Constants;

class Service extends Base\Service
{
    public function createWebhook(Merchant\Entity $merchant, array $input)
    {
        if ($this->auth->isProductBanking())
        {
            return $this->core()->createToStork(
                $merchant,
                $input,
                Product::BANKING
            );
        }

        return $this->core()->createWebhook($merchant, $input);
    }

    public function editWebhook(Merchant\Entity $merchant, string $webhookId, array $input)
    {
        if ($this->auth->isProductBanking())
        {
            return $this->core()->updateToStork($merchant, $webhookId, $input, Product::BANKING);
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
     * @see Core::webhookStorkRecon()
     *
     * @param array $input
     *
     * @return array
     */
    public function webhookStorkRecon(array $input): array
    {
        return $this->core()->webhookStorkRecon($input);
    }

    
    public function webhookStorkCreateBankingBulk(array $input): array
    {
        return $this->core()->webhookStorkCreateBankingBulk($input);
    }

    /**
     * @see Core::webhookDeactivate()
     *
     * @param string $id
     * @param array  $input
     */
    public function webhookDeactivate(string $id, array $input)
    {
        $this->core()->webhookDeactivate($id, $input);
    }

    public function fetchWebhooks($params)
    {
        // return stork setting if banking stork migration feature is enabled
        if ($this->auth->isProductBanking())
        {
            $webhooks = $this->core()->fetchFromStork($this->merchant, Product::BANKING);
        }
        else
        {
            $webhooks = $this->repo->webhook->fetch($params, $this->merchant->getId());
        }

        if (($this->app['basicauth']->isHosted() === true) ||
            ($this->app['basicauth']->isExpress() === true))
        {
            return $webhooks->toArrayHosted();
        }

        return $webhooks->toArrayPublic();
    }
}
