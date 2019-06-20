<?php

namespace RZP\Models\Payment\Downtime;

use Illuminate\Database\Eloquent\Collection;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class UpiProcessor extends BaseProcessor
{
    protected $method = Method::UPI;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        if ($this->impliesUpiDowntime($gatewayDowntimes) === true)
        {
            $this->createPaymentDowntime($gatewayDowntimes);
        }

        if ($gatewayDowntimes->isEmpty() === true)
        {
            $this->endOngoingDowntimes();
        }
    }

    protected function impliesUpiDowntime(Collection $gatewayDowntimes)
    {
        $gatewaysDown = $gatewayDowntimes->pluck(GatewayDowntime::GATEWAY)->toArray();

        // We are checking the gateways that are being actively used.
        $upiGateways = Constants::UPI_GATEWAYS;

        if (in_array(GatewayDowntime::ALL, $gatewaysDown, true) === true)
        {
            return true;
        }

        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes);

        // The time check exists because there could be two non-overlapping
        // but mutually exhaustive downtimes in the future
        if ((empty(array_diff($upiGateways, $gatewaysDown)) === true) and
            ((($end === null) or
             ($begin < $end))))
        {
            return true;
        }

        return false;
    }

    protected function createPaymentDowntime(Collection $gatewayDowntimes): Entity
    {
        $input = $this->getPaymentDowntimeCreationArray($gatewayDowntimes);

        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
        }
        else
        {
            $downtime = (new Core)->edit($downtime, $input);
        }

        return $downtime;
    }

    protected function getPaymentDowntimeCreationArray(Collection $gatewayDowntimes): array
    {
        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes);

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => Status::SCHEDULED,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
        ];

        return $input;
    }

    protected function calculateDowntimePeriod(Collection $gatewayDowntimes): array
    {
        $gatewayDowntimeMaxStart = $gatewayDowntimes->max(GatewayDowntime::BEGIN);

        $gatewayDowntimeMinEnd = $gatewayDowntimes->filter(function ($downtime) {
            return ($downtime->getEnd() !== null);
        })->min(GatewayDowntime::END);

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }
}
