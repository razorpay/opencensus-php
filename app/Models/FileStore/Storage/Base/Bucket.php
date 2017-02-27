<?php

namespace RZP\Models\FileStore\Storage\Base;

use RZP\Constants\Mode;
use RZP\Models\FileStore\Type;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Bucket
{
    const DEFAULT_CONFIG_NAME = 'settlement_bucket';

    const TEST_BUCKET_NAME = 'test_bucket';

    const BUCKET_MAP = [
        Type::KOTAK_NETBANKING_REFUND                => 'settlement_bucket_config',
        Type::HDFC_NETBANKING_REFUND                 => 'settlement_bucket_config',
        Type::AXIS_NETBANKING_REFUND                 => 'settlement_bucket_config',
        Type::AIRTELMONEY_WALLET_REFUND              => 'settlement_bucket_config',
        Type::PAYUMONEY_WALLET_REFUND                => 'settlement_bucket_config',
        Type::ICICI_UPI_REFUND                       => 'settlement_bucket_config',
        Type::BATCH_INPUT                            => 'batch_bucket_config',
        Type::BATCH_OUTPUT                           => 'batch_bucket_config',
        Type::INVOICE_PDF                            => 'invoice_bucket_config',

        MerchantDetail::BUSINESS_PROOF_URL           => 'activation_bucket_config',
        MerchantDetail::BUSINESS_OPERATION_PROOF_URL => 'activation_bucket_config',
        MerchantDetail::BUSINESS_PAN_URL             => 'activation_bucket_config',
        MerchantDetail::ADDRESS_PROOF_URL            => 'activation_bucket_config',
        MerchantDetail::PROMOTER_PROOF_URL           => 'activation_bucket_config',
        MerchantDetail::PROMOTER_PAN_URL             => 'activation_bucket_config',
        MerchantDetail::PROMOTER_ADDRESS_URL         => 'activation_bucket_config',
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
