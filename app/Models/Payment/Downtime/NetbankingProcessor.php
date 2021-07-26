<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime\Source;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class NetbankingProcessor extends BaseProcessor
{
    protected $method = Method::NETBANKING;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::STATUSCAKE);

        $this->trace->info(TraceCode::FILTERED_METHOD_PROC_SPECIFICS, ["context"=>$gatewayDowntimes]);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_NETBANKING, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        $platformDowntime = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '=', null);

        $merchantDowntime = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null);

        $this->trace->info(TraceCode::PLATFORM_SPECIFIC_DOWNTIMES, ["context"=>$platformDowntime]);
        $this->trace->info(TraceCode::MERCHANT_SPECIFIC_DOWNTIMES, ["context"=>$merchantDowntime]);

        $this->processPlatform($platformDowntime);

        $this->processMerchant($merchantDowntime);
    }

    protected function processMerchant(Collection $gatewayDowntimes)
    {
        $merchantIds = $gatewayDowntimes->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();

        foreach ($merchantIds as $merchantId)
        {
            $merchantDowntimes = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '=', $merchantId);
            $this->trace->info(TraceCode::MERCHANT_DOWNTIME_CREATION, ["merchantId" =>$merchantId, "downtimes"  => $merchantDowntimes]);
            $this->processPlatform($merchantDowntimes, $merchantId);
        }

        $this->endOngoingDowntimesForMerchants($merchantIds);
    }

    protected function processPlatform(Collection $gatewayDowntimes, $mid=null)
    {
        $unavailableBanks = $this->calculateUnavailableBanks($gatewayDowntimes);

        foreach ($unavailableBanks as $bank)
        {
            $downtime = $gatewayDowntimes->where(GatewayDowntime::ISSUER, '=', $bank);
            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME, ["bank"=>$bank, "downtime"=>$downtime]);

            $tDowntimes = $downtime->count() != 0 ? $downtime : $gatewayDowntimes;

            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME_NXT, ["bank"=>$bank, "downtime"=>$tDowntimes]);

            if($this->shouldUseMutex())
            {
                $input = $this->getPaymentDowntimeCreationArray($bank, $tDowntimes);

                if ($input === null)
                {
                    return;
                }

                $this->createPaymentDowntimeWithMutex($input);
            }
            else
            {
                $this->createPaymentDowntime($bank, $tDowntimes);
            }

        }

        $this->endOngoingDowntimes($unavailableBanks, $mid);
    }

    protected function createPaymentDowntimeWithMutex(array $input)
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

    protected function calculateUnavailableBanks(Collection $gatewayDowntimes)
    {
        if ($gatewayDowntimes->isEmpty() === true)
        {
            return [];
        }

        $mapping = new NetbankingIssuerMapping;

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {
            $mapping->addDowntime($gatewayDowntime->getGateway(), $gatewayDowntime->getIssuer());
        }

        return $mapping->getUnavailableBanks();
    }

    protected function createPaymentDowntime(string $bank, Collection $gatewayDowntimes)
    {
        $input = $this->getPaymentDowntimeCreationArray($bank, $gatewayDowntimes);

        if ($input === null)
        {
            return;
        }

        // TODO add mutex around below blocks.
        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
            $this->trace->info(TraceCode::CREATE_NEW_PAYMENT_DOWNTIME, ["downtime" =>$downtime]);
        }
        else
        {
            // During update the status gets updated and hence multiple notifications are triggered.
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

    protected function getPaymentDowntimeCreationArray(string $bank, Collection $gatewayDowntimes)
    {
        list($begin, $end) = $this->calculateDowntimePeriodForBank($bank, $gatewayDowntimes);

        if ($begin === null)
        {
            return null;
        }

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $status = Status::SCHEDULED;

        if ($scheduled === false)
        {
            $status = Status::STARTED;
        }

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => $status,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::ISSUER    => $bank,
        ];

        $mids = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null)->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();
        $this->trace->info(TraceCode::EXTRACTED_MERCHANTS_FROM_DOWNTIME, ["mids" => $mids]);

        if(sizeof($mids) === 1)
        {
            $input[Entity::MERCHANT_ID] = $mids[0];
        }

        $this->trace->info(TraceCode::FINAL_DOWNTIME_OBJECT, ["downtimeObject" => $input]);

        return $input;
    }

    protected function calculateDowntimePeriodForBank(string $bank, Collection $gatewayDowntimes)
    {
        $supportingGateways = (new NetbankingIssuerMapping)->getGatewaysSupportingBank($bank);

        $allGatewayDowntime = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, GatewayDowntime::ALL);

        $allGatewayDowntime = $allGatewayDowntime->sortBy(GatewayDowntime::BEGIN);

        // Filter for issuer and supporting gateways
        // Issuer can be `ALL` if the whole gateway is down or `bank` if only one bank is facing issue
        $affectingGatewayDowntimes = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, $supportingGateways)
                                                      ->whereIn(GatewayDowntime::ISSUER, [GatewayDowntime::ALL, $bank]);

        $begin = $end = null;

        if ($affectingGatewayDowntimes->count() > 0)
        {
            list($begin, $end) = $this->getOverlappingDowntimePeriod($affectingGatewayDowntimes);
        }

        // If all gateways are down for that bank then we don't need
        // to calculate for overlapping gateway downtime
        if ($allGatewayDowntime->count() > 0)
        {
            $allDowntimeBegin = $allGatewayDowntime->first()[GatewayDowntime::BEGIN];

            $allDowntimeEnd = $allGatewayDowntime->first()[GatewayDowntime::END];

            if (($begin === null) or
                ($begin > $allDowntimeBegin))
            {
                return [$allDowntimeBegin, $allDowntimeEnd];
            }
        }

        return [$begin, $end];
    }

}
