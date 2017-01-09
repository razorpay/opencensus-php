<?php

namespace RZP\Models\Terminal\GatewayPriorities;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Payment\Method;

class Service extends Base\Service
{
    protected $redis;

    public function __construct()
    {
        parent::__construct();

        $this->redis = $this->app['redis_store'];
    }

    public function addGatewayPrioritiesForMethod(string $method, array $data)
    {
        $priorities = (new Entity($method))->build($data);

        if ($this->redis->save($priorities) === true)
        {
            return $priorities->toArray();
        }
    }

    public function fetchGatewayPriorities()
    {
        $methods = [Method::CARD, Method::NETBANKING];

        $result = new Base\PublicCollection;

        foreach ($methods as $method)
        {
            $priorities = $this->redis->fetchData(new Entity($method));

            $result->push($priorities->toArray());
        }

        return $result->flatMap(function ($value)
        {
            return $value;
        })->toArray();
    }

    public function removeGatewayPrioritiesForMethod(string $method, array $gateways)
    {
        $gatewayPriorities = new Entity($method);

        $gatewayPriorities = $this->redis->removeData($gatewayPriorities, $gateways);

        $gatewayPriorities = $this->redis->fetchData($gatewayPriorities);

        return $gatewayPriorities->toArray();
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
                    $gateways = array_merge($gateways, DefaultPriorities::$directCardGatewaysInTestOrder);
                }

                break;

            case Method::NETBANKING:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ?? DefaultPriorities::$directNetbankingGatewaysOrder;

                if ($mode === Mode::TEST)
                {
                    $gateways = array_merge($gateways, DefaultPriorities::$directNetbankingGatewaysInTestOrder);
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
        $priorities = new Entity($method);

        $this->redis->fetchData($priorities);

        return $priorities->getGateways();
    }
}
