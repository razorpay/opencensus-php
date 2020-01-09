<?php

namespace RZP\Models\Batch\Processor;


use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Processor\Base as BaseProcessor;


class MerchantConfigInheritance extends BaseProcessor
{
    public function __construct(Entity $batch)
    {
        parent::__construct($batch);
    }
}
