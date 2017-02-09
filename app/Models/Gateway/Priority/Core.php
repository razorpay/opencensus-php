<?php

namespace RZP\Models\Gateway\Priority;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\DataStore\PrioritySet;
use RZP\Models\Payment\Method;

class Core extends Base\Core
{
    const ALLOWED_METHODS = [Method::CARD, Method::NETBANKING];

    protected $validator;

    // Namespace used for storing the data in store provider
    protected $storeNameSpace = 'gateway_priority';

    protected function init()
    {
        $this->validator = new Validator;
    }

    public function addPriorityForMethod(string $method, array $priorityData)
    {
        $this->validator->validateAddPriority($method, $priorityData);

        $priority = $this->getPrioritySet($method);

        $priority->setData($priorityData);

        $priority = $priority->saveOrFail();

        return $priority;
    }

    public function fetchPriority()
    {
        $result = new Base\PublicCollection;

        foreach (self::ALLOWED_METHODS as $method)
        {
            $priority = $this->getPrioritySet($method);

            $priority = $priority->fetchOrFail();

            $result->push($priority->toArray());
        }

        return $result;
    }

    public function addOrUpdatePriorityForMethod(string $method, array $priorityData)
    {
        $priority = $this->addPriorityForMethod($method, $priorityData);

        $priority = $priority->fetchOrFail();

        return $priority;
    }

    public function removePriorityForMethod(string $method, array $gateways)
    {
        $this->validator->validateRemovePriority($method, $gateways);

        $priority = $this->getPrioritySet($method);

        $priority->setData($gateways);

        $priority = $priority->deleteOrFail();

        $priority = $priority->fetchOrFail();

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

        try
        {
            $priority = $priority->fetchOrFail();
        }
        catch(Exception\ServerErrorException $e)
        {
            $priority->setData([]);
        }

        return $this->getGateways($priority);
    }

    /**
     * While fetching gateways we only fetcg gateways with positive scores.
     * Gateways with zero scores are ignored
     */
    protected function getGateways(PrioritySet\Base $priority)
    {
        return $priority->getSetMembersWithPositiveScore();
    }

    protected function getPrioritySet(string $method)
    {
        $mock = $this->app['config']->get('app.data_store.mock');
        $gatewayPriorityStoreType = $this->app['config']->get('app.gateway_priority.store_type');

        $prioritySet = PrioritySet\Factory::getStore($gatewayPriorityStoreType, $mock);

        $prioritySet->setPrefix($this->storeNameSpace);

        $prioritySet->setKey($method);

        return $prioritySet;
    }
}
