<?php

namespace RZP\Models\FileStore\Storage\Local;

use RZP\Models\FileStore\Type;
use RZP\Models\FileStore\Storage\Base;

class Bucket extends Base\Bucket
{
    const BUCKET_MAP = [
        Type::KOTAK_NETBANKING_REFUND   => 'netbanking',
        Type::HDFC_NETBANKING_REFUND    => 'netbanking',
        Type::AIRTELMONEY_WALLET_REFUND => 'wallet',
        Type::PAYUMONEY_WALLET_REFUND   => 'wallet',
        Type::ICICI_UPI_REFUND          => 'upi',
        Type::BATCH_INPUT               => 'batch',
        Type::BATCH_OUTPUT              => 'batch',
    ];
}
