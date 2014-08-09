<?php

namespace Models;

use Gateway\GatewayManager;

class Gateway
{
    protected static $gateway = null;

    public static function call($action, $input, $mode, $terminal = null)
    {
        $gateway = self::getGatewayInstance();

        $gateway->setTerminal($terminal);

        $gateway->setMode($mode);

        return  self::getGatewayInstance()->$action($input);
    }

    protected static function getGatewayInstance()
    {
        if (self::$gateway === null)
            self::$gateway = new GatewayManager;

        return self::$gateway;
    }
}