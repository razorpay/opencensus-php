<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Jobs\PaymentDowntimeEvent;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Models\Payment\Downtime\Service;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_DOWNTIME_CREATE,
            $input
        );

        $downtime = (new Entity)->build($input);

        $this->repo->saveOrFail($downtime);

        if($downtime->isScheduled() === false)
        {
            (new Service())->emailDowntime(Constants::CREATED, $downtime);

            $this->trace->info(TraceCode::TRIGGER_WEBHOOK_NOTIFICATIONS, ["state"=> Status::STARTED, "downtime" => $downtime]);

            PaymentDowntimeEvent::dispatch($this->mode, Status::STARTED, serialize($downtime));
        }

        return $downtime;
    }

    public function edit(Entity $downtime, array $input): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_DOWNTIME_EDIT, [
            'Input'     => $input,
            'Downtime'  => $downtime->toArray(),
        ]);

        $lastSeverity = $downtime->getSeverity();

        $downtime->edit($input);

        $this->repo->saveOrFail($downtime);

        if($downtime->isScheduled() === false)
        {
            (new Service())->emailDowntime(Constants::CREATED, $downtime, $lastSeverity);

            $this->trace->info(TraceCode::TRIGGER_WEBHOOK_NOTIFICATIONS, ["state"=> Status::STARTED, "downtime" => $downtime]);

            PaymentDowntimeEvent::dispatch($this->mode, Status::STARTED, serialize($downtime), $lastSeverity);
        }

        return $downtime;
    }

    public function createFromGatewayDowntimes(array $input = [])
    {
        $gatewayDowntimes = $this->repo->gateway_downtime->fetchCurrentAndFutureDowntimes($withoutTerminal = true);

        $this->trace->info(TraceCode::FETCHED_GATEWAY_DOWNTIMES_FROM_DB, ["context" => $gatewayDowntimes]);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::STATUSCAKE);
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::VAJRA);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_PHONEPE, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::PHONEPE);
        }

        foreach (Payment\Method::getAllPaymentMethods() as $method)
        {
            $downtimeProcessor = __NAMESPACE__ . '\\' . studly_case($method) . 'Processor';

            if (class_exists($downtimeProcessor) === true)
            {
                (new $downtimeProcessor)->process($gatewayDowntimes);
            }
        }
    }
}
