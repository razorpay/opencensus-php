<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Modules\Migrate\Migrate;
use RZP\Models\Feature\Constants;

class Service extends Base\Service
{
    const IS_DRY_RUN   = 'is_dry_run';

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

    /**
     * An array of mids are passed as an input. Stork has a field
     * alert_email for each webhook entity. For each mid, this
     * route will make sure that the alert_email field of
     * the webhook entity in Stork belonging to the mid, will
     * be populated with `transactions_report_email` from the
     * merchants table in the API.
     *
     * sample input :
     * {
     *  source : ['mids' : ['merchant01', merchant02']],
     *  'is_dry_run' : true/false (not mandatory)
     * }
     *
     * @param array $input
     * @return array
     */
    public function webhookEmailStorkRecon(array $input): array
    {
        $this->trace->info(TraceCode::WEBHOOK_EMAIL_STORK_RECON_REQUEST, $input);

        $isDryRun = $input[self::IS_DRY_RUN] ?? false;

        $source  = new Merchant\Webhook\AlertEmailRecon\MigrateSource();
        $target  = new Merchant\Webhook\AlertEmailRecon\MigrateTarget();
        $migrate = new Migrate($source, $target);

        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, $isDryRun);
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
