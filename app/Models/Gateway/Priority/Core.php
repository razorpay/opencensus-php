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

    public function getGatewaysForMethod(string $method)
    {
        $gateways = Defaults::GATEWAY_ORDER[$method][Mode::LIVE];

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
}
