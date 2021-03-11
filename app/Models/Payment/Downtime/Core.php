<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Exception;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
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
            PaymentDowntimeEvent::dispatch($this->mode, Status::STARTED, serialize($downtime), $lastSeverity);
        }

        return $downtime;
    }

    public function createFromGatewayDowntimes(array $input = [])
    {
        $gatewayDowntimes = $this->repo->gateway_downtime->fetchCurrentAndFutureDowntimes($withoutTerminal = true);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::STATUSCAKE);

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
