<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const START         = 'start';
    const END           = 'end';
    const FILE_ID       = 'file_id';
    const ENTITY        = 'entity';
    const MERCHANT_ID   = 'merchant_id';

    protected $entity = 'report';
}
