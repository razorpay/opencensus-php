<?php

namespace RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

use RZP\Trace\TraceCode;

class DomainProxyConfigFailedProcessor extends BaseProcessor implements IProcessor
{
    public function handle()
    {
        $this->trace->info(TraceCode::PROXY_CONFIG_FAILED_PROCESSOR, $this->getData()->toArray());

        // TODO: Implement handle() method.
    }
}
