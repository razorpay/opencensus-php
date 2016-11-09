<?php

namespace RZP\Models\FileStore\Storage\Base;

use App;
use RZP\Models\Base\Core;

abstract class Handler extends Core
{
    abstract public function save($bucket, $fileDetails);

    abstract public function getBucketName($entityName);
}
