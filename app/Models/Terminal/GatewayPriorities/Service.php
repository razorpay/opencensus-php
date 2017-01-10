<?php

namespace RZP\Models\Terminal\GatewayPriorities;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment\Method;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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

        $this->trace->info(TraceCode::ADD_GATEWAY_PRIORITIES_REQUEST, $priorities->toArray());

        if ($this->redis->save($priorities) === true)
        {
            return $priorities->toArray();
        }

        throw new Exception\ServerErrorException(
                        "Error saving priorities for method: $method",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $data);
    }

    public function fetchGatewayPriorities()
    {
        $methods = [Method::CARD, Method::NETBANKING];

        $result = new Base\PublicCollection;

        foreach ($methods as $method)
        {
            $priorities = $this->redis->fetchEntityData(new Entity($method));

            $result->push($priorities->toArray());
        }

        // $result is a collection of arrays. Running it through a flatmap to get
        // the structure as <method> => <priorities>
        $result = $result->flatMap(function ($value)
        {
            return $value;
        });

        $this->trace->info(TraceCode::FETCH_GATEWAY_PRIORITIES_RESPONSE, $result->toArray());

        return $result->toArray();
    }

    public function removeGatewayPrioritiesForMethod(string $method, array $gateways)
    {
        $this->trace->info(TraceCode::REMOVE_GATEWAY_PRIORITIES_REQUEST,
                            [$method => $gateways]);

        $gatewayPriorities = new Entity($method);

        // Removes gateways from the redis sorted set
        $gatewayPriorities = $this->redis->removeEntityData($gatewayPriorities, $gateways);

        // Refreshes the priorities again by fetching from redis
        $gatewayPriorities = $this->redis->fetchEntityData($gatewayPriorities);

        return $gatewayPriorities->toArray();
    }

    public function getGatewaysPriority($method, $mode = Mode::LIVE)
    {
        $gateways = [];

        switch ($method)
        {
            case Method::CARD:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ??
                            DefaultPriorities::$directCardGatewaysOrder;

                if ($mode === Mode::TEST)
                {
                    $gateways = array_merge($gateways, DefaultPriorities::$directCardGatewaysInTestOrder);
                }

                break;

            case Method::NETBANKING:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ??
                            DefaultPriorities::$directNetbankingGatewaysOrder;

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

        $this->redis->fetchEntityData($priorities);

        return $priorities->getGateways();
    }
}
