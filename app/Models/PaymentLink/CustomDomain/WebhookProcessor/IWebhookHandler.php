<?php

namespace RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

interface IWebhookHandler
{
    /**
     * @return void
     */
    public function handle(): void;

    /**
     * @param array $data
     *
     * @return void
     */
    public function process(array $data): IWebhookHandler;
}
