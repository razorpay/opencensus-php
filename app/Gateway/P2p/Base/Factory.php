<?php

namespace RZP\Gateway\P2p\Base;

use RZP\Gateway\P2p\Upi;
use RZP\Models\P2p\Base\Libraries\Context;

class Factory
{
    public static function make(Context $context, string $interface)
    {
        $name   = $context->getGatewayData()->get('name');
        $action = $context->getGatewayData()->get('action');
        $input  = $context->getGatewayData()->get('input');

        $class = self::getGatewayClass($name, class_basename($interface));

        $gateway = new $class;

        $gateway->setContext($context);

        $gateway->setActionAndInput($action, $input);

        return $gateway;
    }

    public static function getGatewayClass(string $gateway, string $append)
    {
        $namespace = static::class;

        switch ($gateway)
        {
            case 'p2p_upi_sharp':
                $namespace = Upi\Sharp::class;
                break;

            case 'p2p_upi_axis':
                $namespace = Upi\Axis::class;
                break;

            case 'p2m_upi_axis_olive':
                $namespace = Upi\AxisOlive::class;
                break;
        }

        $className = $namespace . '\\' . $append;

        return $className;
    }

    public static function getServerClass(string $gateway)
    {
        return self::getGatewayClass($gateway, 'Mock\\Server');
    }
}
