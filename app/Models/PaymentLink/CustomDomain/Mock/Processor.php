<?php

namespace RZP\Models\PaymentLink\CustomDomain\Mock;

use RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\IWebhookHandler;

class Processor implements IWebhookHandler
{
    /**
     * @return void
     */
    public function handle(): void
    {
        // in mock so do nothing
    }

    public function process(array $data): IWebhookHandler
    {
        // TODO: Implement process() method.
    }
}
