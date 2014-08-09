<?php

namespace Models;

use Gateway\GatewayManager;

class Gateway
{
    public static function call($action, $input, $mode, $terminal = null)
    {
        $gateway = self::getGatewayInstance()->driver();

        $gateway->setTerminal($terminal);

        $gateway->setMode($mode);

        return  $gateway->$action($input);
    }

    protected static function getGatewayInstance()
    {
        return new GatewayManager;
    }
}