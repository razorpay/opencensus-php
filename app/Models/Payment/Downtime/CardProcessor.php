<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime\Source;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class CardProcessor extends BaseProcessor
{
    protected $method = Method::CARD;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $this->trace->info(TraceCode::FILTERED_METHOD_PROC_SPECIFICS, ["context"=>$gatewayDowntimes]);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_CARD, false);

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
        $unavailableNetworks = $this->calculateUnavailableNetworks($gatewayDowntimes);

        $unavailableIssuer = $this->calculateUnavailableIssuer($gatewayDowntimes);

        foreach ($unavailableNetworks as $network)
        {
            $downtimes = $gatewayDowntimes->where(GatewayDowntime::NETWORK, '=', $network);
            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME, ["network"=>$network, "downtime"=>$downtimes]);

            if($this->shouldUseMutex()) {
                $input = $this->getPaymentDowntimeCreationArray($network, $downtimes->count() != 0 ? $downtimes : $gatewayDowntimes, false);

                if ($input === null)
                {
                    return null;
                }

                $mutexKey = $input[Entity::METHOD] . $network . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

                $this->createPaymentDowntimeWithMutex($input, $mutexKey);
            }
            else
            {
                $this->createPaymentDowntime($network, $downtimes->count() != 0 ? $downtimes : $gatewayDowntimes);
            }

        }

        foreach ($unavailableIssuer as $issuer)
        {
            $downtimes = $gatewayDowntimes->where(GatewayDowntime::ISSUER, '=', $issuer);
            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME, ["issuer"=>$issuer, "downtime"=>$downtimes]);

            if($this->shouldUseMutex()) {
                $input = $this->getPaymentDowntimeCreationArray($issuer, $downtimes, true);

                if ($input === null)
                {
                    return null;
                }

                $mutexKey = $input[Entity::METHOD] . $issuer . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

                $this->createPaymentDowntimeWithMutex($input, $mutexKey);
            }
            else
            {
                $this->createPaymentDowntime($issuer, $downtimes, true);
            }

        }

        // Since the The value of issuer and network can not be same, combined them in a single list
        $unavailable = array_merge($unavailableIssuer, $unavailableNetworks);

        $this->endOngoingDowntimes($unavailable, $mid);
    }

    protected function calculateUnavailableNetworks(Collection $gatewayDowntimes)
    {
        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        if ($gatewayDowntimes->isEmpty() === true)
        {
            return [];
        }

        $mapping = new CardNetworkMapping;

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {
            // Gateway downtimes created without network field is created as `Unknown`
            // hence we are considering `Unknown` and `All` as same.
            if ((in_array($gatewayDowntime->getIssuer(), [GatewayDowntime::ALL, GatewayDowntime::UNKNOWN])) and
                (in_array($gatewayDowntime->getCardType(), [GatewayDowntime::ALL, GatewayDowntime::UNKNOWN])))
            {
                $mapping->addDowntime($gatewayDowntime->getGateway(), $gatewayDowntime->getNetwork());
            }
        }

        return $mapping->getUnavailableNetworks();
    }

    protected function calculateUnavailableIssuer(Collection $gatewayDowntimes)
    {
        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        if ($gatewayDowntimes->isEmpty() === true)
        {
            return [];
        }

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::GATEWAY, '=', GatewayDowntime::ALL);

        $gatewayDowntimes = $gatewayDowntimes->whereNotIn(GatewayDowntime::ISSUER, [GatewayDowntime::NA, GatewayDowntime::UNKNOWN]);

        $gatewaydowntimes = $gatewayDowntimes->unique(GatewayDowntime::ISSUER);

        $issuers = $gatewaydowntimes->pluck(GatewayDowntime::ISSUER)->toArray();

        return $issuers;
    }

    protected function createPaymentDowntime(string $network, Collection $gatewayDowntimes, $issuer = false)
    {
        $input = $this->getPaymentDowntimeCreationArray($network, $gatewayDowntimes, $issuer);

        if ($input === null)
        {
            return null;
        }

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

    protected function createPaymentDowntimeWithMutex($input, $mutexKey)
    {
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

    protected function getPaymentDowntimeCreationArray(string $network, Collection $gatewayDowntimes, $issuer = false)
    {
        $begin = $end = null;

        if ($issuer === true)
        {
            list($begin, $end) = $this->calculateDowntimePeriodForIssuer($network, $gatewayDowntimes);
        }
        else
            {
                list($begin, $end) = $this->calculateDowntimePeriodForNetwork($network, $gatewayDowntimes);
        }

        if ($begin === null)
        {
            return null;
        }

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $status = Status::SCHEDULED;

        if( $scheduled === false)
        {
            $status = Status::STARTED;
        }

        $type = $gatewayDowntimes->pluck(GatewayDowntime::CARD_TYPE)[0];

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => $status,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::TYPE      => $type,
        ];

        if ($issuer === true)
        {
            $input[Entity::ISSUER] = $network;
        }
        else
        {
            $input[Entity::NETWORK] = $network;
        }

        $mids = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null)->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();
        $this->trace->info(TraceCode::FINAL_DOWNTIME_OBJECT, ["downtimeObject" => $input]);

        if(sizeof($mids) === 1)
        {
            $input[Entity::MERCHANT_ID] = $mids[0];
        }
        $this->trace->info(TraceCode::FINAL_DOWNTIME_OBJECT, ["downtimeObject" => $input]);

        return $input;
    }

    protected function calculateDowntimePeriodForNetwork(string $network, Collection $gatewayDowntimes)
    {
        $supportingGateways = (new CardNetworkMapping)->getGatewaysSupportingNetwork($network);

        $allGatewayDowntime = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, GatewayDowntime::ALL);

        $allGatewayDowntime = $allGatewayDowntime->sortBy(GatewayDowntime::BEGIN);

        // Filter for network and supporting gateways
        // Network can be `ALL` if the whole gateway is down or `network` if only one network is facing issue
        $affectingGatewayDowntimes = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, $supportingGateways)
                                                      ->whereIn(GatewayDowntime::NETWORK, [GatewayDowntime::ALL, $network]);

        $begin = $end = null;

        if ($affectingGatewayDowntimes->count() > 0)
        {
            list($begin, $end) = $this->getOverlappingDowntimePeriod($affectingGatewayDowntimes);
        }

        // If all gateways are down for that network then we don't need
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

    protected function calculateDowntimePeriodForIssuer(string $issuer, Collection $gatewayDowntimes)
    {
        $GatewayDowntime = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, GatewayDowntime::ALL);

        $GatewayDowntime = $GatewayDowntime->whereIn(GatewayDowntime::ISSUER, [GatewayDowntime::ALL, $issuer]);

        $GatewayDowntime = $GatewayDowntime->sortBy(GatewayDowntime::BEGIN);

        $begin = $end = null;

        if ($GatewayDowntime->count() > 0)
        {
            list($begin, $end) = $this->getOverlappingDowntimePeriod($GatewayDowntime);
        }

        return [$begin, $end];
    }
}
