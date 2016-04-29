<?php

namespace Models;

use Gateway\GatewayManager;

class Gateway
{
    public static function call($gateway, $action, $input, $mode, $terminal = null)
    {
        $gateway = self::getGatewayInstance($gateway);

        $gateway->setTerminal($terminal);

        $gateway->setMode($mode);

        // Laravel helper function converts snake case to camel case
        $action = camel_case($action);

        return  $gateway->$action($input);
    }

    protected static function getGatewayInstance($gateway)
    {
        $app = \App::getFacadeRoot();

        return $app['gateway']->gateway($gateway);
    }
}
