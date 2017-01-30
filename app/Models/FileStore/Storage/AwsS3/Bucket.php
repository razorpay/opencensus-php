<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use RZP\Models\FileStore\Type;
use RZP\Models\FileStore\Storage\Base;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Bucket extends Base\Bucket
{
    const BUCKET_MAP = [
        Type::ICICI_NETBANKING_REFUND                => 'settlement_bucket',
        Type::KOTAK_NETBANKING_REFUND                => 'settlement_bucket',
        Type::HDFC_NETBANKING_REFUND                 => 'settlement_bucket',
        Type::AXIS_NETBANKING_REFUND                 => 'settlement_bucket',
        Type::AIRTELMONEY_WALLET_REFUND              => 'settlement_bucket',
        Type::PAYUMONEY_WALLET_REFUND                => 'settlement_bucket',
        Type::ICICI_UPI_REFUND                       => 'settlement_bucket',
        Type::ICICI_NODAL_TRANSFER                   => 'h2h_bucket',
        Type::BATCH_INPUT                            => 'batch_bucket',
        Type::BATCH_OUTPUT                           => 'batch_bucket',

        MerchantDetail::BUSINESS_PROOF_URL           => 'activation_bucket',
        MerchantDetail::BUSINESS_OPERATION_PROOF_URL => 'activation_bucket',
        MerchantDetail::BUSINESS_PAN_URL             => 'activation_bucket',
        MerchantDetail::ADDRESS_PROOF_URL            => 'activation_bucket',
        MerchantDetail::PROMOTER_PROOF_URL           => 'activation_bucket',
        MerchantDetail::PROMOTER_PAN_URL             => 'activation_bucket',
        MerchantDetail::PROMOTER_ADDRESS_URL         => 'activation_bucket',
    ];
}