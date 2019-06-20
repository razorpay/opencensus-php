<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Card\Network;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime;

class CardNetworkMapping
{
    protected $gateways;

    protected $networks;

    const CARD = 'card';

    public function __construct()
    {
        $this->initializeGatewayNetworkRelations();
    }

    public function addDowntime(string $gateway, string $network)
    {
        // Don't process downtime for gateways which are not currently active
        if ((in_array($gateway, Constants::CARD_GATEWAYS) === false) and
            ($gateway !== Downtime\Entity::ALL))
        {
            return;
        }

        // Gateway downtimes created without network field is created as `Unknown`
        // hence we are considering `Unknown` and `All` as same.
        // For eg. Downtimes created by StatusCake only sends `gateway`
        if (($gateway === Downtime\Entity::ALL) and
            (in_array($network, [Downtime\Entity::ALL, Downtime\Entity::UNKNOWN])))
        {
            $this->addAllGatewayDowntimeForAllNetwork();
        }
        else if ($gateway === Downtime\Entity::ALL)
        {
            $this->addAllGatewayDowntimeForNetwork($network);
        }
        else if (in_array($network, [Downtime\Entity::ALL, Downtime\Entity::UNKNOWN]))
        {
            $this->addAllNetworkDowntimeForGateway($gateway);
        }
        else
        {
            $this->addDowntimeForGatewayNetwork($gateway, $network);
        }
    }

    public function getUnavailableNetworks()
    {
        $unavailableNetworks = [];

        foreach ($this->networks as $network => $gateways)
        {
            if (empty($gateways) === true)
            {
                $unavailableNetworks[] = $network;
            }
        }

        if (count($unavailableNetworks) === count($this->networks))
        {
            return [Downtime\Entity::ALL];
        }
        else
        {
            return $unavailableNetworks;
        }
    }

    public function getGatewaysSupportingNetwork(string $network)
    {
        return $this->networks[$network];
    }

    // --------------- Helper functions -------------------------------------

    protected function addAllGatewayDowntimeForAllNetwork()
    {
        foreach ($this->networks as $network => $gateway)
        {
            $this->networks[$network] = [];
        }

        foreach ($this->gateways as $gateway => $network)
        {
            $this->gateways[$gateway] = [];
        }
    }

    protected function addAllGatewayDowntimeForNetwork(string $network)
    {
        foreach ($this->networks[$network] as $gateway)
        {
            array_delete($network, $this->gateways[$gateway]);
        }

        $this->networks[$network] = [];
    }

    protected function addAllNetworkDowntimeForGateway(string $gateway)
    {
        foreach ($this->gateways[$gateway] as $network)
        {
            array_delete($gateway, $this->networks[$network]);
        }

        $this->gateways[$gateway] = [];
    }

    protected function addDowntimeForGatewayNetwork(string $gateway, string $network)
    {
        array_delete($gateway, $this->networks[$network]);

        array_delete($network, $this->gateways[$gateway]);
    }

    // --------------- Initialization ---------------------------------------

    protected function initializeGatewayNetworkRelations()
    {
        // Contains deprecated gateways
        // $gateways = Gateway::$methodMap[self::CARD];

        // These gateways are not being actively used
        // $gateways = array_keys(Gateway::$cardNetworkMap);

        // We are checking the gateways that are being actively used.
        $gateways = Constants::CARD_GATEWAYS;

        foreach ($gateways as $gateway)
        {
            $this->initializeNetworksForGateway($gateway);
        }

        $this->networks[Downtime\Entity::ALL] = array_keys($this->gateways);

        $this->gateways[Downtime\Entity::ALL] = array_keys($this->networks);
    }

    protected function initializeNetworksForGateway(string $gateway)
    {
        $networks = Gateway::$cardNetworkMap[$gateway];

        array_delete(Network::UNKNOWN, $networks);

        foreach ($networks as $network)
        {
            $this->gateways[$gateway][] = $network;

            $this->networks[$network][] = $gateway;
        }
    }
}
