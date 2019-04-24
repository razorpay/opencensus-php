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
        if ($gateway === Downtime\Entity::ALL)
        {
            $this->addAllGatewayDowntimeForNetwork($network);
        }
        else if ($network === Downtime\Entity::ALL)
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

        return $unavailableNetworks;
    }

    public function getGatewaysSupportingNetwork(string $network)
    {
        return $this->networks[$network];
    }

    // --------------- Helper functions -------------------------------------

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

        $gateways = array_keys(Gateway::$cardNetworkMap);

        foreach ($gateways as $gateway)
        {
            if ($gateway === Gateway::SHARP)
            {
                continue;
            }

            $this->initializeNetworksForGateway($gateway);
        }
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
