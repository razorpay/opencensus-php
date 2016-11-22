<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use RZP\Models\FileStore\Type;
use RZP\Models\FileStore\Storage\Base;

class Bucket extends Base\Bucket
{
    const BUCKET_MAP = [
        Type::KOTAK_NETBANKING_REFUND   => 'settlement_bucket',
        Type::HDFC_NETBANKING_REFUND    => 'settlement_bucket',
        Type::AIRTELMONEY_WALLET_REFUND => 'settlement_bucket',
        Type::PAYUMONEY_WALLET_REFUND   => 'settlement_bucket',
        Type::ICICI_UPI_REFUND          => 'settlement_bucket',
        Type::BATCH_INPUT               => 'batch_bucket',
        Type::BATCH_OUTPUT              => 'batch_bucket',
    ];
}
