<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Store
{
    const S3    = 's3';
    const LOCAL = 'local';

    const STORE_MAP = [
        self::S3 => Storage\AwsS3\Handler::class,
    ];

    public static function validateStore($store)
    {
        if (defined(__CLASS__.'::'.strtoupper($store)) === false)
        {
            throw new Exception\LogicException(
                'Not a valid store: ' . $store);
        }
    }

    public static function getHandler($store)
    {
        self::validateStore($store);

        $class = self::STORE_MAP[$store];

        return new $class;
    }
}
