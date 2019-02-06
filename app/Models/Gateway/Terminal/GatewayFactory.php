<?php

namespace RZP\Models\Gateway\Terminal;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;

class GatewayFactory
{
    public static function build (string $gateway) : BaseGatewayProcessor
    {
        $className = __NAMESPACE__ . '\\GatewayProcessor\\' . ucfirst($gateway). '\\GatewayProcessor';

        if (class_exists($className) === true)
        {
            return new $className();
        }
        else
        {
            $trace = App::getFacadeRoot()['trace'];

            $trace->info(
                TraceCode::MERCHANT_ONBOARD_GATEWAY_NOT_FOUND_ERROR,
                [
                    'gateway'   => $gateway,
                    'classname' => $className,
                ]);

            throw new Exception\RuntimeException('Gateway Processor doesn\'t exist for, '. $gateway);
        }
    }
}