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

    protected function init()
    {
        $storeMock = $this->app['config']->get('app.data_store.mock');

        if ($storeMock === true)
        {
            $this->store = new DataStore\Mock\Manager;

            return;
        }

        $this->store = new DataStore\Manager;
    }

    public function addPriorityForMethod(string $method, array $priorityData)
    {
        $priority = $this->getPrioritySet($method);

        $priority->setData($priorityData);

        $priority = $this->store->saveOrFail($priority);

        return $priority;
    }

    public function fetchPriority()
    {
        $result = new Base\PublicCollection;

        foreach (self::ALLOWED_METHODS as $method)
        {
            $priority = $this->getPrioritySet($method);

            $priority = $this->store->fetchOrFail($priority);

            $result->push($priority->toArray());
        }

        return $result;
    }

    public function removePriorityForMethod(string $method, array $gateways)
    {
         $priority = $this->getPrioritySet($method);

         $priority->setData($gateways);

         $priority = $this->store->deleteOrFail($priority);

         $priority = $this->store->fetchOrFail($priority);

         return $priority;
    }

    public function getGatewaysForMethod(string $method)
    {
        $gateways = $this->fetchOrderedGatewaysForMethod($method) ??
                    Defaults::GATEWAY_ORDER[$method][Mode::LIVE];

        if ($this->mode === Mode::TEST)
        {
            $gateways = array_merge($gateways, Defaults::GATEWAY_ORDER[$method][Mode::TEST]);
        }

        if ($method === Method::NETBANKING)
        {
            // For netbanking we boost direct netbanking gateways
            array_unshift($gateways, 'direct');
        }

        return $gateways;
    }

    /**
     * Fetches the gateways ordered by priority score
     *
     * @param  string $method Method to fetch gateway s for
     * @return [type]         [description]
     */
    protected function fetchOrderedGatewaysForMethod(string $method)
    {
        $priority = $this->getPrioritySet($method);

        $priority = $this->store->fetch($priority);

        return $this->getGateways($priority);
    }

    /**
     * While fetching gateways we only fetcg gateways with positive scores.
     * Gateways with zero scores are ignored
     */
    protected function getGateways(DataStore\PrioritySet $priority)
    {
        return $priority->getSetMembersWithPositiveScore();
    }

    protected function getPrioritySet(string $method)
    {
        return (new DataStore\PrioritySet(self::$storeNameSpace, $method));
    }
}
