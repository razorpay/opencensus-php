<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Error\ErrorCode;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime\Source;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class FpxProcessor extends BaseProcessor
{
    protected $method = Method::FPX;

    /*
    @param Collection $gatewayDowntimes

    Here we will the firstly filter the gateway downtimes on the basis of the method as fpx, source as
    PAYNET (external api to get the downtimes) and merchant id should be null. Because downtime needs to be created
    for the issuer/bank basis only, not for any specific merchant
    */
    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '=', Source::PAYNET);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_FPX, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        $this->processDowntime($gatewayDowntimes);
    }


    protected function processDowntime(Collection $gatewayDowntimes, $mid=null)
    {
        $unavailableBanks = $this->getUnavailableIssuers($gatewayDowntimes);

        foreach ($gatewayDowntimes as $downtime)
        {
            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME, ["downtime" => $downtime]);

            $input = $this->getPaymentDowntimeCreationArray($downtime);

            try {
                $this->createPaymentDowntime($input);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::PAYMENT_DOWNTIME_CREATE_WEBHOOK_FAILED, [
                    "bank" => $downtime->getIssuer(),
                    "method" => $this->method,
                    "error_code" => $e->getCode(),
                ]);
            }
        }

        $this->endOngoingDowntimes($unavailableBanks, $mid);
    }

    protected function createPaymentDowntime(array $input)
    {
        $mutexKey = $input[Entity::METHOD] . $input[Entity::ISSUER] . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

        $this->mutex->acquireAndRelease(
            $mutexKey,
            function () use ($input)
            {
                $this->createPaymentDowntimeWithDowntimeCreationArray($input);
            },
            10,
            ErrorCode::BAD_REQUEST_PAYMENT_DOWNTIME_MUTEX_TIMED_OUT
        );
    }

    protected function getUnavailableIssuers(Collection $gatewayDowntimes): array
    {
        $gatewayDowntimes = $gatewayDowntimes->unique(GatewayDowntime::ISSUER);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::ISSUER, '!=', null);

        $gatewayDowntimes = $gatewayDowntimes->whereNotIn(GatewayDowntime::ISSUER, [GatewayDowntime::UNKNOWN, GatewayDowntime::NA, GatewayDowntime::ALL]);

        return $gatewayDowntimes->pluck(GatewayDowntime::ISSUER)->toArray();
    }


    protected function createPaymentDowntimeWithDowntimeCreationArray(array $input)
    {
        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
            $this->trace->info(TraceCode::CREATE_NEW_PAYMENT_DOWNTIME, ["downtime" =>$downtime]);
        }
        else
        {
            if (isset($input[Entity::SCHEDULED]) && isset($input[Entity::SEVERITY]) &&
                ($input[Entity::SEVERITY] != $downtime->getSeverity()))
            {
                $updateList = [
                    Entity::SEVERITY => $input[Entity::SEVERITY],
                    Entity::SCHEDULED => $input[Entity::SCHEDULED],
                ];
                $downtime = (new Core)->edit($downtime, $updateList);
                $this->trace->info(TraceCode::EDIT_PAYMENT_DOWNTIME, ["downtime" =>$downtime]);
            }
            return $downtime;
        }
    }

    protected function getPaymentDowntimeCreationArray($gatewayDowntime)
    {
        list($begin, $end) = $this->calculateDowntimePeriodForBank($gatewayDowntime);

        if ($begin === null)
        {
            return null;
        }

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntime);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntime);

        $status = Status::STARTED;

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => $status,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::ISSUER    => $gatewayDowntime->getIssuer(),
        ];

        $this->trace->info(TraceCode::FINAL_DOWNTIME_OBJECT, ["downtimeObject" => $input]);

        return $input;
    }

    protected function calculateDowntimeScheduled($gatewayDowntimes): bool
    {
        return false;
    }

    protected function calculateDowntimeSeverity($gatewayDowntimes): string
    {
        return ReasonCode::getSeverity(ReasonCode::ISSUER_DOWN);
    }

    protected function calculateDowntimePeriodForBank($gatewayDowntimes)
    {
        $gatewayDowntimeMaxStart = $gatewayDowntimes->getBegin();

        $gatewayDowntimeMinEnd = $gatewayDowntimes->getEnd();

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }
}
