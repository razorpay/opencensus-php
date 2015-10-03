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

        return  $gateway->$action($input);
    }

    protected static function getGatewayInstance($gateway)
    {$gateway='wallet_payzapp';
        $app = \App::getFacadeRoot();
        $gatewayManager = new GatewayManager($app);
        return $gatewayManager->gateway($gateway);
    }
}