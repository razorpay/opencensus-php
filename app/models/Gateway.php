<?php

namespace Models;

use Gateway\GatewayManager;

class Gateway
{
    protected static $gateway = null;

    public static function call($action, $input)
    {
        $gateway = self::getGatewayInstance();

        return  self::getGatewayInstance()->$action($input);
   }

    protected static function getGatewayInstance()
    {
        if (self::$gateway === null)
            self::$gateway = new GatewayManager;

        return self::$gateway;
    }
}