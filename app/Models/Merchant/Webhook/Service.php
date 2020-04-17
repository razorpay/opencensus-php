<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;

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
        $this->core()->webhookDeactivate($id);
    }
}
