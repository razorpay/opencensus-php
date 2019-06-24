<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class CardProcessor extends BaseProcessor
{
    protected $method = Method::CARD;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

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

    protected function createPaymentDowntime(string $network, Collection $gatewayDowntimes): Entity
    {
        $input = $this->getPaymentDowntimeCreationArray($network, $gatewayDowntimes);

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

    protected function getPaymentDowntimeCreationArray(string $network, Collection $gatewayDowntimes): array
    {
        list($begin, $end) = $this->calculateDowntimePeriodForNetwork($network, $gatewayDowntimes);

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => Status::SCHEDULED,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::NETWORK   => $network,
        ];

        return $input;
    }

    protected function calculateDowntimePeriodForNetwork(string $network, Collection $gatewayDowntimes)
    {
        $supportingGateways = (new CardNetworkMapping)->getGatewaysSupportingNetwork($network);

        // Gateway downtime can also be created as gateway = ALL which
        // gets skipped while filtering affectingGatewayDowntimes
        $supportingGateways = array_merge($supportingGateways, [GatewayDowntime::ALL]);

        $affectingGatewayDowntimes = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, $supportingGateways);

        $gatewayDowntimeMaxStart = $affectingGatewayDowntimes->max(GatewayDowntime::BEGIN);

        $gatewayDowntimeMinEnd = $affectingGatewayDowntimes->filter(function ($downtime) {
            return ($downtime->getEnd() !== null);
        })->min(GatewayDowntime::END);

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }
}
