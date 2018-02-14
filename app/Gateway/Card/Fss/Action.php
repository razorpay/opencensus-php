<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Exception;
use RZP\Gateway\Base;

class Action extends Base\Action
{
    const VERIFY_REFUND    = 'verify_refund';

    // Actions have there own representation as per fss.
    public static $gatewayActionValues  = [
        self::PURCHASE => '1',
        self::REFUND   => '2',
        self::VERIFY   => '8',
    ];

    /**
     * Gateway constant for action type.
     * @param string $action
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    public static function getActionValue(string $action)
    {
        if (empty(self::$gatewayActionValues[$action]) === true)
        {
            throw new Exception\LogicException(
                'Unsupported Action for the gateway',
                null,
                [
                    'action' => $action,
                ]);
        }

        return self::$gatewayActionValues[$action];
    }
}
