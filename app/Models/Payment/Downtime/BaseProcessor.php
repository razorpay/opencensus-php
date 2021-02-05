<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Jobs\PaymentDowntimeEvent;
use RZP\Gateway\Upi\Base\ProviderPsp;
use RZP\Models\Gateway\Downtime\Severity;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Payment\Downtime\Constants;
use RZP\Constants\Entity as EntityConstants;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class BaseProcessor extends Base\Core
{
    protected function endOngoingDowntimes(array $unavailableList = [], string $mid = null)
    {
        $ongoingDowntimes = $this->getRepo()->fetchOngoingDowntimesByMethodAndMerchant($this->method, $mid);

        $ongoingDowntimes = $ongoingDowntimes->where(Entity::PSP, '!=', ProviderPsp::GOOGLE_PAY);

        /**
         * Filter out all the downtimes which should be resolved by checking the unavailable list
         * `issuer` in case of nebanking and wallet
         * `network` in case of card
         *
         * This insures that downtime for `issuers` which are available now i.e which are not in unavailable list
         * gets resolved
         */
        if (array_key_exists($this->method, Constants::METHOD_QUERY_MAP) === true)
        {
            $attributes = Constants::getMethodQueryInstrument($this->method);

            foreach ($attributes as $attribute)
            {
                $ongoingDowntimes = $ongoingDowntimes->whereNotIn($attribute, $unavailableList);
            }
        }

        $this->endDowntime($ongoingDowntimes);
    }

    protected function endOngoingDowntimesForMerchants(array $merchantsWithDowntime)
    {
        $ongoingDowntimes = $this->getRepo()->fetchOngoingDowntimesByMethodForMerchantsWithoutDowntimes($this->method, $merchantsWithDowntime);

        $this->endDowntime($ongoingDowntimes);
    }

    protected function endDowntime($ongoingDowntimes)
    {
        foreach ($ongoingDowntimes as $downtime)
        {
            $downtime->setEndNow();

            $downtime->setStatus(Status::RESOLVED);

            $this->getRepo()->saveOrFail($downtime);

            $this->trace->info(
                TraceCode::PAYMENT_DOWNTIME_RESOLVE,
                $downtime->toArray()
            );

            PaymentDowntimeEvent::dispatch($this->mode, Status::RESOLVED, serialize($downtime));
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

    protected function getOverlappingDowntimePeriod(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->sortBy(GatewayDowntime::BEGIN);

        $gatewayDowntimes = $gatewayDowntimes->unique(GatewayDowntime::GATEWAY);

        $gatewayDowntimes = array_values($gatewayDowntimes->toArray());

        $beginTime = $gatewayDowntimes[0][GatewayDowntime::BEGIN];

        $endTime = $gatewayDowntimes[0][GatewayDowntime::END];

        for ($idx = 1; $idx < count($gatewayDowntimes); $idx++)
        {
            if (($endTime === null) or
                ($endTime > $gatewayDowntimes[$idx][GatewayDowntime::BEGIN]))
            {
                $beginTime = $gatewayDowntimes[$idx][GatewayDowntime::BEGIN];

                $endTime = min($endTime, $gatewayDowntimes[$idx][GatewayDowntime::END]);
            }
            else
            {
                $beginTime = null;

                break;
            }
        }

        return [$beginTime, $endTime];
    }

    protected function getRepo()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }
}
