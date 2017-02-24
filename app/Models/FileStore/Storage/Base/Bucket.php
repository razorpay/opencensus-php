<?php

namespace RZP\Models\FileStore\Storage\Base;

use RZP\Constants\Mode;
use RZP\Models\FileStore\Type;

class Bucket
{
    const DEFAULT_CONFIG_NAME = 'settlement_bucket';

    const TEST_BUCKET_NAME = 'test_bucket';

    const BUCKET_MAP = [
        Type::KOTAK_NETBANKING_REFUND   => 'settlement_bucket',
        Type::HDFC_NETBANKING_REFUND    => 'settlement_bucket',
        Type::AIRTELMONEY_WALLET_REFUND => 'settlement_bucket',
        Type::PAYUMONEY_WALLET_REFUND   => 'settlement_bucket',
        Type::ICICI_UPI_REFUND          => 'settlement_bucket',
        Type::ICICI_NODAL_TRANSFER      => 'h2h_bucket',
        Type::BATCH_INPUT               => 'batch_bucket',
        Type::BATCH_OUTPUT              => 'batch_bucket',
    ];

    public static function getBucketConfigName($type, $env = 'production')
    {
        $bucketConfigName = static::DEFAULT_CONFIG_NAME;

        if (array_key_exists($type, static::BUCKET_MAP))
        {
            $bucketConfigName = static::BUCKET_MAP[$type];
        }

        if ($env !== 'production')
        {
            $bucketConfigName = static::TEST_BUCKET_NAME;
        }

        return $bucketConfigName;
    }
}
