<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;
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

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);

        $unavailableNetworks = $this->calculateUnavailableNetworks($gatewayDowntimes);

        foreach ($unavailableNetworks as $network)
        {
            $this->createPaymentDowntime($network, $gatewayDowntimes);
        }

        $this->endOngoingDowntimes($unavailableNetworks);
    }

    protected function calculateUnavailableNetworks(Collection $gatewayDowntimes)
    {
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

    protected function createPaymentDowntime(string $network, Collection $gatewayDowntimes)
    {
        $input = $this->getPaymentDowntimeCreationArray($network, $gatewayDowntimes);

        if ($input === null)
        {
            return null;
        }

        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
        }
        else
        {
            // During update the status gets updated and hence multiple notifications are triggered.
            if (isset($input[Entity::STATUS]))
            {
                unset($input[Entity::STATUS]);
            }

            $downtime = (new Core)->edit($downtime, $input);
        }
    }

    protected function getPaymentDowntimeCreationArray(string $network, Collection $gatewayDowntimes)
    {
        list($begin, $end) = $this->calculateDowntimePeriodForNetwork($network, $gatewayDowntimes);

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

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => $status,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::NETWORK   => $network,
        ];

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
}
