<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\RazorxTreatment;

class Service extends Base\Service
{
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
        $disableWebhookUpdate = $this->app->razorx->getTreatment(
            'any',
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
