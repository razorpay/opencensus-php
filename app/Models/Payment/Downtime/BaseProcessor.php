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

class BaseProcessor extends Base\Core
{
    protected function endOngoingDowntimes()
    {
        $ongoingDowntimes = $this->getRepo()->fetchOngoingDowntimesByMethod($this->method);

        foreach ($ongoingDowntimes as $downtime)
        {
            $downtime->setEndNow();

            $this->getRepo()->saveOrFail($downtime);
        }
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
        return $this->getRepo()->getDuplicate($input);
    }

    protected function getRepo()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }
}
