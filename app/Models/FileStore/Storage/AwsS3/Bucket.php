<?php

namespace RZP\Models\FileStore\Storage\AwsS3;

use RZP\Models\FileStore\Type;

class Bucket
{
    const BUCKET_MAP = [
        Type::KOTAK_NETBANKING_REFUND => 'settlement_bucket',
        Type::BATCH                   => 'batch_bucket',
    ];
}
