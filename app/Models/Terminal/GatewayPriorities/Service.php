<?php

namespace RZP\Models\Terminal\GatewayPriorities;

use RZP\Constants\Mode;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function addGatewayPrioritiesForMethod(string $method, array $data)
    {
        $priorities = (new Entity)->build($method, $data);

        if ($priorities->save() === true)
        {
            return $priorities->toArray();
        }
    }

    public function fetchGatewayPriorities()
    {
        $methods = ['card', 'netbanking'];

        $priorities = new Base\PublicCollection;

        foreach ($methods as $method)
        {
            $priorities->push((new Entity)->fetchPrioritiesForMethod($method)->toArray());
        }

        return $priorities->flatMap(function ($value)
        {
            return $value;
        })->toArray();
    }

    public function removeGatewayPrioritiesForMethod(string $method, array $gateways)
    {
        $gatewayPriorities = new Entity;

        $gatewayPriorities->removePrioritiesForMethod($method, $gateways);

        return $gatewayPriorities->fetchPrioritiesForMethod($method)->toArray();
    }

    public function getGatewaysPriority($method, $mode = Mode::LIVE)
    {
        $gateways = [];

        switch ($method)
        {
            case Method::CARD:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ?? DefaultPriorities::$directCardGatewaysOrder;

                if ($mode === Mode::TEST)
                {
                    $gateways = array_merge($gateways, DefaultPriorities::$directCardGatewaysInTest);
                }

                break;

            case Method::NETBANKING:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ?? DefaultPriorities::$directNetbankingGatewaysOrder;

                if ($mode === Mode::TEST)
                {
                    $gateways = array_merge($gateways, self::$directNetbankingGatewaysInTest);
                }

                // Adds direct netbanking to have highest priority
                array_unshift($gateways, 'direct');

                break;

            default:
                break;
        }

        return $gateways;
    }

    public function fetchOrderedGatewaysForMethod(string $method)
    {
        $priorities = (new Entity)->fetchPrioritiesForMethod($method);

        return $priorities->getGateways();
    }
}
