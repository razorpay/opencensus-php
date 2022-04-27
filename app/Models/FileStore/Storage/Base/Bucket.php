<?php

namespace RZP\Models\FileStore\Storage\Base;

use RZP\Constants\Mode;
use RZP\Models\FileStore\Type;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Trace\TraceCode;

class Bucket
{
    const DEFAULT_CONFIG_NAME = Type::AP_SOUTH_DEFAULT_SETTLEMENT_BUCKET_CONFIG;
    const TEST_BUCKET_NAME    = Type::TEST_BUCKET_CONFIG;

    /**
     * @param string $type File Type
     * @param string $env  Environment
     *
     * @return string Bucket config Name
     */
    public static function getBucketConfigName($type, $env = 'production')
    {
        $bucketConfigName = static::DEFAULT_CONFIG_NAME;

        $bucketConfigTypeMap = Type::BUCKET_CONFIG_TYPE_MAPPING;

        foreach ($bucketConfigTypeMap as $bucketConfig => $types)
        {
            if (in_array($type, $types))
            {
                $bucketConfigName = $bucketConfig;
                break;
            }
        }

        if (($env !== 'production') and ($env !== 'testing'))
        {
            $bucketConfigName = static::TEST_BUCKET_NAME;
        }

        return $bucketConfigName;
    }
}
