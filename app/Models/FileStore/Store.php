<?php

namespace RZP\Models\FileStore;

class Store
{
    const S3    = 's3';
    const LOCAL = 'local';

    const STORE_MAP = [
        self::S3 => Storage\AwsS3\Handler::class,
    ];

    public static function validateStore($store)
    {
        if (defined(__CLASS__.'::'.$store) === false)
        {
            throw new Exception\LogicException(
                'Not a valid store: ' . $store);
        }
    }

    public static function getHandler($storage)
    {
        self::validateStore($store);

        $class = self::STORE_MAP[$storage];

        return new $class;
    }
}
