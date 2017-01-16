<?php

namespace RZP\Models\Gateway\Priority;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\DataStore;
use RZP\Models\Payment\Method;

class Core extends Base\Core
{
    const ALLOWED_METHODS = [Method::CARD, Method::NETBANKING];

    protected $store;

    // Namespace used for storing the data in store provider
    protected static $storeNameSpace = 'gateway_priority';

    public function __construct()
    {
        parent::__construct();

        $this->store = $this->app['data_store'];
    }

    public function addPriorityForMethod(string $method, array $priorityData)
    {
        $priority = new DataStore\PrioritySet(self::$storeNameSpace, $method);

        $priority->setData($priorityData);

        $priority = $this->store->saveOrFail($priority);

        return $priority;
    }

    public function fetchPriority()
    {
        $result = new Base\PublicCollection;

        foreach (self::ALLOWED_METHODS as $method)
        {
            $priority = new DataStore\PrioritySet(self::$storeNameSpace, $method);

            $priority = $this->store->fetch($priority);

            $result->push($priority->toArray());
        }

        return $result;
    }

    public function removePriorityForMethod(string $method, array $gateways)
    {
         $priority = new DataStore\PrioritySet(self::$storeNameSpace, $method);

         $priority->setData($gateways);

         $priority = $this->store->delete($priority);

         $priority = $this->store->fetch($priority);

         return $priority;
    }

    public function getGatewaysForMethod(string $method)
    {
        $gateways = [];

        switch ($method)
        {
            case Method::CARD:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ??
                            Defaults::$directCardGatewaysOrder;

                if ($this->mode === Mode::TEST)
                {
                    $gateways = array_merge($gateways, Defaults::$directCardGatewaysInTestOrder);
                }

                break;

            case Method::NETBANKING:
                $gateways = $this->fetchOrderedGatewaysForMethod($method) ??
                            Defaults::$directNetbankingGatewaysOrder;

                if ($this->mode === Mode::TEST)
                {
                    $gateways = array_merge($gateways, Defaults::$directNetbankingGatewaysInTestOrder);
                }

                // Adds direct netbanking to have highest priority
                array_unshift($gateways, 'direct');

                break;

            default:
                break;
        }

        return $gateways;
    }

    /**
     * Fetches the gateways ordered by priority socre
     *
     * @param  string $method Method to fetch gateway s for
     * @return [type]         [description]
     */
    protected function fetchOrderedGatewaysForMethod(string $method)
    {
        $priority = new DataStore\PrioritySet(self::$storeNameSpace, $method);

        $priority = $this->store->fetch($priority);

        return $this->getGateways($priority);
    }

    protected function getGateways($priority)
    {
        return $priority->getSetMembers();
    }
}
