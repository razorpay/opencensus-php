<?php

namespace RZP\Models\P2p\Client;

use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const GET_GATEWAY_CONFIG               = 'getGatewayConfig';
    const GET_GATEWAY_CONFIG_SUCCESS       = 'getGatewayConfigSuccess';
    const GET_GATEWAY_CONFIG_FAILURE       = 'getGatewayConfigFailure';
}
