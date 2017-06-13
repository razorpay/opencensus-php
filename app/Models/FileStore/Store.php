<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Store
{
    const S3    = 's3';

    const LOCAL = 'local';

    const STORAGE_DIRECTORY = 'files/filestore/';

    const STORE_MAP = [
        self::S3    => Storage\AwsS3\Handler::class,
        self::LOCAL => Storage\Local\Handler::class,
    ];

    /**
     * Check if store passed is valid or not
     *
     * @param  string   $store
     * @return void
     * @throws Exception\LogicException
     */
    public static function validateStore($store)
    {
        if (defined(__CLASS__.'::'.strtoupper($store)) === false)
        {
            throw new Exception\LogicException(
                'Not a valid Store: ' . $store);
        }
    }

    /**
     * Returns the Store Handler Object
     *
     * @param string $store Store
     *
     * @return Store instance of Store Object
     * @throws Exception\LogicException
     */
    public static function getHandler($store)
    {
        self::validateStore($store);

        $class = self::STORE_MAP[$store];

        return new $class;
    }
}
