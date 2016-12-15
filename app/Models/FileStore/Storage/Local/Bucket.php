<?php

namespace RZP\Models\FileStore\Storage\Local;

use RZP\Models\FileStore\Type;
use RZP\Models\FileStore\Storage\Base;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Bucket extends Base\Bucket
{
    const BUCKET_MAP = [
        Type::ICICI_NETBANKING_REFUND                   => 'settlement_bucket',
        Type::KOTAK_NETBANKING_REFUND                   => 'netbanking',
        Type::HDFC_NETBANKING_REFUND                    => 'netbanking',
        Type::AIRTELMONEY_WALLET_REFUND                 => 'wallet',
        Type::PAYUMONEY_WALLET_REFUND                   => 'wallet',
        Type::ICICI_UPI_REFUND                          => 'upi',
        Type::BATCH_INPUT                               => 'batch',
        Type::BATCH_OUTPUT                              => 'batch',

        MerchantDetail::BUSINESS_PROOF_URL              => 'activation_bucket',
        MerchantDetail::BUSINESS_OPERATION_PROOF_URL    => 'activation_bucket',
        MerchantDetail::BUSINESS_PAN_URL                => 'activation_bucket',
        MerchantDetail::ADDRESS_PROOF_URL               => 'activation_bucket',
        MerchantDetail::PROMOTER_PROOF_URL              => 'activation_bucket',
        MerchantDetail::PROMOTER_PAN_URL                => 'activation_bucket',
        MerchantDetail::PROMOTER_ADDRESS_URL            => 'activation_bucket',
    ];
}
