<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime\Severity;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Constants\Entity as EntityConstants;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class UpiProcessor extends Base\Core
{
    const UPI = 'upi';

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', self::UPI);

        if (($gatewayDowntimes->isEmpty() === true) or
            ($this->impliesUpiDowntime($gatewayDowntimes) === false))
        {
            return;
        }

        return $this->createPaymentDowntime($gatewayDowntimes);
    }

    protected function impliesUpiDowntime(Collection $gatewayDowntimes)
    {
        $gatewaysDown = $gatewayDowntimes->pluck(GatewayDowntime::GATEWAY)->toArray();

        $upiGateways = Gateway::$methodMap[self::UPI];

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
            Entity::METHOD    => self::UPI,
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

    protected function calculateDowntimeScheduled(Collection $gatewayDowntimes): bool
    {
        return $gatewayDowntimes->every(GatewayDowntime::SCHEDULED, '=', true);
    }

    protected function calculateDowntimeSeverity(Collection $gatewayDowntimes): string
    {
        $sortedBySeverity = $gatewayDowntimes->sort(function($a, $b) {
            $severityA = ReasonCode::getSeverity($a->getReasonCode());
            $severityB = ReasonCode::getSeverity($a->getReasonCode());

            return Severity::PRECEDENCE[$severityA] - Severity::PRECEDENCE[$severityB];
        });

        $reasonCode = $sortedBySeverity->first()->getReasonCode();

        return ReasonCode::getSeverity($reasonCode);
    }

    protected function getDuplicate(array $input)
    {
        return $this->getPaymentDowntimeRepository()->getDuplicate($input);
    }

    protected function getPaymentDowntimeRepository()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }
}
