<?php

namespace RZP\Gateway\Upi\Hulk;

use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Exception\GatewayErrorException;

class Type
{
    const PULL          = 'pull';
    const PUSH          = 'push';
    const EXPECTED_PUSH = 'expected_push';

    protected static $map = [
        self::PULL              => Base\Type::COLLECT,
        self::PUSH              => Base\Type::PAY,
        self::EXPECTED_PUSH     => Base\Type::PAY,
    ];

    /**
     * Maps HULK type to API type which is saved in DB
     *
     * @param string $type
     * @return string
     */
    public static function getMappedTyped(string $type): string
    {
        if (isset(self::$map[$type]) === true)
        {
            return self::$map[$type];
        }

        throw new GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            null,
            null,
            [
                'type'  => $type,
            ]);
    }
}
