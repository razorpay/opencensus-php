<?php

namespace RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

use RZP\Trace\TraceCode;

class DomainProxyConfigSuccessProcessor extends BaseProcessor implements IProcessor
{
    public function handle()
    {
        $this->trace->info(TraceCode::PROXY_CONFIG_SUCCESS_PROCESSOR, $this->getData()->toArray());

        // TODO: Implement handle() method.
    }
}
