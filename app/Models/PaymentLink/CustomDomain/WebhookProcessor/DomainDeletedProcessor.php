<?php

namespace RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

use RZP\Trace\TraceCode;

class DomainDeletedProcessor extends BaseProcessor implements IProcessor
{
    public function handle()
    {
        $this->trace->info(TraceCode::CUSTOM_DOMAIN_DELETED_PROCESSOR, $this->getData()->toArray());

        // TODO: Implement handle() method.
    }
}
