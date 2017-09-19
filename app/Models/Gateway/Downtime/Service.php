<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime\Webhook;

class Service extends Base\Service
{
    protected $processor;

    public function create(array $input)
    {
        $downtime = $this->core()->create($input);

        return $downtime->toArrayAdmin();
    }

    public function edit($id, array $input)
    {
        $downtime = $this->core()->edit($id, $input);

        return $downtime->toArrayAdmin();
    }

    public function getPublicGatewayDowntimeData(): array
    {
        $downtimes = $this->core()->getPublicGatewayDowntimeData();

        return $downtimes->toArrayCheckout();
    }

    public function getDowntimeDataForMerchant(): array
    {
        // Currently we are only exposing netbanking downtimes over the public
        // downtime fetch route. For other methods, support will be added after
        // the relevant downtimes are being utilised on Razorpay checkout.
        $downtimes = $this->core()->getPublicGatewayDowntimeData([
            Method::NETBANKING,
        ]);

        return $downtimes->toArrayPublic();
    }

    public function processGatewayDowntimeWebhook(string $source, array $input)
    {
        $processor = new Webhook\Processor($source);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, $input);

        $processor->validate($input);

        $data = $processor->process($input);

        return $data;
    }
}
