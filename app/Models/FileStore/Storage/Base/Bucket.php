<?php

namespace RZP\Models\FileStore\Storage\Base;

use RZP\Models\FileStore\Type;

class Bucket
{
    const DEFAULT_CONFIG_NAME = 'settlement_bucket';

    const BUCKET_MAP = [
        Type::KOTAK_NETBANKING_REFUND   => 'settlement_bucket',
        Type::HDFC_NETBANKING_REFUND    => 'settlement_bucket',
        Type::AIRTELMONEY_WALLET_REFUND => 'settlement_bucket',
        Type::PAYUMONEY_WALLET_REFUND   => 'settlement_bucket',
        Type::ICICI_UPI_REFUND          => 'settlement_bucket',
        Type::BATCH_INPUT               => 'batch_bucket',
        Type::BATCH_OUTPUT              => 'batch_bucket',
    ];

    public static function getBucketConfigName($name)
    {
        $bucketConfigName = self::DEFAULT_CONFIG_NAME;

        if (array_key_exists($name, self::BUCKET_MAP))
        {
            $bucketConfigName = self::BUCKET_MAP[$name];
        }

        return $bucketConfigName;
    }
}
